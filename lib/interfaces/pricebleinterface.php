<?php


namespace Aniart\Main\Interfaces;


interface PricebleInterface
{
    public function getPrice($format = false);
    public function getCurrency();
}