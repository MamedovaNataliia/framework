<?php

namespace Aniart\Main\Repositories;

use Aniart\Main\Exceptions\AniartException;
use Aniart\Main\Interfaces\ErrorableInterface;
use Aniart\Main\Models\AbstractModel;
use Aniart\Main\Traits\ErrorTrait;

class AbstractBitrixRepository implements ErrorableInterface
{

    use ErrorTrait;

    protected $bitrixClass;
    protected $selectedFields = array();
    protected $dbResult = array();
    private $model;

    public function __construct(AbstractModel $model)
    {
        $this->model = $model;
        if(empty($this->bitrixClass) || !class_exists($this->bitrixClass))
        {
            throw new AniartException('Bitrix class "' . $this->bitrixClass . '" not exists');
        }
    }

    public function newInstance(array $fields)
    {
        $className = get_class($this->model);
        return new $className($fields);
    }

    public function getById($id)
    {
        $list = $this->getList(array(), array('ID' => $id));
        if(!empty($list))
        {
            return current($list);
        }
        return false;
    }

    /**
     * @param array $order
     * @param array $filter
     * @param bool $groupBy
     * @param bool $navStartParams
     * @param array $selectedFields
     * @return AbstractModel[]
     */
    public function getList($order = array(), $filter = array(), $groupBy = false, $navStartParams = false, $selectedFields = array())
    {
        $list = array();
        if(empty($order))
        {
            $order = array('SORT' => 'ASC');
        }
        $selectedFields = array_merge($this->selectedFields, $selectedFields);
        $bitrixClass = $this->bitrixClass;
        $rsListItems = $bitrixClass::GetList($order, $filter, $groupBy, $navStartParams, $selectedFields);
        $this->dbResult = $rsListItems;
        while($arItem = $rsListItems->Fetch())
        {
            $list[$arItem['ID']] = $this->newInstance($arItem);
        }

        return $list;
    }
    public function getLastDBResult(){
        return $this->dbResult;
    }

}
