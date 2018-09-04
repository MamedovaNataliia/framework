<?php

namespace Aniart\Main\Models;

use Aniart\Main\Interfaces\DiscountPricebleInterface;
use Aniart\Main\Repositories\BasketItemsRepository;

class Basket extends AbstractModel implements DiscountPricebleInterface
{

    protected $currency;
    protected $itemsCount = 0;
    protected $itemsQuantity = 0;
    protected $price = 0;
    protected $basePrice = 0;
    protected $discountPrice = 0;

    /**
     * @var BasketItem[]
     */
    protected $basketItems = [];

    /**
     * @var BasketItemsRepository
     */
    protected $basketItemsRepository;
    private $precision = SALE_VALUE_PRECISION;

    public function __construct(array $fields = [])
    {
        $this->basketItemsRepository = app('BasketItemsRepository');
        if(isset($fields['BASKET_ITEMS']))
        {
            $this->setItems($fields['BASKET_ITEMS']);
            unset($fields['BASKET_ITEMS']);
        }
        parent::__construct($fields);
    }

    public function setItems(array $basketItems)
    {
        $this->resetData();
        foreach($basketItems as $basketItem)
        {
            if(is_array($basketItem))
            {
                $basketItem = $this->basketItemsRepository->newInstance($basketItem);
            }
            if($basketItem instanceof BasketItem)
            {
                if(!$this->currency)
                {
                    $this->currency = $basketItem->getCurrency();
                }

                $quantity = $basketItem->getQuantity();

                $this->price += $basketItem->getTotalPrice();
                $this->basePrice += $basketItem->getTotalBasePrice();
                $this->discountPrice += $basketItem->getDiscountPrice() * $quantity;
                $this->itemsQuantity += $quantity;

                $this->basketItems[$basketItem->getId()] = $basketItem;
            }
        }
        $this->itemsCount = count($this->basketItems);
        return $this;
    }

    private function resetData()
    {
        $this->itemsCount = 0;
        $this->itemsQuantity = 0;
        $this->price = 0;
        $this->basePrice = 0;
        $this->discountPrice = 0;
        $this->basketItems = [];
    }

    public function getItems()
    {
        return $this->basketItems;
    }

    public function getItem($itemId)
    {
        return $this->getItems()[$itemId];
    }

    public function getItemsByProductId($productId)
    {
        return array_filter($this->getItems(), function(BasketItem $basketItem) use ($productId)
        {
            return $basketItem->getProductId() == $productId;
        });
    }

    public function itemsCount()
    {
        return $this->itemsCount;
    }

    public function itemsQuantity()
    {
        return $this->itemsQuantity;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getPrice($format = false)
    {
        return $this->formatPrice($this->price, $format);
    }

    public function getBasePrice($format = false)
    {
        return $this->formatPrice($this->basePrice, $format);
    }

    public function hasDiscount()
    {
        return ($this->getDiscountPrice() > 0);
    }

    public function getDiscountPrice($format = false)
    {
        return $this->formatPrice($this->discountPrice, $format);
    }

    protected function formatPrice($price, $format = false)
    {
        $price = round($price, $this->getPrecision());
        return $format ? SaleFormatCurrency($price, $this->getCurrency()) : $price;
    }

    public function getDiscountPercentage($round = null)
    {
        if(is_null($round))
        {
            $round = $this->getPrecision();
        }
        return round(($this->getDiscountPrice() / $this->getBasePrice()) * 100, $round);
    }

    /**
     * @return int
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @param int $precision
     * @return $this
     */
    public function setPrecision($precision)
    {
        $this->precision = $precision;
        return $this;
    }

}
