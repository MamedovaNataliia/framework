<?php


namespace Aniart\Main\NovaPoshta\Models;



class City extends AbstractModel
{
    public function getAvailableFields()
    {
        return [
            'ID', 'REF_ID', 'NAME_RU', 'NAME_UA', 'AREA_REF_ID'
        ];
    }

    public function getId()
    {
        return $this->fields['ID'];
    }

    public function getRefId()
    {
        return $this->fields['REF_ID'];
    }

    public function getName($lang = 'ru')
    {
        $lang = strtoupper($lang);
        return $this->fields['NAME_'.$lang];
    }

    public function getAreaRefId()
    {
        return $this->fields['AREA_REF_ID'];
    }

}