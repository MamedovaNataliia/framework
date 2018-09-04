<?php
namespace Aniart\Main\Seo;

use Aniart\Main\Tools\IBlock;

class CustomFilterSEFController
{
    protected $filter = null;
    protected $params = null;
    protected $links = array();
    protected $linksRemove = array();
    protected $rootUrl = '/';

    protected static $additionalSefProps = array();

    const SPEC_ENDING_CHAR = '*';

    public function __construct(\CustomFilter $filter, $lang = 'ru')
    {
        $this->filter = $filter;
        $this->lang   = $lang;
    }

    public function __clone()
    {
        $this->params = clone $this->params;
    }

    public static function setAdditionalFilteredProps(array $props)
    {
        self::$additionalSefProps = $props;
    }

    public static function determineSefUrl($url = '', $urlOrigin = null)
    {
        if(empty($url)){
            global $APPLICATION;
            $urlOrigin = $APPLICATION->GetCurPage(true);
            $url = self::removeSefUrlPart($urlOrigin);
        }
        if(is_null($urlOrigin)){
            $urlOrigin = $url;
        }
        $urlOriginParts = explode('/', $urlOrigin);
        if(count($urlOriginParts) == 1){
    	    $urlFilterPart = [$urlOriginParts[0]];
        }
        elseif(in_array($urlOriginParts[1], ['catalog', 'collections'])){
            $urlFilterPart  = array_slice($urlOriginParts, 3, 1);
            if(current($urlFilterPart) == 'index.php'){
                $urlFilterPart = array_slice($urlOriginParts, 2, 1);
            }
        }
        else{
    	    $urlFilterPart  = array_slice($urlOriginParts, 2, 1);
    	}
        if(!empty($urlFilterPart)){
    	    $urlFilterPart = current($urlFilterPart);
    	    $filteredPropsCodes = self::getFilteredPropsCodes($url);
            foreach($filteredPropsCodes as $propCode){
                if(strpos($urlFilterPart, $propCode.'-') !== false){
                    return true;
                }
    	    }
    	}
        return false;
    }

    public static function getFilteredPropsCodes($url)
    {
        $props     = self::$additionalSefProps;
        $cacher    = new \CPHPCache();
        $cacheId   = md5($url);
        if($cacher->InitCache(36000, $cacheId)){
            $vars = $cacher->GetVars();
            if(is_array($vars['properties'])){
                $props = array_merge($props, $vars['properties']);
            }
        }
        else{
            $props = array_merge($props, self::obtainFilteredPropsCodes(0));
        }
        return $props;
    }

    public static function bindFilteredPropsToUrl($url, $sectionId)
    {
        $sectionId = (int)$sectionId;
        if($sectionId < 0){
            return array();
        }
        $cacher    = new \CPHPCache();
        $cacheId   = md5($url);
        if(!$cacher->InitCache(36000, $cacheId)){
            $filteredProps = self::obtainFilteredPropsCodes($sectionId);
            if($cacher->StartDataCache()){
                $cacher->EndDataCache(array(
                    'properties' => $filteredProps
                ));
            }
        }
    }

    protected static function obtainFilteredPropsCodes($sectionId)
    {
        $result    = array();
        $sectionId = (int)$sectionId;
        if($sectionId === 0){
            //костыляем
            $props = app('IBlockTools')->GetListPropertiesElement(PRODUCTS_IBLOCK_ID);
        }
        else{
            $props = app('FilterProperty')->GetSectionBXProperties($sectionId);
        }
        foreach($props as $prop){
            $code = strtolower($prop['CODE']);
            if(!empty($code)){
                $result[] = $code;
            }
        }
        return $result;
    }

    public static function removeSefUrlPart($url)
    {
        $sectionsUrl = app('CatalogService')->getCatalogSectionsUrl();
        foreach($sectionsUrl as $sectionUrl){
            if(strpos($url, $sectionUrl) !== false && $sectionUrl !== "/"){
                if(substr($url, -9) === 'index.php'){
                    $sectionUrl.='index.php';
                    return $sectionUrl;
                }
            }
        }
        return $url;
    }

    //public static function getPropertiesCodesBySectionId()

    private static function substrBetween($string, $from, $to)
    {
        $string = " ".$string;
        $ini = strpos($string,$from);
        if ($ini == 0){
            return "";
        }
        $ini += strlen($from);
        $len = strpos($string,$to,$ini) - $ini;
        return substr($string,$ini,$len);
    }


    /**
     * Парсит строку с параметрами фильтрации к виду:
     * $params[property_code] = array(property_value, property_value)
     *
     * @param string $sefString - строка из url с параметрами фильтра
     * @return array()
     */
    protected function parseSefParams($sefString)
    {
        $sefString = str_replace(array('+', ';'), array('_', '-'), $sefString);
        $params = array();
        $regProps = implode('-|', $this->getFilterPropertiesCodes());
        $regPattern = '/'.$regProps.'-/';
        //ищем коды свойств в строке фильтра
        preg_match_all($regPattern, $sefString, $matches);
        $matches = array_map(function($match){
            return rtrim($match, '-');
        }, $matches[0]);
        if(!empty($matches)){
            $propCodes = $matches;
            $propCodes[] = self::SPEC_ENDING_CHAR;
            $sefString.= self::SPEC_ENDING_CHAR;
            $i = 1;
            //ищем значения свойтсв
            while($toWord = $propCodes[$i]){
                $fromWord = $propCodes[$i-1];
                $propValues = self::substrBetween($sefString, $fromWord, $toWord);
                $sefString = str_replace($fromWord.$propValues, '', $sefString);
                $propValues = trim($propValues, '-');
                $params[$fromWord] = explode('_', $propValues);
                $i++;
            }
        }
        return $params;
    }

    public function getFilterPropertiesCodes()
    {
        static $props;
        if(!isset($props)){
            $props = array();
            foreach($this->filter->getProperties() as $propId => $prop){
                $props[] =  strtolower($prop->getData('CODE'));
            }
        }
        return $props;
    }

    protected function initParamsFromFilterSelectedValues()
    {
        $this->params = new CustomFilterSEFParamsCollection();
        $propertiesSelectedValues = $this->filter->getSelectedValues();
        if($propertiesSelectedValues->SelectedValuesCount() > 0)
        {
            foreach($propertiesSelectedValues->Get() as $propId => $selectedValues)
            {
                $property = $this->filter->getProperty($propId);
                $paramsCollection = new CustomFilterSEFParamsCollection(
                    array(),
                    $propId,
                    strtolower($property->getData('CODE'))
                );
                if(!empty($selectedValues['VALUES'])){
                    $values = array();
                    foreach($selectedValues['VALUES'] as $valId => $valData){
                        if($sefParam = $this->createPropertySefParam($property, $valId)){
                            if(!$values[$sefParam->getCode()] || $propId == 'PRICE'){
                                $paramsCollection->add($sefParam);
                            }
                            $values[$sefParam->getCode()] = true;
                        }
                    }
                }
                $this->params->add($paramsCollection);
            }
        }
    }

    protected function getPropertyValueParams($property, $propValueId)
    {
        $result = false;
        if(is_numeric($property)){
            $property = $this->filter->getProperty($property);
        }
        if($property instanceof \CustomFilterProperty){
            $value = $property->getValue($propValueId);
            //new \dBug($value);
            if(!$value){
                $selValues = $this->filter->GetSelectedValues();
                if($selValues->SelectedValuesCount() > 0){
                    $value = $selValues->Get($property->GetID(), $propValueId);
                }
            }
            
            if(!empty($value)){
                $result = array(
                    'ID' => $propValueId,
                    'NAME' => $value['NAME'],
                    'CODE' => isset($value['UF_CODE']) ? (string)$value['UF_CODE'] : (string)$value['CODE'],
                    'PROPERTY_TYPE' => $property->getData('PROPERTY_TYPE'),
                    'USER_TYPE' => $property->getData('USER_TYPE')
                );
                if(empty($result['CODE']) && $result['CODE'] !== 0){
                    $result['CODE'] = $value['ID'];
                }
            }
        }
        return $result;
    }

    protected function obtainSefParamCode($property, $propValueId)
    {
        $code = false;
        $valueParams = $this->getPropertyValueParams($property, $propValueId);
        if($valueParams){
            switch($valueParams['PROPERTY_TYPE']){
                case 'S':
                case 'N':
                    if($valueParams['USER_TYPE'] == 'price'){
                        $code = $valueParams['NAME'];
                    }
                    else{
                        $code = (string)($valueParams['CODE'] ? $valueParams['CODE'] : $valueParams['NAME']);
                    }
                    break;
                case 'L':
                    $code = $valueParams['ID'];
                    break;
                case 'E':
                case 'G':
                case 'U':
                    $code = $valueParams['CODE'] ? $valueParams['CODE'] : $valueParams['ID'];
                    break;
                default:
                    $code = $valueParams['NAME'];
                    break;
            }
        }
        return $code;
    }

    protected function createPropertySefParam($property, $propValueId)
    {
        $sefParam = false;
        $propSefCode = $this->obtainSefParamCode($property, $propValueId);
        if($propSefCode){
            $sefParam = new CustomFilterSefParam($propValueId, $propSefCode);
        }
        return $sefParam;
    }

    public function setRootUrl($url)
    {
        $this->rootUrl = $url;
    }

    public function getRootUrl()
    {
        return $this->rootUrl;
    }

    public function getPropertyLink($propId, $valueId, $as_key = true)
    {
        $key = '#custom_filter_'.$propId.'_'.$valueId.'#';
        if($as_key){
            return $key;
        }
        else{
            return $this->links[$key];
        }
    }

    public function getPropertyRemoveLink($propId, $valueId = null, $as_key = true)
    {
        $link = '#custom_filter_remove_'.$propId;
        if(!is_null($valueId)){
            $link.='_'.$valueId;
        }
        $link.='#';
        return $link;
    }

    public function generatePropertiesLinks()
    {
        $this->initParamsFromFilterSelectedValues();
        if($this->filter->propertiesCount() <= 0){
            return;
        }
        $url = $this->getRootUrl();
        foreach($this->filter->getProperties() as $propId => $property){
            $propType  = $property->getData('PROPERTY_TYPE');
            $isSefProp = (
                ($propType != 'N' &&  $propType != 'S') ||
                ($propType == 'S' && $property->getData('USER_TYPE') == 'directory')
            );
            if( $property->valuesCount() <= 0 || !$isSefProp){
                continue;
            }
            foreach($property->getValues() as $valueId => $value){
                $link = $this->getSefUrlWithParam($propId, $valueId, $url);
                $this->links[$this->getPropertyLink($propId, $valueId)] = $link;
            }
        }
        if($this->params->count() > 0){
            $this->params->rewind();
            foreach($this->params->toArray() as $propParams){
                $propId = $propParams->getId();
                if($propId == 'PRICE'){
                    $clone = clone $this;
                    $this->linksRemove[$this->getPropertyRemoveLink($propId)] = $clone->getSefUrlWithoutParam($propId, '', $url);
                    unset($clone);
                    continue;
                }
                if($propParams->count() > 0){
                    $propParams->rewind();
                    foreach($propParams->toArray() as $sefValue){
                        $valueId = $sefValue->getId();
                        $this->linksRemove[$this->getPropertyRemoveLink($propId, $valueId)] = $this->getSefUrlWithoutParam($propId, $valueId, $url);
                    }
                }
            }
        }
    }

    public function insertSEFLinks($html)
    {
        $links = array_merge($this->links, $this->linksRemove);
        return str_replace(array_keys($links), array_values($links), $html);
    }

    protected function addParam($propId, $propValueId)
    {
        $property = $this->filter->getProperty($propId);
        $propParams = $this->params->getById($propId);
        if(!$propParams){
            $code = strtolower($property->getData('CODE'));
            $propParams = $this->params->add(new CustomFilterSEFParamsCollection(
                array(),
                $propId,
                $code
            ));
        }
        $propParams->add($this->createPropertySefParam($property, $propValueId));
    }

    protected function removeParam($propId, $propValueId = '')
    {
        if($this->params->count() > 0){
            if(empty($propValueId)){
                $this->params->remove($propId);
            }
            else{
                $this->params->rewind();
                do{
                    $propParams = $this->params->current();
                    if($propParams->getId() == $propId){
                        $propParams->remove($propValueId);
                        if($propParams->count() == 0){
                            $this->params->remove($propId);
                        }
                        break;
                    }
                }while($this->params->next());
            }
        }
    }

    public function getSefUrl($url = '/')
    {
        $result = '/'.trim($url, '/');
        $result.= '/'.$this->params->toUrl().'/';
        $result = preg_replace('/(\/){2,}/', '/', $result);
        return $result;
    }

    public function getSefUrlWithParam($propId, $propValueId, $url = '/')
    {
        $result = '';
        $property = $this->filter->getProperty($propId);
        if($property){
            $propValue = $property->getValue($propValueId);
            if($propValue){
                $this->addParam($propId, $propValueId);
                $result = $this->getSefUrl($url);
                $this->removeParam($propId, $propValueId);
            }
        }
        return $result;
    }

    public function getSefUrlWithoutParam($propId, $propValueId = "", $url = '/')
    {
        $this->removeParam($propId, $propValueId);
        $result = $this->getSefUrl($url);
        if($propValueId){
            $this->addParam($propId, $propValueId);
        }
        return $result;
    }

    public function parseSefUrlToGlobalRequest($urlOrigin = '')
    {
        if(empty($urlOrigin)){
            global $APPLICATION;
            $urlOrigin = $APPLICATION->GetCurPage(true);
            $url = self::removeSefUrlPart($urlOrigin);
        }
        $filterName = $this->filter->getName();
        $sefParams = array();
        $urlParts  = explode('/', $urlOrigin);
        foreach($urlParts as $i => $pos){
    	    if($i < 2) continue;
            if(self::determineSefUrl($url, $pos)){
                $sefParams = $this->parseSefParams($pos);
                break;
            }
        }
        if(!empty($sefParams)){
            foreach($sefParams as $propCode => $propValues){
                /**
                 * @var \CustomFilterProperty $property
                 */
                $property = $this->getPropertyByCode($propCode);
                if(!$property){
                    $propCode = strtoupper($propCode);
                    $property = $this->getPropertyByCode($propCode);
                }
                if($property){
                    $propId	= $property->getId();
                    switch($property->getData('PROPERTY_TYPE')){
                        case 'S':
                            if($property->getData('USER_TYPE') == 'directory'){
                                $userSettings = $property->getData('USER_TYPE_SETTINGS');
                                //случай когда в ссылке - символьный код
                                $hlValues = app('CatalogService')->getDictionaryItems($userSettings['TABLE_NAME']);
                                foreach($hlValues as $hlValue){
                                    if(in_array($hlValue['UF_CODE'], $propValues)){
                                        $_REQUEST[$filterName][$propId][] = (string)$hlValue['UF_XML_ID'];
                                    }
                                }
                                //случай когда в ссылке - xml_id
                                //$_REQUEST[$filterName][$propId] = $propValues;
                            }
                        break;
                        case 'N':
                            if($property->getData('USER_TYPE') == 'price'){
                                if(isset($propValues[0])){
                                    $_REQUEST[$filterName][$propId]['f'] = $propValues[0];
                                }
                                if(isset($propValues[1])){
                                    $_REQUEST[$filterName][$propId]['t'] = $propValues[1];
                                }
                            }
                            else{
                                $_REQUEST[$filterName][$propId] = $propValues;
                            }
                            break;
                        case 'L':
                            $_REQUEST[$filterName][$propId] = $propValues;
                            break;
                        case 'E':
                            $filter = array(
                                'IBLOCK_ID' => $property->getData('LINK_IBLOCK_ID'),
                                'CODE' => $propValues
                            );
                            $elements = app('IBlockTools')->GetListElements($filter, array('ID', 'IBLOCK_ID'), array('id' => 'asc'), false, true, true, false);
                            if(!empty($elements)){
                                foreach($elements as $el){
                                    if(!in_array($el['ID'], $_REQUEST[$filterName][$propId])){
                                        $_REQUEST[$filterName][$propId][] = $el['ID'];
                                    }
                                }
                            }
                            break;
                        case 'U':
                            $filter = array(
                                'IBLOCK_ID' => $property->getData('LINK_IBLOCK_ID'),
                                'CODE' => $propValues
                            );
                            $elements = app('IBlockTools')->GetListElements($filter, array('ID', 'IBLOCK_ID'), array('id' => 'asc'), false, true, true, false);
                            if(!empty($elements)){
                                foreach($elements as $el){
                                    if(!in_array($el['ID'], $_REQUEST[$filterName][$propId])){
                                        //p($propId);
                                        $_REQUEST[$filterName][$propId][] = $el['ID'];
                                    }
                                }
                            }
                        case 'G':
                            break;
                    }
                }
            }
        }
    }

    protected function getPropertyByCode($propCode)
    {
        $result = false;
        if(!empty($propCode)){
            foreach($this->filter->getProperties() as $property){
                if($property->getData('CODE') == $propCode){
                    $result = $property;
                    break;
                }
            }
        }
        return $result;
    }
}

class CustomFilterSEFParamsCollection extends \Bitrix\Main\Type\Dictionary
{
    protected $propId;
    protected $propCode;

    public function __construct(array $values = array(), $propId = 0, $propCode = '')
    {
        $this->propId = $propId;
        $this->propCode = $propCode;
        parent::__construct($values);
    }

    public function add($value, $reSort = false)
    {
        $this->offsetSet(null, $value);
        if($reSort){
            $this->reSort();
        }
        return end($this->values);
    }

    public function remove($valueId, $reSort = false)
    {
        foreach($this->values as $key => $param){
            if($param->getId() == $valueId){
                $this->offsetUnset($key);
                break;
            }
        }
        if($reSort){
            $this->reSort();
        }
    }

    public function hasCode($code)
    {
        foreach($this->values as $value){
            if($value->getCode() == $code){
                return true;
            }
        }
        return false;
    }

    public function getById($id)
    {
        $result = false;
        if($this->count() > 0){
            $this->rewind();
            do{
                if($this->current()->getId() == $id){
                    $result = $this->current();
                    break;
                }
            }while($this->next());
        }

        return $result;
    }

    public function reSort()
    {
        if($this->count() > 1){
            usort($this->values, function($a, $b){
                $c1 = $a->getCode();
                $c2 = $b->getCode();
                if(is_numeric($c1)){
                    if ($a == $b) {
                        return 0;
                    }
                    return ($a < $b) ? -1 : 1;
                }
                else{
                    return strcmp($c1, $c2);
                }
            });
        }
    }

    public function getId()
    {
        return $this->propId;
    }

    public function getCode()
    {
        return $this->propCode;
    }

    public function toUrl()
    {

        $url = '';
        $this->reSort();
        $count = $this->count();
        foreach($this->values as $index => $paramsCollection){
            if($paramsCollection instanceof self){
                if($paramsCollection->count() > 0){
                    //для некоторых случаев ссылка должна быть
                    //catalog/section_code/propValue/
                    if(
                        $this->count() == 1 &&
                        $paramsCollection->count() == 1 &&
                        in_array($paramsCollection->getCode(), array('some_property_code'))
                    ){
                        $url.= $paramsCollection->toUrl();
                    }
                    else{
                        $url.= $paramsCollection->getCode().'-'.$paramsCollection->toUrl().'-';
                    }
                }
            }
            else{
/*                if(!$paramsCollection){
                    pre_dump_clr($this, $paramsCollection, false);die;
                }*/
                $url.=$paramsCollection->getCode();
                if($index < ($count - 1)){
                    $url.='_';
                }
            }
        }

        return trim($url, '-');
    }
}

class CustomFilterSEFParam
{
    protected $id;
    protected $code;

    public function __construct($id, $code)
    {
        $this->id = $id;
        $this->code = $code;
    }

    public function getId(){
        return $this->id;
    }

    public function getCode(){
        return $this->code;
    }
}