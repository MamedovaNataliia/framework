<?php


namespace Aniart\Main\Repositories;

class StoresRepository
{
    public function newInstance(array $fields)
    {
        return app('Store', array($fields));
    }

    public function getList(
        $arOrder = array('SORT' => 'ASC'), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectedFields = array())
    {
        $stores = array();
        if (empty($arSelectedFields)) {
            $arSelectedFields = [
                "*",
                "UF_*"
            ];
        }
        $rsStores = \CCatalogStore::GetList($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectedFields);
        while ($arStore = $rsStores->GetNext()) {
            $stores[$arStore['ID']] = $this->newInstance($arStore);
        }
        return $stores;
    }
}