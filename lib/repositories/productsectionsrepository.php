<?php


namespace Aniart\Main\Repositories;


class ProductSectionsRepository extends AbstractIblockSectionsRepository
{
    protected $selectedFields = [
        '*'
    ];

    public function newInstance(array $fields = array())
    {
        return app('ProductSection', [$fields]);
    }
}