<?php

namespace Aniart\Main\Repositories;

use Aniart\Main\Models\AbstractHLElementModel;
use Aniart\Main\Models\AbstractModel;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Entity\DataManager;

abstract class AbstractHLBlockElementsRepository
{
    /**
     * @var DataManager
     */
    protected static $entities;
    protected $hlblock_id;

    public function __construct($hlblock_id)
    {
        $this->hlblock_id = $hlblock_id;

        if(!static::$entities[$hlblock_id]){
            $hlblock = HighloadBlockTable::getById($hlblock_id)->fetch();
            static::$entities[$hlblock_id] = HighloadBlockTable::compileEntity($hlblock)->getDataClass();
        }
    }

    abstract public function newInstance(array $fields);

    public function getHLBlockId()
    {
        return $this->hlblock_id;
    }

    public function getEntity()
    {
        return static::$entities[$this->hlblock_id];
    }

    protected function createQueryParams($arOrder, $arFilter, $arGroup, $arNavStartParams, $arSelect)
    {
        $result = array();
        if(is_array($arOrder) && !empty($arOrder)){
            $result['order'] = $arOrder;
        }
        if(is_array($arFilter) && !empty($arFilter)){
            $result['filter'] = $arFilter;
        }
        if(is_array($arGroup) && !empty($arGroup)){
            $result['group'] = $arGroup;
        }
        if(($limit = (int)$arNavStartParams['limit']) > 0){
            $result['limit'] = $limit;
        }
        if(($offset = (int)$arNavStartParams['offset']) > 0){
            $result['offset'] = $offset;
        }
        if(is_array($arSelect) && !empty($arSelect)){
            $result['select'] = $arSelect;
        }

        return $result;
    }

	public function getByXmlId($xmlId)
	{
		if(!$xmlId){
			return false;
		}
		$items = $this->getList([], ['UF_XML_ID' => $xmlId]);
		if(!empty($items)){
			return current($items);
		}
		return false;
	}

    public function getByIds(array $ids)
    {
        $items = array();
        if(!empty($ids)){
            $items = $this->getList([], ['ID' => $ids]);
        }
        return $items;
    }

    public function getList($arOrder = array('ID' => 'ASC'), $arFilter = array(), $arGroup = false, $arNavStartParams = false, $arSelect = array())
    {
        $result = array();
        $queryParams = $this->createQueryParams($arOrder, $arFilter, $arGroup, $arNavStartParams, $arSelect);
        $entity = $this->getEntity();
        $rsData = $entity::getList($queryParams);
        while($arData = $rsData->Fetch()){
            $result[] = $this->createInstance($arData);
        }

        return $result;
    }

	private function createInstance(array $fields)
	{
		$fields = $this->hydrate($fields);
		return $this->newInstance($fields);
	}

	protected function hydrate(array $fields)
	{
		return $fields;
	}

	public function getById($id)
    {
        $id = (int)$id;
        $result = $this->getList(array('ID' => 'ASC'), array('ID' => $id));
        if(!empty($result)){
            return $result[0];
        }
        return false;
    }

	public function add(array $fields = array())
    {
	    return $this->save($this->newInstance($fields));
    }

	public function update($id, array $fields = array())
    {
	    $fields['ID'] = $id;
	    return $this->save($this->newInstance($fields));
    }

	public function save(AbstractHLElementModel $model)
	{
		/**
		 * @var HighloadBlockTable $entity
		 */
		$entity = $this->getEntity();
		$fields = $this->dehydrate($model->getFields());
		if($model->isNew()){
			$result = $entity::add($fields);
			if($result->isSuccess()){
				$model->ID = $result->getId();
			}
			return $result;
		}
		else{
			return $entity::update($model->getId(), $fields);
		}
	}

	protected function dehydrate(array $fields)
	{
		return $fields;
	}
}