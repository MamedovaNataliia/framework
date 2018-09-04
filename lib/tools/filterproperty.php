<?
/**
 * Класс обрабатывающий свойства инфоблоков и формирующий список свойст для компонента 
 * custom.fiter.oop
 * 
 * @author Alexander Kuprin
 *
 */

namespace Aniart\Main\Tools;

use Aniart\Main\CustomFilter\Models\FilterPropMetaData;

class FilterProperty
{
	var $arProperties;
	var $catalogIblockID;
	var $offersIblockID;
    var $arSort;
    /**
     * @var FilterPropMetaData[]
     */
    var $propsMetaData;
	
	function  __construct($catalogIblockID, $offersIblockID = false) {

	    $arIblockID = [];
		if (!empty($catalogIblockID)) {
			$this->catalogIblockID = $catalogIblockID; 
			$arIblockID[] = $catalogIblockID;
		}

		if (!empty($offersIblockID)) {
			$this->offersIblockID = $offersIblockID;
			$arIblockID[] = $offersIblockID;
		}
		
		$dbList = \CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y"));
		while ($dbItem = $dbList->GetNext()) {
			if (in_array($dbItem["IBLOCK_ID"], $arIblockID)) {
				$this->arProperties[$dbItem["ID"]] = $dbItem;
			}
		}
	}
	
	function  GetPropertyList($sectionID, $arTemplates = [], $excludedProps = []) {
		$result = array();
		
		$arPropertiesInSection = array();

		if (!empty($this->catalogIblockID)) {
			//Если передан массив с id разделами, то это страница поиска
			//формируем массив свойст для фильтра из разных разделов
            if(!is_array($sectionID)){
                $sectionID = array($sectionID);
            }
            foreach($sectionID as $id) {
                $arPropertiesInOneSection = $this->GetSectionProperties($id);

                foreach($arPropertiesInOneSection as $arPropsId => $arProps) {
                    if(array_key_exists($arPropsId, $arPropertiesInSection)) {
                        if($arProps["SMART_FILTER"] == "Y") {
                            $arPropertiesInSection[$arPropsId]["SMART_FILTER"] = "Y";
                        }
                    } else {
                        $arPropertiesInSection[$arPropsId] = $arProps;
                    }
                }
            }
		}

		if (!empty($this->offersIblockID)) {
            $arPropertiesInSection = array_merge($arPropertiesInSection, \CIBlockSectionPropertyLink::GetArray($this->offersIblockID, $sectionID));
        }
		foreach ($arPropertiesInSection as $propertyShort) {
            $propertyID = $propertyShort["PROPERTY_ID"];
            if(in_array($propertyID, $excludedProps)){
                continue;
            }
			if (
                isset($this->arProperties[$propertyID]) &&
                $propertyShort["SMART_FILTER"] == "Y" &&
                $this->arProperties[$propertyID]["CODE"] != "DUMMY"
            ) {
                $propertyDetail = $this->arProperties[$propertyID];
 				if ($propertyDetail["IBLOCK_ID"] == $this->catalogIblockID) {
					$suffix = "";
					$result["IBLOCK_PROPERTIES"][] = $propertyID;
				}
                else {
					$suffix = "OFFER_";
					$result["OFFERS_PROPERTIES"][] = $propertyID;
				}
				$result["PROPERTY_" . $suffix . $propertyID . "_TITLE"] = $propertyDetail["NAME"];
				$result["PROPERTY_" . $suffix . $propertyID . "_CODE"] = $propertyDetail["CODE"];
				$result["PROPERTY_" . $suffix . $propertyID . "_TYPE"] = ".default";
				$result["PROPERTY_" . $suffix . $propertyID . "_EXPANDED"] = $propertyShort["DISPLAY_EXPANDED"];
				$result["PROPERTY_" . $suffix . $propertyID . "_SORT"] = (isset($this->arSort[$propertyID]) ? $this->arSort[$propertyID] : $propertyDetail["SORT"]);
				$result["PROPERTY_" . $suffix . $propertyID . "_TEMPLATE"] = (isset($arTemplates[$propertyID]) ? $arTemplates[$propertyID] : ['main', 'select']);
				$result["PROPERTY_" . $suffix . $propertyID . "_MULTIPLE"] = ($propertyDetail["PROPERTY_TYPE"] == "N" ? "N" : "Y");
				$result["PROPERTY_" . $suffix . $propertyID . "_SHOWCOUNT"] = ($propertyDetail["PROPERTY_TYPE"] == "N" ? "N" : "Y");

				if (!empty($propertyDetail["LINK_IBLOCK_ID"])) {
                    $result["PROPERTY_" . $suffix . $propertyID . "_LINKIBLOCKID"] = $propertyDetail["LINK_IBLOCK_ID"];
                }
			}
		}
		return $result;
	}

    public function GetSectionBXProperties($sectionId)
    {
        $result = array();
        $ids = $this->GetSectionPropertiesId($sectionId);
        if(empty($ids)){
            return $result;
        }
        $rsProperties = \CIBlockProperty::GetList(array(), array(
            'IBLOCK_ID' => $this->catalogIblockID,
            '@ID' => $ids
        ));
        while($arProperty = $rsProperties->Fetch()){
            $result[$arProperty['ID']] = $arProperty;
        }

        return $result;
    }

    public function GetSectionPropertiesId($sectionId)
    {
        $ids = array_map(function($filterProp){
            return $filterProp['PROPERTY_ID'];
        }, $this->GetSectionProperties($sectionId));

        return $ids;
    }

    public function GetSectionProperties($sectionId)
    {
        return \CIBlockSectionPropertyLink::GetArray($this->catalogIblockID, $sectionId);
    }

    public function setPropertiesSort(array $sort = [])
    {
        $this->arSort = $sort;
    }

    public function setPropertiesMetaData($filterPropsMetaData)
    {
        $this->propsMetaData = $filterPropsMetaData;
    }

    public function getCustomFilterComponentPropertyParams($propertyData, $smartFilterPropertyData)
    {

    }
}
