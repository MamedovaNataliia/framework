<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 8/9/2017
 * Time: 1:25 PM
 */

namespace Aniart\Main\Models;


class Review extends IblockElementModel
{
    function __construct($fields)
    {
        parent::__construct($fields);
    }

    public function getComment()
    {
        $data = $this->getPropertyValue('COMMENT');
        if ($data) {
            return $data['TEXT'];
        } else {
            return false;
        }
    }

    public function getAuthorName()
    {
        $data = $this->getPropertyValue('AUTHOR_NAME');
        if ($data) {
            $rsUser = \CUser::GetByID($data);
            $arUser = $rsUser->Fetch();
            return ($arUser['NAME']) ? $arUser['NAME'] : $arUser['LOGIN'];

        } else {
            return false;
        }

    }

    public function getDate($format_str = "")
    {

        $data = $this->getPropertyValue('DATE');
        return $data;


    }
}