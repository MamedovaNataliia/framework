<?php

namespace Aniart\Main\Repositories;


class SaleOrdersRepository extends AbstractBitrixRepository
{
    protected $bitrixClass = "\\CSaleOrder";

    public function newInstance(array $fields = array())
    {
        return app('Order', array($fields));
    }
}