<?php

namespace Aniart\Main\Repositories;

class BasketItemsRepository extends AbstractBitrixRepository
{

    protected $bitrixClass = "\\CSaleBasket";
    
    /**
     * @param array $fields
     * @return \Aniart\Main\Models\Product
     */
    public function newInstance(array $fields = array())
    {
        return app('BasketItem', array($fields));
    }

}
