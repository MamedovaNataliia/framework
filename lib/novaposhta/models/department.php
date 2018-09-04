<?php


namespace Aniart\Main\NovaPoshta\Models;

/**
 * Class Department
 * @package Aniart\Main\NovaPoshta\Models
 * @link https://devcenter.novaposhta.ua/docs/services/556d7ccaa0fe4f08e8f7ce43/operations/556d8211a0fe4f08e8f7ce45
 */
class Department extends AbstractModel
{
    protected $schedule;

    public function getAvailableFields()
    {
        return [
            'ID', 'SITE_KEY', 'NAME_RU', 'NAME_UA', 'PHONE', 'TYPE_REF_ID', 'REF_ID', 'NUMBER', 'CITY_REF_ID',
            'CITY_NAME_RU', 'CITY_NAME_UA', 'LONGITUDE', 'LATITUDE', 'POST_FINANCE', 'BICYCLE_PARKING',
            'POS_TERMINAL', 'INTERNATIONAL_SHIPPING', 'TOTAL_MAX_WEIGHT_ALLOWED', 'PLACE_MAX_WEIGH_ALLOWED',
            'RECEPTION', 'DELIVERY', 'SCHEDULE'
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

    public function getSiteKey()
    {
        return $this->fields['CITY_KEY'];
    }

    public function getName($lang = 'ru')
    {
        $lang = strtoupper($lang);
        return $this->fields['NAME_'.$lang];
    }

    public function getCityName($lang = 'ru')
    {
        $lang = strtoupper($lang);
        return $this->fields['CITY_NAME_'.$lang];
    }

    public function getPhone()
    {
        return $this->fields['PHONE'];
    }

    public function getSchedule()
    {
        if(is_null($this->schedule)){
            $this->schedule = array_filter($this->fields['SCHEDULE'], function($item){
                return $item != '"-"';
            });
        }
        return $this->schedule;
    }
}