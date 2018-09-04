<?php


namespace Aniart\Main\Models;


class SaleDeliveryService extends SaleDelivery
{
    public function getParentId()
    {
        return $this->fields['PARENT_ID'];
    }
}