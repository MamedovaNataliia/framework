<?php


namespace Aniart\Main\Services;


use Aniart\Main\Repositories\Catalog\Section;
use Aniart\Main\Repositories\ProductSectionsRepository;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Entity\Query;
use Bitrix\Sale\Compatible\CDBResult;

class CatalogService
{
    /**
     * @var ProductSectionsRepository
     */
    protected $sectionsRepository;

    public function __construct()
    {
        $this->sectionsRepository = app('ProductSectionsRepository');
        //$this->collectionsRepository = app('CollectionsRepository');
    }

    /**
     * Возвращает все разделы каталога: разделы товаров + коллекции товаров
     * @return array
     */
    public function getCatalogSectionsUrl()
    {
        static $sectionsUrl;
        if(!isset($sectionsUrl)){
            $sections = $this->sectionsRepository->getList(array('SORT' => 'ASC'));
            foreach($sections as $section){
                if($section->RIGHT_MARGIN - $section->LEFT_MARGIN == 1) {
                    $sectionsUrl[] = $section->getUrl();
                }
            }
            /*$collections = $this->collectionsRepository->getList(['SORT' => 'ASC'], ['ACTIVE' => 'Y']);
            foreach($collections as $collection){
                $sectionsUrl[] = $collection->getDetailPageUrl();
            }*/
        }
        return $sectionsUrl;
    }

    /**
     * Возвращает все элементы справочника привязанного к свойству инфоблока
     * @param int|string $propertyId ИД или символьный код свойства
     * @return array|false
     */
    public function getDictionaryItemsByProperty($propertyId)
    {
        $property = $this->getPropertyById($propertyId);
        return $this->getDictionaryItemsByPropertyData($property);
    }

    /**
     * Возвращает все элементы справочника привязанного к свойству инфоблока, представленного в виде массива данных
     * этого свойства
     * @param array $property массив данных свойства инфоблока (должны быть поля USER_TYPE и USER_TYPE_SETTINGS)
     * @return array|false
     */
    public function getDictionaryItemsByPropertyData($property)
    {
        $result = false;
        if(
            $property['USER_TYPE'] == 'directory' &&
            $dictionaryTable = $property['USER_TYPE_SETTINGS']['TABLE_NAME']
        ){
            $result = $this->getDictionaryItems($dictionaryTable);
        }
        return $result;
    }

    /**
     * Возвращает все элементы справочника заданного с помощью названия таблицы
     * @param string $dictionaryTable название справочной таблицы
     * @return array|false
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    public function getDictionaryItems($dictionaryTable)
    {
        static $entities;

        if(!isset($entities[$dictionaryTable])){
            $entities[$dictionaryTable] = false;
            $dictionaryEntityData = HighloadBlockTable::getList(
                ['filter' => ['TABLE_NAME' => $dictionaryTable]]
            )->fetch();
            if(!empty($dictionaryEntityData)){
                $dictionaryEntity = HighloadBlockTable::compileEntity($dictionaryEntityData);
                $query = new Query($dictionaryEntity);

                $query->setSelect(['*']);
                $query->setOrder(['UF_SORT' => 'ASC']);

                $queryResult = new \CDBResult($query->exec());
                while($row = $queryResult->Fetch()){
                    $entities[$dictionaryTable][$row['UF_XML_ID']] = $row;
                }
            }
        }

        return $entities[$dictionaryTable];
    }

    public function getPropertyIdByCode($propertyId)
    {
        if(is_numeric($propertyId)){
            return $propertyId;
        }
        elseif($property = $this->getPropertyById($propertyId)){
            return $property['ID'];
        }
        return false;
    }

    public function getPropertyById($propertyId)
    {
        static $properties;
        if(!isset($properties)){
            $properties = [
                'id'   => [],
                'code' => []
            ];
        }

        if(is_numeric($propertyId)){
            $property = $properties['id'][$propertyId];
            if(is_null($property)){
                $properties['id'][$propertyId] = false;
            }
        }
        else{
            $propertyId = strtoupper((string)$propertyId);
            $property   = $properties['code'][$propertyId];
            if(is_null($property)){
                $properties['code'][$propertyId] = false;
            }
        }

        if(is_null($property)){
            $property = \CIBlockProperty::GetByID($propertyId, PRODUCTS_IBLOCK_ID)->Fetch();
            $properties['id'][$property['ID']] = $property;
            $properties['code'][$property['CODE']] = &$properties['id'][$property['ID']];
        }

        return $property;

    }
}