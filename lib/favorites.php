<?php
namespace Aniart\Main;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
/**
 * Class FavoritesTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_ID int mandatory
 * <li> PRODUCTI_ID int mandatory
 * </ul>
 *
 * @package Bitrix\User
 **/

class FavoritesTable extends Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return USER_FAVORITES_TABLE;
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage('FAVORITES_ENTITY_ID_FIELD'),
            ),
            'USER_ID' => array(
                'data_type' => 'integer',
                'required' => true,
                'title' => Loc::getMessage('FAVORITES_ENTITY_USER_ID_FIELD'),
            ),
            'PRODUCT_ID' => array(
                'data_type' => 'integer',
                'required' => true,
                'title' => Loc::getMessage('FAVORITES_ENTITY_PRODUCTI_ID_FIELD'),
            ),
        );
    }

    public static function getProductIds($userId){
        if($userId== 0)
            return false;

        $arSelect = array("PRODUCT_ID");
        $arFilter = array('=USER_ID' => $userId);
        $arGroupBy =  array("PRODUCT_ID");

        $arResult = self::setQuery($arSelect,$arFilter,$arGroupBy);
        $arProductIds = [];

        if(count($arResult) > 0){
           foreach ($arResult as $item){
               $arProductIds[] = $item['PRODUCT_ID'];
           }
        }


        return $arProductIds;

    }

    public static function isExist($userId,$productID){

        if($userId == 0 || $productID == 0)
            throw new Exception('Пустые данные');

        $arSelect = array("ID");
        $arFilter = array('=USER_ID' => $userId,"=PRODUCT_ID" => $productID);

        $arResult = self::setQuery($arSelect,$arFilter);

        if($arResult){
           return $arResult['ID'];
        }
        return false;

    }

   private function setQuery($arSelect = [],$arFilter = [],$arGroupBy = [],$offset = 0,$limit = 0){
       global $DB;
       $favQuery = new \Bitrix\Main\Entity\Query(self::getEntity());

       $favQuery->setSelect($arSelect);
       $favQuery->setFilter($arFilter);

       if($arGroupBy)
           $favQuery->setGroup($arGroupBy);
       if($offset)
           $favQuery->setOffset($offset);
       if($limit)
           $favQuery->setLimit($limit);

       $resFavQuery = $favQuery->exec();
       $arResult = array();
       while ($element = $resFavQuery->fetch()){
           $arResult[] = $element;
       }

       return $arResult;
   }
}