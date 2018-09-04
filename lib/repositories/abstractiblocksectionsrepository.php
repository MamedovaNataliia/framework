<?php
namespace Aniart\Main\Repositories;


use Aniart\Main\Interfaces\ErrorableInterface;
use Aniart\Main\Models\ProductSection;
use Aniart\Main\Models\IblockSectionModel;
use Aniart\Main\Traits\ErrorTrait;

abstract class AbstractIblockSectionsRepository implements ErrorableInterface
{
    use ErrorTrait;

    protected $iblockId;
    protected $selectedFields = array();

    public function __construct($iblockId)
    {
        $this->iblockId = $iblockId;
    }

    abstract public function newInstance(array $fields = array());

	/**
	 * @param IblockSectionModel|int $section
	 * @param bool $withCurrentSection
	 * @return \Aniart\Main\Models\ProductSection[]
	 */
	public function getActiveParentsList($section, $withCurrentSection = false)
	{
		$result = [];
		if(is_numeric($section)){
			$section = $this->getById($section);
		}
		if($section instanceof IblockSectionModel){
			$result = $this->getList(['left_margin' => 'asc'], [
				'ACTIVE' => 'Y',
				'<LEFT_BORDER' => $section->LEFT_MARGIN,
				'>RIGHT_BORDER' => $section->RIGHT_MARGIN
			]);
			if($withCurrentSection){
				$result[] = $section;
			}
		}

		return $result;
	}

	public function getById($id)
	{
		$list = $this->getList(array(), array('ID' => $id));
		if(!empty($list)){
			return $list[0];
		}
		return false;
	}

    /**
     * @param array $arOrder
     * @param array $arFilter
     * @param bool|false $bIncCnt
     * @param array $arSelect
     * @param bool|false $arNavStartParams
     * @return ProductSection[]
     */
    public function getList($arOrder = array("SORT"=>"ASC"), $arFilter = array(), $bIncCnt = false,
                            $arSelect = array(), $arNavStartParams = false)
    {
        $result = array();
        $arFilter['IBLOCK_ID'] = $this->iblockId;
        $arSelect = array_merge($this->selectedFields, $arSelect);
        $rsSections = \CIBlockSection::GetList($arOrder, $arFilter, $bIncCnt, $arSelect, $arNavStartParams);
        while($arSection = $rsSections->GetNext()){
            $result[] = $this->newInstance($arSection);
        }

        return $result;
    }

    public function getByCode($code)
    {
        $list = $this->getList(array(), array('CODE' => $code));
        if(!empty($list)){ //считаем, что код такой же уникальный как и id
            return $list[0];
        }
        return false;
    }

    public function getByElementId($elementId, $arSelect = array())
    {
        $result  = array();
        $elementId = (int)$elementId;
        if($elementId > 0){
            $rsSections = \CIBlockElement::GetElementGroups($elementId, false, $arSelect);
            while($arSection = $rsSections->GetNext()){
                $result[] = $this->newInstance($arSection);
            }
        }
        return $result;
    }
}