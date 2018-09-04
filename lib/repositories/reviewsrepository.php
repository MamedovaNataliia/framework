<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 8/9/2017
 * Time: 1:13 PM
 */
namespace Aniart\Main\Repositories;

class ReviewsRepository extends AbstractIblockElementRepository{

    public function newInstance(array $fields = array())
    {
        return app('Review', [$fields]);
    }



}