<?php

namespace Aniart\Main\Services;

use Aniart\Main\Exceptions\AniartException;
use Aniart\Main\Repositories\OffersRepository;

class BasketService
{

    const DEFAULT_PRODUCT_PROVIDER = '\Aniart\Main\Services\CustomProductProvider';
    const BX_ERROR_CODE = 101;

    /**
     * @var BasketItem
     */
    protected $basketItemsRepository;
    protected $basketItemClass;

    /**
     * @var OffersRepository
     */
    protected $offerRepository;

    public function __construct()
    {
        $this->basketItemsRepository = app('BasketItemsRepository');
        $this->offerRepository = app('OffersRepository');
        $this->basketItemClass = get_class($this->basketItemsRepository->newInstance([]));
    }

    /**
     * Добавляет товар в корзину
     * @param $product - ИД или объект товара в каталоге
     * @param int $quantity
     * @param array $arRewriteFields
     * @param array $arProductParams
     * @return int|false
     * @throws BasketServiceException
     */
    public function addProductToBasket($productID, $quantity = 1, $arRewriteFields = [], $arProductParams = [])
    {
        $basketItemParams = [];

        $offer = $this->offerRepository->getById($productID);

        if(!$offer)
            throw new AniartException("Offer not found");

        $basketItemParams[] = [
            "NAME" => i18n("SIZE"),
            "CODE" => "SIZE",
            "VALUE" => $offer->getSize()
        ];


        $existedBasketItem = array_shift($this->getBasket()->getItemsByProductId($productID));

        //$arRewriteFields = array_merge(['PRODUCT_PROVIDER_CLASS' => self::DEFAULT_PRODUCT_PROVIDER], $arRewriteFields);
        $arRewriteFields = [];
        $arProductParams = array_merge($arProductParams, $basketItemParams);
        $basketItemId = \Add2BasketByProductID(
                $productID, $quantity, $arRewriteFields, $arProductParams
        );
        if($basketItemId)
        {
            return $basketItemId;
        }
        else
        {
            global $APPLICATION;
            $message = 'Cannot add product to basket!';
            if($e = $APPLICATION->GetException())
            {
                $message = $e->GetString();
            }
            throw new AniartException($message, self::BX_ERROR_CODE);
        }
    }

    public function deleteItem($id)
    {
        global $APPLICATION;

        $result = \CSaleBasket::Delete($id);

        if($result)
            return $result;

        if($e = $APPLICATION->GetException())
        {
            $message = $e->GetString();
        }

        throw new AniartException($message);
    }

    /**
     * @param int|\Aniart\Main\Models\BasketItem $basketItem
     * @return \Aniart\Main\Models\BasketItem
     * @throws BasketServiceException
     */
    public function getBasketItem($basketItem)
    {
        if(is_numeric($basketItem))
        {
            $basketItem = $this->basketItemsRepository->getById($basketItem);
        }
        $basketItemClass = $this->basketItemClass;
        if(!($basketItem instanceof $basketItemClass))
        {
            throw new BasketServiceException('$basketItem must be numeric or object of "' . $basketItemClass . '" class');
        }
        return $basketItem;
    }

    /**
     * @param int|null $fUserId
     * @return array
     */
    public function getBasket($fUserId = null)
    {
        $fUserId = $this->normalizeFUserId($fUserId);
        $basketItems = $this->basketItemsRepository->getList([], ['FUSER_ID' => $fUserId, 'ORDER_ID' => null]);
        return app('Basket', [['BASKET_ITEMS' => $basketItems]]);
    }

    public function clearBasket($fUserId = null)
    {
        $fUserId = $this->normalizeFUserId($fUserId);
        \CSaleBasket::DeleteAll($fUserId);
    }

    /**
     * @param $fUserId
     * @return int
     */
    private function normalizeFUserId($fUserId = null)
    {
        $fUserId = (int) $fUserId;
        if(!$fUserId)
        {
            $fUserId = \CSaleBasket::GetBasketUserID();
        }
        return $fUserId;
    }

    public function getItemsCount()
    {
        return $this->getBasket()->itemsCount();
    }

}
