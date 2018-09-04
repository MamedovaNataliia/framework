<?

namespace Aniart\Main\Ajax\Handlers;

use Aniart\Main\Ajax\AbstractAjaxHandler;
use Aniart\Main\Exceptions\AniartException;
use Aniart\Main\Services\BasketService;

class BasketAjaxHandler extends AbstractAjaxHandler
{

    protected $lang;
    protected $productProvider = '\Aniart\Main\Services\CustomProductProvider';

    /**
     * @var BasketService
     */
    protected $basketService;

    public function __construct()
    {
        $this->lang = i18n()->lang();
        $this->basketService = app('BasketService');
        parent::__construct();
    }

    protected function getFunction()
    {
        return $this->request['func'];
    }

    public function add()
    {
        $offer = $this->request['offer'];
        return $this->doAdd($offer);
    }

    public function delete()
    {
        $id = $this->request['id'];
        try
        {
            $this->basketService->deleteItem($id);
        }
        catch(AniartException $e)
        {
            return $this->setError($e->getMessage());
        }

        return $this->setOK(["count" => $this->basketService->getItemsCount()]);
    }

    protected function doAdd($offer)
    {
        $errMessage = i18n('ADD_TO_BASKET_ERROR', 'basket');
        try
        {
            $basketItemId = $this->basketService->addProductToBasket($offer);
            return $this->setOK(['id' => $basketItemId, 'count' => $this->basketService->getItemsCount()]);
        }
        catch(AniartException $e)
        {
            $bs = $this->basketService;
            if($e->getCode() === $bs::BX_ERROR_CODE)
            {
                if($this->lang == 'ru')
                {
                    $errMessage = $e->getMessage();
                }
            }
            return $this->setError($errMessage);
        }
    }

    public function update()
    {
        $basketItemId = (int) $this->request['id'] ? $this->request['id'] : false;
        $quantity = $this->request['quantity'] ? $this->request['quantity'] : 0;
        $arFields['QUANTITY'] = $quantity;

        if(\CModule::IncludeModule('sale') || \CModule::IncludeModule('catalog'))
        {
            $dbRes = \CSaleBasket::GetPropsList(array(), array('BASKET_ID' => $basketItemId));
            $curProps = array();
            while($arProp = $dbRes->Fetch())
            {
                $curProps[$arProp['CODE']] = $arProp;
            }

            $arFields['CAN_BUY'] = 'Y';

            if(!$quantity)
                \CSaleBasket::Update($basketItemId, $arFields);
            else
            {
                \CSaleBasket::_Update($basketItemId, $arFields);
            }
            $this->setOK(['count' => $this->basketService->getItemsCount()]);
        }
    }

    public function clear()
    {
        $this->basketService->clearBasket();
    }
    
    public function getBasketList()
    {
        global $APPLICATION;
        ob_start();
        $APPLICATION->IncludeComponent(
            'aniart:basket.list',
            'header',
            [
                'CACHE_TYPE' => 'N',
                'CACHE_TIME' => '0',
                'CACHE_FILTER' => '',
                'CACHE_GROUPS' => 'N',
                'PATH_TO_ORDER'=> '/order/'
            ]
        );
        $html = ob_get_contents();
        ob_end_clean();
        $this->setOK($html);
    }

}
