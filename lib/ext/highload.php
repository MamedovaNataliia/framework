<?
namespace Aniart\Main\Ext;

class Highload
{
	function GetDataFromHL($HL_BLOCK_ID, $arFilter, $arSelect=array('*'), $entity_data_class=false, $entity_table_name=false) 
	{
		global $DB;
		
		$result = array();
	
		if(empty($arFilter) || !$HL_BLOCK_ID)
			return $result;
		
		
		if(!$entity_data_class || !$entity_table_name)
		{
			$hlblock  = \Bitrix\Highloadblock\HighloadBlockTable::getById( $HL_BLOCK_ID )->fetch();
			$entity  = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity( $hlblock );
			$entity_data_class = $entity->getDataClass();
			$entity_table_name = $hlblock['TABLE_NAME'];
		}
		
		$sTableID = 'tbl_'.$entity_table_name;
		$rsData = $entity_data_class::getList(array(
				"select" => $arSelect, //выбираем все поля
				"filter" => $arFilter,
				"order" => array()
		));
		$rsData = new \CDBResult($rsData, $sTableID);
		while($arRes = $rsData->Fetch())
		{
			$result[] = $arRes;
		}
	
		return $result;
	}
	
	function Add($HL_BLOCK_ID, $arFields) 
	{
		$arResult = array('SUCCESS' => false);

		if(empty($arFields) || empty($HL_BLOCK_ID))
			return $arResult;

		$hlblock  = \Bitrix\Highloadblock\HighloadBlockTable::getById( $HL_BLOCK_ID )->fetch();
		$entity  = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity( $hlblock );
		$entity_data_class = $entity->getDataClass();
		$entity_table_name = $hlblock['TABLE_NAME'];
		
		if ($element = $entity_data_class::add($arFields))
		{
			$arResult['ELEMENT_ID'] = $element->getId(); 
			$arResult['SUCCESS'] = true;
		}

		return $arResult;
	}
}

