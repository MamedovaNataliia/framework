<?php


namespace Aniart\Main\Interfaces;


interface DiscountPricebleInterface extends PricebleInterface
{
    public function hasDiscount();
    public function getDiscountPrice($format = false);
    public function getDiscountPercentage($format = false);
    public function getBasePrice($format = false);
}