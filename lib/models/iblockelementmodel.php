<?php

namespace Aniart\Main\Models;

use Aniart\Main\Interfaces\SeoParamsInterface;
use Aniart\Main\Repositories\HLBlockRepository;
use Bitrix\Iblock\InheritedProperty\ElementValues;
use Aniart\Main\Models\IblockSectionModel as Section;

class IblockElementModel extends AbstractModel implements SeoParamsInterface
{

    protected $sections;
    protected $seoParams;
    protected $PROPERTIES;

    public function getIBlockId()
    {
        return $this->fields['IBLOCK_ID'];
    }



    public function getDateActiveFrom()
    {
        return $this->fields['DATE_ACTIVE_FROM'];
    }

    public function getDateCreate()
    {
        return $this->fields['DATE_CREATE'];
    }

    public function getCreatedTimestamp()
    {
        return \MakeTimeStamp($this->getDateCreate());
    }

    public function isActive()
    {
        return $this->fields['ACTIVE'] !== 'N';
    }

    public function getCode()
    {
        return $this->fields['CODE'];
    }
    public function getID()
    {
        return $this->fields['ID'];
    }

    public function getXmlId()
    {
        return $this->fields['XML_ID'];
    }

    public function getName()
    {
        return $this->fields['NAME'];
    }

    public function obtainSections()
    {
        $rsSections = \CIBlockElement::GetElementGroups($this->getId(), true);
        while($arSection = $rsSections->GetNext())
        {
            $this->sections[$arSection['ID']] = $this->createSection($arSection);
        }
        return $this;
    }

    protected function createSection(array $fields = [])
    {
        return new Section($fields);
    }

    /**
     * Элемент может принадлежать несколим секциям
     */
    public function getSections()
    {
        if(is_null($this->sections))
        {
            $this->obtainSections();
        }
        return $this->sections;
    }

    public function getSectionsId()
    {
        return array_keys($this->getSections());
    }

    public function getSectionId()
    {
        return $this->fields['IBLOCK_SECTION_ID'];
    }

    public function hasPropertyValue($propertyName, $value)
    {
        $propertyValues = $this->getPropertyValue($propertyName);
        if(is_array($propertyValues))
        {
            return in_array($value, $propertyValues);
        }
        else
        {
            return $propertyValues == $value;
        }
    }

    /**
     * @param $propName
     * @param bool $index
     * @return mixed
     */
    public function getPropertyValue($propName, $index = false)
    {
        return $this->getPropertyParam($propName, $index, "VALUE");
    }

    public function getPropertyValueName($propName, $index = false)
    {
        return $this->getPropertyParam($propName, $index, "VALUE_NAME");
    }

    public function getPropertyValueData($propName, $index = false)
    {
        return $this->getPropertyParam($propName, $index, "VALUE_DATA");
    }

    public function getPropertyDescription($propName, $index = false)
    {
        return $this->getPropertyParam($propName, $index, "DESCRIPTION");
    }

    public function getPropertyName($propName, $index = false)
    {
        return $this->getPropertyParam($propName, $index, "NAME");
    }

    protected function getPropertyParam($propName, $index, $paramName = "VALUE")
    {
        $result = false;
        if(!empty($propName))
        {
            if(array_key_exists('PROPERTY_'.$propName.'_'.$paramName, $this->fields))
            {
                $propValue = $this->fields['PROPERTY_'.$propName.'_'.$paramName];
            }
            else
            {
                $this->obtainProps();
                $propValue = $this->PROPERTIES[$propName][$paramName];
            }

            if($propValue && $index !== false)
            {
                $propValue = $propValue[$index];
            }

            $result = $propValue;
        }
        return $result;
    }

    public function getAllProperties()
    {
        $this->obtainProps();
        return array_map(function($a)
        {
            return $a["VALUE"];
        }, $this->PROPERTIES);
    }

    /**
     * Пытается вытащить все данные связанные со свойством из массива данных модели.
     * Например:
     * PROPERTY_<PROP_NAME>_VALUE
     * PROPERTY_<PROP_NAME>_NAME
     * PROPERTY_<PROP_NAME>_<INNER_PROPERTY>_VALUE
     * @param string $propName
     * @return array
     */
    public function extractPropertyData($propName)
    {
        $result = array();
        $propName = 'PROPERTY_'.$propName;
        foreach($this->toArray() as $fieldName => $fieldValue)
        {
            $tmpName = $propName.'_';
            if(strpos($fieldName, $tmpName) === 0)
            {
                $propFieldName = str_replace($tmpName, '', $fieldName);
                $result[$propFieldName] = $fieldValue;
            }
            $tmpName = '~'.$tmpName;
            if(strpos($fieldName, $tmpName) === 0)
            {
                $propFieldName = str_replace($tmpName, '', $fieldName);
                $result['~'.$propFieldName] = $fieldValue;
            }
        }

        return $result;
    }

    public function getPropertyAsModel($propName)
    {
        $prop = array();
        if(!empty($propName))
        {
            $prop = $this->getPropertyValue($propName);
            if(!is_array($prop) && !is_object($prop))
            {
                $prop = $this->extractPropertyData($propName);
                if(isset($prop['VALUE']))
                {
                    $prop['ID'] = $prop['VALUE'];
                }
            }
        }
        return new self($prop);
    }

    public function getPreviewPictureId()
    {
        return $this->fields['PREVIEW_PICTURE'];
    }

    public function getDetailPictureId()
    {
        return $this->fields['DETAIL_PICTURE'];
    }

    public function getDetailPageUrl()
    {
        return $this->fields['DETAIL_PAGE_URL'];
    }

    public function getDetailText()
    {
        return $this->fields['DETAIL_TEXT'];
    }

    public function getPreviewText()
    {
        return $this->fields['PREVIEW_TEXT'];
    }

    public function getSeoPageTitle()
    {
        return $this->getSeoParamValue('ELEMENT_META_TITLE');
    }

    public function getSeoMetaTitle()
    {
        return $this->getName();
    }

    public function getSeoKeywords()
    {
        return $this->getSeoParamValue('ELEMENT_META_KEYWORDS');
    }

    public function getSeoDescription()
    {
        return $this->getSeoParamValue('ELEMENT_META_DESCRIPTION');
    }

    protected function getSeoParamValue($paramName)
    {
        $this->getSeoParams();
        return $this->seoParams[$paramName];
    }

    public function getSeoParams()
    {
        if(is_null($this->seoParams))
        {
            $this->obtainSeoParams();
        }
        return $this->seoParams;
    }

    public function obtainSeoParams()
    {
        $seoParamsValues = array();
        if(($iblockId = $this->getIblockId()) && ($id = $this->getId()))
        {
            $seoParams = new ElementValues($iblockId, $id);
            if($seoParams)
            {
                $seoParamsValues = $seoParams->getValues();
            }
        }
        $this->seoParams = $seoParamsValues;

        return $this;
    }

    private function obtainProps()
    {
        if(empty($this->PROPERTIES))
        {
            $dbl = \CIBlockElement::GetByID($this->getId())->GetNextElement();
            $this->PROPERTIES = $dbl->GetProperties();
            $this->obtainLinkedPropsData();
        }
    }

    private function obtainLinkedPropsData()
    {
        foreach($this->PROPERTIES as $code => $prop)
        {
            if(empty($prop["VALUE"]))
                continue;

            if($prop["USER_TYPE"] == "directory")
            {
                $repository = new HLBlockRepository(HLBlockRepository::resolveTableName($prop["USER_TYPE_SETTINGS"]["TABLE_NAME"]));

                $values = [];
                $fullValueData = [];

                foreach((array) $prop["VALUE"] as $propValue)
                {
                    $value = $repository->getByXmlId($propValue);

                    if($value)
                    {
                        $fields = $value->toArray();
                        $strValue = $fields["UF_NAME"];
                        $strValue = $strValue ?: $fields["UF_NAME_".strtoupper(i18n()->lang())];
                        $values[$fields["XML_ID"]] = $strValue;
                        $fullValueData[$propValue] = $fields;
                    }
                }

                $prop["VALUE_NAME"] = is_array($prop["VALUE"]) ? $values : array_shift($values);
                $prop["VALUE_DATA"] = is_array($prop["VALUE"]) ? $fullValueData : array_shift($fullValueData);
            }

            if($prop["PROPERTY_TYPE"] == "E")
            {
                $values = [];
                $fullValueData = [];

                $iblockID = $prop["LINK_IBLOCK_ID"];
                $filter = ["IBLOCK_ID" => $iblockID, "ID" => $prop["VALUE"]];
                $select = ["ID", "IBLOCK_ID", "DETAIL_PAGE_URL", "NAME", "PROPERTY_NAME_".strtoupper(i18n()->lang())];

                $dbl = \CIBlockElement::GetList([], $filter, false, false, $select);
                while($res = $dbl->GetNext())
                {
                    $strValue = $res["NAME"];
                    $strValue = $res["PROPERTY_NAME_".strtoupper(i18n()->lang())] ? $res["PROPERTY_NAME_".strtoupper(i18n()->lang())] : $strValue;
                    $values[$res["ID"]] = $strValue;
                    $fullValueData[$res["ID"]] = $res;
                }

                $prop["VALUE_NAME"] = is_array($prop["VALUE"]) ? $values : array_shift($values);
                $prop["VALUE_DATA"] = is_array($prop["VALUE"]) ? $fullValueData : array_shift($fullValueData);
            }

            $this->PROPERTIES[$code] = $prop;
        }
    }

    public function setBreadcrumbs()
    {
        foreach($this->getNavChain() as $key => $arSection)
        {
            $this->getAplication()->AddChainItem($arSection["NAME"], $arSection["SECTION_PAGE_URL"]);
        }
        $this->getAplication()->AddChainItem($this->getName(), "");
    }

    public function getNavChain()
    {
        $arResult = [];
        $arSelect = [
            "ID",
        ];
        $dbList = \CIBlockSection::GetNavChain($this->getIblockId(), $this->getSectionId(), $arSelect);
        while($arItem = $dbList->GetNext())
        {
            $arId[] = $arItem["ID"];
        }

        if($arId)
        {
            $dbSectionList = \CIBlockSection::GetList([], ["IBLOCK_ID" => $this->getIblockId(), "ID" => $arId], false, ["ID", "NAME", "UF_NAME_".strtoupper(i18n()->lang()), "SECTION_PAGE_URL"]);
            while($arSection = $dbSectionList->GetNext())
            {
                $arResult[$arSection["ID"]]["ID"] = $arSection["ID"];
                $name = i18nSelect($arSection["NAME"], $arSection["UF_NAME_".strtoupper(i18n()->lang())]);
                $arResult[$arSection["ID"]]["NAME"] = (strlen($name) > 0) ? $name : $arSection["NAME"];
                $arResult[$arSection["ID"]]["SECTION_PAGE_URL"] = i18n()->getLangDir($arSection["SECTION_PAGE_URL"]);
            }
        }
        return $arResult;
    }

    public function getFilePath($id)
    {
        if(empty($id))
        {
            return false;
        }
        return \CFile::GetPath($id);
    }

    public function getPageTitle()
    {
        return $this->getSeoParamValue('ELEMENT_PAGE_TITLE');
    }

    public function getMetaTitle()
    {
        return $this->getSeoParamValue('ELEMENT_META_TITLE');
    }

    public function getKeywords()
    {
        return $this->getSeoParamValue('ELEMENT_META_KEYWORDS');
    }

    public function getDescription()
    {
        return $this->getSeoParamValue('ELEMENT_META_DESCRIPTION');
    }

}
