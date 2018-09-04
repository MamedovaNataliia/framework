<?
namespace Aniart\Main\Ext;
/*
 *  Эти обработчки нужны только если файл с классом подключается вручную где-то в init.php при подключении через автолоадер 
 *  события будут регистрироваться только в момент первого вызова какой-либо функции класса
 *  
*/

/*
AddEventHandler("iblock", "OnAfterIBlockElementUpdate", array("\Aniart\Main\Ext\CIBlockExt", "OnAfterIBlockElementUpdateHandler"));
AddEventHandler("iblock", "OnAfterIBlockElementDelete", array("\Aniart\Main\Ext\CIBlockExt", "OnAfterIBlockElementDeleteHandler"));
AddEventHandler("iblock", "OnAfterIBlockSectionUpdate", array("\Aniart\Main\Ext\CIBlockExt", "OnAfterIBlockSectionUpdateHandler"));
AddEventHandler("iblock", "OnAfterIBlockSectionDelete", array("\Aniart\Main\Ext\CIBlockExt", "OnAfterIBlockSectionDeleteHandler"));
*/

use CPHPCache, CIBlockElement, CIBlockProperty, CIBlockSection, CUserTypeEntity;

class CIBlockExt 
{
	const TAG_CACHE = "iblock_ext";
	const TTL_CACHE = 21600;

	function OnAfterIBlockElementUpdateHandler(&$arFields) { self::ClearCacheIBlock($arFields["IBLOCK_ID"]); }
	function OnAfterIBlockElementDeleteHandler($arFields) {	self::ClearCacheIBlock($arFields["IBLOCK_ID"]);	}
	function OnAfterIBlockSectionUpdateHandler(&$arFields) { self::ClearCacheIBlock($arFields["IBLOCK_ID"]); }
	function OnAfterIBlockSectionDeleteHandler($arFields) {	self::ClearCacheIBlock($arFields["IBLOCK_ID"]);	}
	
	private function GetTagElement($elementID) {	return "iblock_ext:e:".$elementID; }
	private function GetTagSection($sectionID) {	return "iblock_ext:s:".$sectionID; }
	private function GetTagIBlock($iblockID) {	return "iblock_ext:i:".$iblockID; }

	private function ClearCacheIBlock($iblockID)
	{
		if (!empty($iblockID))
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag(self::GetTagIBlock($iblockID));
		}
	}
	
	private function ClearCacheElement($elementID)
	{
		if (!empty($elementID))
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag(self::GetTagElement($elementID));
		} 
	}

	private function ClearCacheSection($sectionID)
	{
		if (!empty($sectionID))
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag(self::GetTagSection($sectionID));
		}
	}
	
	/**
	 * Возвращаем список разделов инфоблока. По умолчанию возвращает 25 разделов. Если $limit = false, то возвращаем
	 * весь список. Список проиндексирован по ID разделов
	 *
	 * @param array $arFilter
	 * @param array $arSelect
	 * @param array $arSort
	 * @param number/boolean $limit
	 * @param boolean $useCache
	 * @param boolean $useManagedCache
	 * @param boolean $useSEO
	 * @return array
	 */
	function GetListSections($arFilter, $arSelect = array(), $arSort = array("sort" => "asc", "name" => "asc"), $limit = false, $useCache = true, $useManagedCache = true, $useSEO = false)
	{
		global $CACHE_MANAGER;
	
		$arListSections = array();
	
		if (empty($arFilter)) return;
	
		if (!is_array($arFilter)) $arFilter = array("IBLOCK_ID" => $arFilter);

		if (is_array($arSelect))
			$arSelect = array_merge(array("IBLOCK_ID", "ID", "NAME", "CODE"), $arSelect);
		
		if (!is_array($arSort)) $arSort = array("sort" => "asc", "name" => "asc");
		
		if (isset($arFilter['ID']) && empty($arFilter['ID'])) return array();
		 
		if (\Bitrix\Main\Loader::includeModule("iblock"))
		{
			if (!$limit)
				$arNavParams = $limit;
			else
				$arNavParams = array("nPageSize" => $limit);
				
			if (!$useCache)
			{
				$dbList = CIBlockSection::GetList($arSort, $arFilter, false, $arSelect, $arNavParams);
	
				while ($dbItem = $dbList->GetNext())
				{
					self::RemoveTildaFields($dbItem);
					
					if ($useSEO)
					{
						$ipropValues = new \Bitrix\Iblock\InheritedProperty\SectionValues($dbItem["IBLOCK_ID"], $dbItem["ID"]);
						$ipropSEO = $ipropValues->getValues();
						$dbItem = array_merge($dbItem, $ipropSEO);
						unset($ipropValues);
					}
						
					$arListSections[$dbItem["ID"]] = $dbItem;
				}
			}
			else
			{
				$obCache = new CPHPCache();
				if ($obCache->InitCache(self::TTL_CACHE, serialize(array(__FUNCTION__, $arFilter, $arSelect, $arNavParams, $arSort, $limit)), self::TAG_CACHE))
				{
					$arListSections = $obCache->GetVars();
				}
				elseif ($obCache->StartDataCache())
				{
	
					$dbList = CIBlockSection::GetList($arSort, $arFilter, false, $arSelect, $arNavParams);

					$iblockId = false;
					
					while ($dbItem = $dbList->GetNext())
					{
						self::RemoveTildaFields($dbItem);
						$arListSections[$dbItem["ID"]] = $dbItem;
						
						if (!$iblockId)
							$iblockId = $dbItem['IBLOCK_ID'];
					}


					// Меняем логику сохранения кеша. Для списков создаем один тег, описывающий ID инфоблока
					if(defined("BX_COMP_MANAGED_CACHE") && $useManagedCache && !empty($arListSections))
					{
						global $CACHE_MANAGER;
						$CACHE_MANAGER->StartTagCache(self::TAG_CACHE);
						$CACHE_MANAGER->RegisterTag(self::GetTagIBlock($iblockId));
						$CACHE_MANAGER->EndTagCache();
					}
						
					if (!empty($arListSections))
						$obCache->EndDataCache($arListSections);
					else
						$obCache->AbortDataCache();
					
				}
			}
		}
	
		return $arListSections;
	}
	
	/**
	 * Возвращаем список элементов инфоблока. По умолчанию возвращает 25 элементов. Если $limit = false, то возвращаем 
	 * весь список. Список элементов проиндексирован по ID элементов
	 * 
	 * @param array $arFilter
	 * @param array $arSelect
	 * @param array $arSort
	 * @param number/boolean $limit
	 * @param boolean $useCache
	 * @param boolean $useManagedCache
	 * @param boolean $useSEO
	 * @return array
	 */
	function GetListElements($arFilter, $arSelect = array(), $arSort = array("sort" => "asc", "name" => "asc"), $limit = false, $useCache = true, $useManagedCache = true, $useSEO = false, $arLinkFields = array(), $getProperties = true)
	{
		$arListElements = array();
	
		if (empty($arFilter)) return;
	
		if (!is_array($arFilter)) $arFilter = array("IBLOCK_ID" => $arFilter);
	
		if (is_array($arSelect)){
			
			if($getProperties)
				$arSelect = array_merge(array("IBLOCK_ID", "ID", "NAME", "DETAIL_PAGE_URL", "CODE", "DETAIL_PICTURE"), self::GetSelectProperties($arFilter['IBLOCK_ID'], $arLinkFields), $arSelect);
			else
				$arSelect = array_merge(array("IBLOCK_ID", "ID", "NAME", "DETAIL_PAGE_URL", "CODE", "DETAIL_PICTURE"), $arSelect);
		}
		if (!is_array($arSort)) $arSort = array("sort" => "asc", "name" => "asc");

		if (isset($arFilter['ID']) && empty($arFilter['ID'])) return array();
		
		if (\Bitrix\Main\Loader::includeModule("iblock"))
		{
			if (!$limit)
				$arNavParams = $limit;
			else
				$arNavParams = array("nPageSize" => $limit);
				
			if (!$useCache)
			{
				$dbList = CIBlockElement::GetList($arSort, $arFilter, false, $arNavParams, $arSelect);
	
				while ($dbItem = $dbList->GetNext())
				{
					self::RemoveTildaFields($dbItem);
	
					if ($useSEO)
					{
						$ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues($dbItem["IBLOCK_ID"], $dbItem["ID"]);
						$ipropSEO = $ipropValues->getValues();
						$dbItem = array_merge($dbItem, $ipropSEO);
						unset($ipropValues);
					}
	
					$arListElements[$dbItem["ID"]] = $dbItem;
				}
			}
			else
			{
				$obCache = new CPHPCache();
				if ($obCache->InitCache(self::TTL_CACHE, serialize(array(__FUNCTION__, $arFilter, $arSelect, $arSort, $arNavParams, $limit)), self::TAG_CACHE))
				{
					$arListElements = $obCache->GetVars();
				}
				elseif ($obCache->StartDataCache())
				{
	
					$dbList = CIBlockElement::GetList($arSort, $arFilter, false, $arNavParams, $arSelect);
	
					$iblockId = false;
					
					while ($dbItem = $dbList->GetNext())
					{
						self::RemoveTildaFields($dbItem);
	
						if (!$iblockId)
							$iblockId = $dbItem['IBLOCK_ID'];
						
						$arListElements[$dbItem["ID"]] = $dbItem;
					}

					// Меняем логику сохранения кеша. Для списков создаем один тег, описывающий ID инфоблока
					if(defined("BX_COMP_MANAGED_CACHE") && $useManagedCache && !empty($arListElements))
					{
						global $CACHE_MANAGER;
						$CACHE_MANAGER->StartTagCache(self::TAG_CACHE);
						$CACHE_MANAGER->RegisterTag(self::GetTagIBlock($iblockId));
						$CACHE_MANAGER->EndTagCache();
					}
						
					if (!empty($arListElements))
						$obCache->EndDataCache($arListElements);
					else
						$obCache->AbortDataCache();
				}
			}
		}
	
		return $arListElements;
	}
	
	/**
	 * Проверяет существует ли элемент
	 *
	 * @param array $arFilter
	 * @return array
	 */
	function ElementExist($arFilter = array())
	{
		$result = false;
	
		if (!empty($arFilter))
		{
			$dbResult = CIBlockElement::GetList(array(), $arFilter);
			$result = ($dbItem = $dbResult->GetNext());
		}
	
		return $result;
	}
	
	/**
	 * Возвращает информацию элемента (поля + свойства) 
	 *
	 * @param integer $iblockID
	 * @param boolean $useCache
	 * @param boolean $useManagedCache
	 * @param boolean $useSEO
	 * @return array
	 */
	function GetElementInfo($arFilter = array(), $useCache = true, $useManagedCache = true, $useSEO = false, $arLinkFields = array("NAME", "CODE"))
	{
		if (empty($arFilter)) return;
		
		if (!is_array($arFilter)) $arFilter = array("ID" => $arFilter);
		 
		if (\Bitrix\Main\Loader::includeModule("iblock"))
		{
			if (!$useCache)
			{
				$dbList = CIBlockElement::GetList(array(), $arFilter);
				
				if ($dbItem = $dbList->GetNext()) {
					self::RemoveTildaFields($dbItem);
					
					if ($useSEO)
					{
						$ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues($dbItem["IBLOCK_ID"], $dbItem["ID"]);
						$ipropSEO = $ipropValues->getValues();
						$dbItem = array_merge($dbItem, $ipropSEO);
						unset($ipropValues);
					}

					$arProperties = self::GetListPropertiesValueElement($dbItem["IBLOCK_ID"], $dbItem["ID"], $arLinkFields);
				
					$arElementInfo = array_merge($arElementInfo, $arProperties);
						
					$arElementInfo = $dbItem;
				}
			}
			else 
			{
				$obCache = new CPHPCache();
				if ($obCache->InitCache(self::TTL_CACHE, serialize(array(__FUNCTION__, $arFilter)), self::TAG_CACHE))
				{
					$arElementInfo = $obCache->GetVars();
				}
				elseif ($obCache->StartDataCache())
				{
					$dbList = CIBlockElement::GetList(array(), $arFilter);
				
					if ($dbItem = $dbList->GetNext()) {
						self::RemoveTildaFields($dbItem);
						
						if ($useSEO)
						{
							$ipropValues = new \Bitrix\Iblock\InheritedProperty\ElementValues($dbItem["IBLOCK_ID"], $dbItem["ID"]);
							$ipropSEO = $ipropValues->getValues();
							$dbItem = array_merge($dbItem, $ipropSEO);
							unset($ipropValues);
						}
						
						$arElementInfo = $dbItem;
						

						$arProperties = self::GetListPropertiesValueElement($dbItem["IBLOCK_ID"], $dbItem["ID"], $arLinkFields);
						
						$arElementInfo = array_merge($arElementInfo, $arProperties);
						
						// Меняем логику сохранения кеша. Для списков создаем один тег, описывающий ID инфоблока
						if(defined("BX_COMP_MANAGED_CACHE") && $useManagedCache)
						{
							global $CACHE_MANAGER;
							$CACHE_MANAGER->StartTagCache(self::TAG_CACHE);
							$CACHE_MANAGER->RegisterTag(self::GetTagIBlock($dbItem['IBLOCK_ID']));
							$CACHE_MANAGER->EndTagCache();
						}
						
					}
						
					if (!empty($arElementInfo))
						$obCache->EndDataCache($arElementInfo);
					else
						$obCache->AbortDataCache();
						
				}
			}
		}
		
		return $arElementInfo;
	}

	/**
	 * Возвращает поля раздела
	 *
	 * @param integer $sectionID
	 * @return array
	 */
	function GetSectionInfo($arFilter = array(), $useCache = true, $useManagedCache = true, $useSEO = false)
	{
		if (empty($arFilter)) return;
		
		if (!is_array($arFilter)) $arFilter = array("ID" => $arFilter);
		 
		if (\Bitrix\Main\Loader::includeModule("iblock"))
		{
			if (!$useCache)
			{
				$dbList = CIBlockSection::GetList(array(), $arFilter);
				
				if ($dbItem = $dbList->GetNext()) {
					self::RemoveTildaFields($dbItem);
					$arSectiontInfo = $dbItem;
				}
			}
			else 
			{
				$obCache = new CPHPCache();
				if ($obCache->InitCache(self::TTL_CACHE, serialize(array(__FUNCTION__, $arFilter)), self::TAG_CACHE))
				{
					$arSectionInfo = $obCache->GetVars();
				}
				elseif ($obCache->StartDataCache())
				{
					$dbList = CIBlockSection::GetList(array(), $arFilter);
				
					if ($dbItem = $dbList->GetNext()) {
						self::RemoveTildaFields($dbItem);
						
						if ($useSEO)
						{
							$ipropValues = new \Bitrix\Iblock\InheritedProperty\SectionValues($dbItem["IBLOCK_ID"], $dbItem["ID"]);
							$ipropSEO = $ipropValues->getValues();
							$dbItem = array_merge($dbItem, $ipropSEO);
							unset($ipropValues);
						}
						
						$arSectionInfo = $dbItem;
						
						if(defined("BX_COMP_MANAGED_CACHE") && $useManagedCache)
						{
							global $CACHE_MANAGER;
							$CACHE_MANAGER->StartTagCache(self::TAG_CACHE);
							$CACHE_MANAGER->RegisterTag(self::GetTagIBlock($dbItem["IBLOCK_ID"]));
							$CACHE_MANAGER->EndTagCache();
						}
					}
				
					if (!empty($arSectionInfo))
						$obCache->EndDataCache($arSectionInfo);
					else
						$obCache->AbortDataCache();
					
				}
			}
		}
		
		return $arSectionInfo;
	}
		
	/**
	 * Возвращает информацию об инфоблоке(ах). Если результат выборки из БД один, то возвращается
	 * массив полей. В противном случае возвразается список массивов
	 *
	 * @param integer $iblockID
	 * @return array
	 */
	function GetIBlockInfo($arFilter = array(), $useManagedCache = true)
	{
		if (empty($arFilter)) return;
		
		if (!is_array($arFilter)) $arFilter= array("ID" => $arFilter);
		
		if (\Bitrix\Main\Loader::includeModule("iblock"))
		{
			global $APPLICATION, $CACHE_MANAGER;
				
			$obCache = new CPHPCache();
			if ($obCache->InitCache(self::TTL_CACHE, serialize(array(__FUNCTION__, $arFilter)), self::TAG_CACHE))
			{
				$arIBlockInfo = $obCache->GetVars();
			}
			elseif ($obCache->StartDataCache())
			{
				$dbList = \CIBlock::GetList(array("SORT" => "ASC", "NAME" => "ASC"), $arFilter);
				
				while ($dbItem = $dbList->GetNext()) 
				{
					self::RemoveTildaFields($dbItem);
		
					$arIBlockInfo[] = $dbItem;
					
					if(defined("BX_COMP_MANAGED_CACHE") && $useManagedCache)
					{
						$CACHE_MANAGER->StartTagCache(self::TAG_CACHE);
						$CACHE_MANAGER->RegisterTag(self::GetTagIBlock($dbItem["ID"]));
						$CACHE_MANAGER->EndTagCache();
					}
				}

				if (count($arIBlockInfo) == 1)
					$arIBlockInfo = array_shift($arIBlockInfo);

				if (!empty($arIBlockInfo))
					$obCache->EndDataCache($arIBlockInfo);
				else
					$obCache->AbortDataCache();
				
			}
		}
		
		return $arIBlockInfo;
	}
	
	/**
	 * Формируем список свойств элементов инфоблока
	 * 
	 * @param integer $iblockID
	 * @return array
	 */
	function GetListPropertiesElement($iblockID, $useManagedCache = true)
	{
		if (empty($iblockID)) return;
		
		if (\Bitrix\Main\Loader::includeModule("iblock"))
		{
			global $APPLICATION, $CACHE_MANAGER;
		
			$arFilter = array("IBLOCK_ID" => $iblockID);
			
			$obCache = new CPHPCache();
			if ($obCache->InitCache(self::TTL_CACHE, serialize(array(__FUNCTION__, $arFilter)), self::TAG_CACHE))
			{
				$arProperties = $obCache->GetVars();
			}
			elseif ($obCache->StartDataCache())
			{
				$dbList = CIBlockProperty::GetList(array("sort"=>"asc", "name"=>"asc"), $arFilter);
				while ($dbItem = $dbList->GetNext()) {
					self::RemoveTildaFields($dbItem);
					
					$arProperties[$dbItem["ID"]] = $dbItem;
					
					if(defined("BX_COMP_MANAGED_CACHE") && $useManagedCache)
					{
						$CACHE_MANAGER->StartTagCache(self::TAG_CACHE);
						$CACHE_MANAGER->RegisterTag(self::GetTagIBlock($iblockID));
						$CACHE_MANAGER->EndTagCache();
					}
				}
				
				if (!empty($arProperties))
					$obCache->EndDataCache($arProperties);
				else
					$obCache->AbortDataCache();
				
			}
		}
		
		return $arProperties;
	}

	/**
	 * Формируем список свойств для массива $arSelect для метода CIBlockElement::GetList
	 *
	 * @param integer $iblockID
	 * @return multitype:|multitype:string
	 */
	function GetSelectProperties($iblockID, $arFieldsElement = array())
	{
		$arResult = array();
	
		if (empty($iblockID)) return $arResult;
	
		$arFieldsElement = array_merge(array('CODE', 'NAME', 'XML_ID'), $arFieldsElement);
		
		$listProperties = self::GetListPropertiesElement($iblockID);
	
		foreach ($listProperties as $arProperty)
		{
			if($arProperty['PROPERTY_TYPE']=='E' && !empty($arFieldsElement) )
				foreach ($arFieldsElement as $field)
					$arResult[] = 'PROPERTY_'.$arProperty['CODE'].'.'.strtoupper($field);
			
			$arResult[] = 'PROPERTY_'.$arProperty['CODE'];
		}

		return $arResult;
	}
	
	/**
	 * Формируем список значений свойств элемента
	 *
	 * @param integer $iblockID
	 * @return array
	 */
	function GetListPropertiesValueElement($iblockID, $elementID, $arLinkFields = array("NAME", "CODE"))
	{
		$arProperties = self::GetListPropertiesElement($iblockID);
		
		$arFilter = array(
				"IBLOCK_ID" => $iblockID,
				"ACTIVE" => "Y",
				"ID" => $elementID
		);
		
		$arSelect = array("ID", "NAME", "IBLOCK_ID");
		foreach ($arProperties as $arProperty) {
			
			if ($arProperty["PROPERTY_TYPE"] == "E")
			{
				foreach ($arLinkFields as $field) $arSelect[] = "PROPERTY_".$arProperty["CODE"].".".$field;
			}
			$arSelect[] = "PROPERTY_".$arProperty["CODE"];
		}

		$dbList = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);
		
		$arValues = array();
		if ($dbItem = $dbList->Fetch()) {
			foreach ($dbItem as $key => $value)
			{
				if (strpos($key, "ROPERTY") == 1) $arValues[$key] = $value;
			} 
		}
		
		return $arValues;
	}

	/**
	 * Формируем список свойств секций инфоблока
	 *
	 * @param integer $iblockID
	 * @return array
	 */
	function GetListPropertiesSection($iblockID)
	{
		$arProperties = array();
		$arFilter = array("ENTITY_ID" => "IBLOCK_".$iblockID."_SECTION");
		$dbList = CUserTypeEntity::GetList(array("sort"=>"asc", "name"=>"asc"), $arFilter);
		while ($dbItem = $dbList->GetNext()) $arProperties[] = $dbItem;
		return $arProperties;
	}
	/**
	
	 * Формируем список значений свойст секции
	 *
	 * @param integer $iblockID
	 * @return array
	 */
	function GetListPropertiesValueSection($iblockID, $sectionID)
	{
		$arProperties = self::GetListPropertiesSection($iblockID);
	
		$arFilter = array(
				"IBLOCK_ID" => $iblockID,
				"ACTIVE" => "Y",
				"ID" => $sectionID
		);
	
		$arSelect = array("ID", "NAME", "IBLOCK_ID");
		foreach ($arProperties as $arProperty) $arSelect[] = $arProperty["FIELD_NAME"];
	
		$dbList = CIBlockSection::GetList(array(), $arFilter, false, $arSelect);
	
		$arValues = array();
		if ($dbItem = $dbList->Fetch()) {
			foreach ($dbItem as $key => $value)
			{
				if (strpos($key, "F_") == 1) $arValues[$key] = $value;
			}
		}
	
		return $arValues;
	}

	/**
	 * Функция формирует хлебные крошки для элемента
	 *
	 * @param integer $elementID
	 */
	function SetBreadcrumbElement($elementID, $nameElement = false, $useManagedCache = true)
	{
		if (\Bitrix\Main\Loader::includeModule("iblock"))
		{
			global $APPLICATION;
			
			$obCache = new CPHPCache();
			if ($obCache->InitCache(self::TTL_CACHE, serialize(array(__FUNCTION__, $elementID)), self::TAG_CACHE))
			{
				$arBreadcrumbs = $obCache->GetVars();
			}
			elseif ($obCache->StartDataCache())
			{
				$dbList = CIBlockElement::GetByID($elementID);
					
				if ($dbItem = $dbList->GetNext())
				{
					if (!$nameElement) $nameElement = $dbItem["NAME"];
						
					$dbList = CIBlockSection::GetNavChain($dbItem["$IBLOCK_ID"], $dbItem["IBLOCK_SECTION_ID"]);
						
					while ($dbItem = $dbList->GetNext())
					{
								
						$arBreadcrumbs[] = array(
								"NAME" => $dbItem["NAME"],
								"LINK" => $dbItem["SECTION_PAGE_URL"]
						);
						
						$firstItem = false;
					}
					
					$arBreadcrumbs[] = array(
							"NAME" => $nameElement,
							"LINK" => ""
					);
				}
			
				if(defined("BX_COMP_MANAGED_CACHE") && $useManagedCache)
				{
					global $CACHE_MANAGER;
					$CACHE_MANAGER->StartTagCache(self::TAG_CACHE);
					$CACHE_MANAGER->RegisterTag(self::GetTagIBlock($dbItem["IBLOCK_ID"]));
					$CACHE_MANAGER->EndTagCache();
				}
				
				if (!empty($arBreadcrumbs))
					$obCache->EndDataCache($arBreadcrumbs);
				else
					$obCache->AbortDataCache();
				
			}
			
			foreach ($arBreadcrumbs as $arItemBreadcrumbs)
				$APPLICATION->AddChainItem($arItemBreadcrumbs["NAME"],$arItemBreadcrumbs["LINK"]);
		}
	}

	/**
	 * Функция формирует хлебные крошки для раздела
	 *
	 * @param integer $elementID
	 */
	function SetBreadcrumbSection($sectionID, $nameSection = false, $useManagedCache = true)
	{
		$arBreadcrumbs = array();
		
		if (\Bitrix\Main\Loader::includeModule("iblock"))
		{
			global $APPLICATION;

			$obCache = new CPHPCache();
			if ($obCache->InitCache(self::TTL_CACHE, serialize(array(__FUNCTION__, $sectionID)), self::TAG_CACHE))
			{
				$arBreadcrumbs = $obCache->GetVars();
			}
			elseif ($obCache->StartDataCache())
			{
				$sectionInfo = self::GetSectionInfo($sectionID);
							
				if (!empty($sectionInfo))
				{
					if (!$nameSection) $nameSection = $sectionInfo["NAME"];
				
					$dbList = CIBlockSection::GetNavChain($sectionInfo["$IBLOCK_ID"], $sectionInfo["IBLOCK_SECTION_ID"]);
						
					while ($dbItem = $dbList->GetNext())
					{
						$arBreadcrumbs[] = array(
								"NAME" => $dbItem["NAME"],
								"LINK" => $dbItem["SECTION_PAGE_URL"]
						);
					}
				
					$arBreadcrumbs[] = array(
								"NAME" => $nameSection,
								"LINK" => ""
						);
					}
					
				if(defined("BX_COMP_MANAGED_CACHE") && $useManagedCache)
				{
					global $CACHE_MANAGER;
					$CACHE_MANAGER->StartTagCache(self::TAG_CACHE);
					$CACHE_MANAGER->RegisterTag(self::GetTagIBlock($sectionInfo['IBLOCK_ID']));
					$CACHE_MANAGER->EndTagCache();
				}
				
				if (!empty($arBreadcrumbs))
					$obCache->EndDataCache($arBreadcrumbs);
				else
					$obCache->AbortDataCache();
				
			}
				
			foreach ($arBreadcrumbs as $arItemBreadcrumbs)
				$APPLICATION->AddChainItem($arItemBreadcrumbs["NAME"],$arItemBreadcrumbs["LINK"]);
		}
	}
	
	/**
	 * Извлекаем из инфоблока-справочника список вида [ID] => array(NAME, PREVIEW_PICTURE) 
	 *
	 * @param integer $iblockID
	 * @return array
	 */
	function GetDictionary($iblockID, $arSelect = array())
	{
		$result = array();
	
		$arSort = array("sort" => "asc", "name" => "asc");
		$arFilter = array("IBLOCK_ID" => $iblockID, "ACTIVE" => "Y"); 
		$arSelect = array_merge(array("IBLOCK_ID", "ID", "SORT", "CODE", "NAME", "PREVIEW_PICTURE"), $arSelect);
		
		$dbResult = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);
		while($dbItem = $dbResult->GetNext())
		{
			$dbItem["PREVIEW_PICTURE"] = (empty($dbItem["PREVIEW_PICTURE"])?"":CFile::GetPath($dbItem["PREVIEW_PICTURE"]));
			$result[$dbItem["ID"]] = $dbItem;
		} 
			
		return $result;
	}

	
	/**
	 * Метод используется для обработки значений свойств инфоблоков, передаваемых в событиях
	 * 
	 * @param array $arValues
	 * @return any
	 */
	function GetFirstParamValue($arValues)
	{
		$arValue = array_shift($arValues);
		return $arValue["VALUE"];
	}
	
	/**
	 * Удаляем поля начинающиеся с ~ и другой хлам
	 * 
	 * @param array $dbItem
	 */
	private function RemoveTildaFields(&$dbItem)
	{
		unset($dbItem["SEARCHABLE_CONTENT"]);
		
		foreach ($dbItem as $key => $value)
		if (preg_match("/^~/i", $key)) unset($dbItem[$key]);
	}

	function GetListElementsByGroup($arSort = array("sort" => "asc", "name" => "asc"), $arFilter, $arGroup = false, $useCache = true)
	{
		global $CACHE_MANAGER;
	
		$arListElements = array();
	
		if (empty($arFilter)) return;
	
		if (!is_array($arFilter)) $arFilter = array("IBLOCK_ID" => $arFilter);
	
		if (!is_array($arSort)) $arSort = array("sort" => "asc", "name" => "asc");
	
		if (isset($arFilter['ID']) && empty($arFilter['ID'])) return array();
	
		if (\Bitrix\Main\Loader::includeModule("iblock"))
		{
			if (!$useCache)
			{
				$dbList = CIBlockElement::GetList($arSort, $arFilter, $arGroup);
	
				while ($dbItem = $dbList->GetNext(true, false))
				{
					$arListElements[] = $dbItem;
				}
			}
			else
			{
				$obCache = new CPHPCache();
				if ($obCache->InitCache(self::TTL_CACHE, serialize(array(__FUNCTION__, func_get_args())), self::TAG_CACHE))
				{
					$arListElements = $obCache->GetVars();
				}
				elseif ($obCache->StartDataCache())
				{
					$dbList = CIBlockElement::GetList($arSort, $arFilter, $arGroup);
	
					$CACHE_MANAGER->StartTagCache(self::TAG_CACHE);
	
					while ($dbItem = $dbList->GetNext(true, false))
					{
						$arListElements[] = $dbItem;
					}
	
					$CACHE_MANAGER->EndTagCache();
	
					if (!empty($arListElements))
						$obCache->EndDataCache($arListElements);
					else
						$obCache->AbortDataCache();
				}
			}
		}
	
		return $arListElements;
	}

} 
