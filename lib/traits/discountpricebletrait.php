<?php


namespace Aniart\Main\Traits;


/**
 * Class DiscountPricebleTrait
 * @package Aniart\Main\Traits
 */
trait DiscountPricebleTrait
{
    abstract public function getBasePrice();
    abstract public function getPrice();
    abstract public function getCurrency();

    public function hasDiscount()
    {
        return abs($this->getDiscountPrice()) > 0.001;
    }

    public function getDiscountPercentage($format = false)
    {
        $percentage = ($this->getDiscountPrice() / $this->getBasePrice()) * 100;
        return $format ? $percentage.'%' : $percentage;
    }

    public function getDiscountPrice($format = false)
    {
        $price = $this->getBasePrice() - $this->getPrice();
        return $format ? \CCurrencyLang::CurrencyFormat($price, $this->getCurrency()) : $price;
    }
}