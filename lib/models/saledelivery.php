<?php


namespace Aniart\Main\Models;


use Aniart\Main\Interfaces\PricebleInterface;
use Aniart\Main\Repositories\StoresRepository;

class SaleDelivery extends AbstractModel implements PricebleInterface
{
    protected $stores;
    /**
     * @var StoresRepository
     */
    protected $storesRepository;

    public function __construct(array $fields)
    {
        $this->storesRepository = app('StoresRepository');
        parent::__construct($fields);
    }

    public function getName($lang = null)
    {
        $lang = $lang ?: i18n()->lang();
        if(i18n()->isLangDefault($lang)){
            $name = $this->fields['NAME'];
        }
        else{
            $name = i18n("DELIVERY_".$this->getId(), 'order', $lang);
        }
        return $name;
    }

    public function getDescription($lang = 'ru')
    {
        $lang = $lang ?: i18n()->lang();
        if(i18n()->isLangDefault($lang)){
            $desc = $this->fields['DESCRIPTION'];
        }
        else{
            $desc = i18n("DELIVERY_".$this->getId().'_DESCRIPTION', 'order', $lang);
        }
        return $desc;

    }

    public function hasStores()
    {
        return count($this->getStoresId()) > 0;
    }

    public function isChecked()
    {
        return (isset($this->fields['CHECKED']) && $this->fields['CHECKED'] == 'Y');
    }

    public function getPrice($format = false)
    {
        $price = $this->fields['PRICE'];
        return $format ? FormatCurrency($price, $this->getCurrency()) : $price;
    }

    public function getCurrency()
    {
        return $this->fields['CURRENCY'];
    }

    /**
	 * @return Store[]
	 */
    public function getStores()
    {
        if(is_null($this->stores)){
            $this->stores = array();
            if($this->hasStores()){
            	$arFilter = array('ACTIVE' => 'Y', 'ID' => $this->getStoresId());
                $this->stores = $this->storesRepository->getList(
                    array('SORT' => 'ASC'), $arFilter
                );
            }
        }
        return $this->stores;
    }

	public function getStoresId()
    {
        return $this->fields['STORE'];
    }

    public function isCourier()
    {
        return $this->getId() == COURIER_DELIVERY_ID;
    }

    public function isNewPost()
    {
        return $this->getId() == NEW_POST_DELIVERY_ID;
    }

    public function isNewPostStores()
    {
        return $this->getId() == NEW_POST_STORE_DELIVERY_ID;
    }
}
