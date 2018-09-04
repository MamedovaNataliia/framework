<?php


namespace Aniart\Main\Services;


use Aniart\Main\Exceptions\CatalogPropertiesServiceException;
use Aniart\Main\Models\Product;
use Aniart\Main\Models\ProductSection;
use Aniart\Main\Properties\Hodgepodge\Models\HPProperty;
use Aniart\Main\Properties\Hodgepodge\Repositories\AbstractItemsRepository;
use Aniart\Main\Properties\Hodgepodge\Repositories\PropertiesRepository;
use Aniart\Main\Properties\MultiTemplate\Parsers\AbstractMTParser;
use Aniart\Main\Properties\MultiTemplate\Parsers\DefaultMTParser;
use Aniart\Main\Properties\MultiTemplate\Repositories\AbstractMTRepository;
use Aniart\Main\Repositories\ProductSectionsRepository;

class CatalogPropertiesService
{
    /**
     * @var AbstractItemsRepository
     */
    protected $hodgepodgeRepository;
    /**
     * @var AbstractMTRepository
     */
    protected $multiTemplateRepository;
    /**
     * @var ProductSectionsRepository
     */
    protected $productsSectionsRepository;

    /**
     * @var PropertiesRepository
     */
    protected $hodgepodgePropertiesRepository;

    protected static $propTemplates = array();


    protected $sections;

    public function __construct()
    {
        $this->multiTemplateRepository = app('MultiTemplateRepository');
        $this->hodgepodgeRepository = app('HodgepodgeRepository');
        $this->productsSectionsRepository = app('ProductSectionsRepository');
        $this->hodgepodgePropertiesRepository = app('ProductHodgepodgePropertiesRepository');
    }

    /**
     * Достает свойства по которым необходимо проводить фильтрацию в указнной секции в формате custom.filter.oop
     * @param $section
     * @param string $lang
     * @return array
     */
    public function getSectionFilteredPropertiesForCustomFilter($section, $lang = 'ru')
    {
        $result = array();
        $properties = $this->getSectionFilteredProperties($section, $lang);
        foreach($properties as $i => $prop){
            $result['IBLOCK_PROPERTIES'][] = $prop->getId();
            $prefix = 'PROPERTY_'.$prop->getId().'_';
            $result = array_merge($result, array(
                $prefix.'TITLE' => $prop->getNameInGroup($section, $lang),
                $prefix.'TYPE'  => 'hodgepodge',
                $prefix.'SORT'  => ($i+1)*100,
                $prefix.'TEMPLATE' => 'default',
                $prefix.'MULTIPLE' => 'Y',
                $prefix.'SHOWCOUNT' => 'Y'
            ));
        }
        return $result;
    }

    public function getProductGroupedProperties(Product $product, $lang = 'ru')
    {
        $props = array();
        $attributes = $product->getAttributes($lang);
        $propsTemplate = array_keys($attributes);
        if(!empty($propsTemplate)){
            /**
             * @var DefaultMTParser $parser
             */
            $parser = app('MultiTemplateParser', array($propsTemplate));
            $group = '';
            foreach($parser->getAll() as $propKey => $prop){
                if($parser->isGroupProperty($prop)){
                    $group = $prop;
                }
                else{
                    $props[$group][] = $prop;
                }
            }
        }
        return $props;
    }

    public function getProductPreviewPropertiesFull(Product $product, $lang = 'ru')
    {
        $result = array();
        $previewPropsNames = $namedProps = array();
        $props = $this->getProductPreviewProperties($product, $lang, $previewPropsNames);
        foreach($props as $hpProp){
            $namedProps[$hpProp->getNameInGroup($product->getSectionId(), $lang)] = $hpProp;
        }
        foreach($previewPropsNames as $propName){
            $result[$propName] = isset($namedProps[$propName]) ? $namedProps[$propName] : null;
        }
        return $result;
    }

    public function getProductPreviewProperties(Product $product, $lang = 'ru', &$previewPropsNames = array())
    {
        $props = array();
        $attributes = $product->getAttributes($lang);
        if(empty($attributes)){
            try{
                $propsTemplate = $this->getProductSectionTemplateOrFail($product->getSectionId(), $lang);
            }
            catch(CatalogPropertiesServiceException $e){
                return $props;
            }
        }
        else{
            $propsTemplate = array_keys($attributes);
        }
        if(!empty($propsTemplate)){
            $previewPropsNames = app('MultiTemplateParser', array($propsTemplate))->getPreviewProperties();
            $props = $this->getProductSectionsBXPropertiesByNames($product->getSectionId(), $previewPropsNames, $lang);
        }
        return $props;
    }

    /**
     * Достает свойства по которым необходимо проводить фильтрацию в указанной секции
     * @param $section
     * @param string $lang
     * @return HPProperty[]
     * @throws CatalogPropertiesServiceException
     */
    public function getSectionFilteredProperties($section, $lang = 'ru')
    {
        return $this->getSectionPropertiesByCallback($section, function(DefaultMTParser $parser){
            return $parser->getFilteredProperties();
        }, $lang);
    }

    public function getSectionCommonProperties($section, $lang = 'ru')
    {
        return $this->getSectionPropertiesByCallback($section, function(DefaultMTParser $parser){
            return $parser->getCommonProperties();
        }, $lang);
    }

    public function getSectionPreviewProperties($section, $lang = 'ru')
    {
        return $this->getSectionPropertiesByCallback($section, function(DefaultMTParser $parser){
            return $parser->getPreviewProperties();
        }, $lang);
    }

    public function getSectionHiddenPropertiesNames($sectionId, $lang = 'ru')
    {
        $parser = false;
        try{
            $parser = $this->getSectionPropertiesParser($sectionId, $lang);
        }
        catch(CatalogPropertiesServiceException $e){
        }

        return $parser ? $parser->getHiddenProperties() : [];
    }

    protected function getSectionPropertiesByCallback($section, callable $callback, $lang = 'ru')
    {
        $props = array();
        try {
            $sectionId = $this->getProductSectionOrFail($section);
            $parser = $this->getSectionPropertiesParser($sectionId, $lang);
            $commonProps = call_user_func($callback, $parser);
            $props = $this->getProductSectionsBXPropertiesByNames($sectionId, $commonProps, $lang);
        }
        catch(CatalogPropertiesServiceException $e){
            if($lang != 'ru'){
                $props = $this->getSectionPropertiesByCallback($section, $callback);
            }
        }
        return $props;
    }

    /**
     * @param $sectionId
     * @param string $lang
     * @return DefaultMTParser
     * @throws CatalogPropertiesServiceException
     */
    protected function getSectionPropertiesParser($sectionId, $lang = 'ru')
    {
        static $sectionsParsers;
        if(is_null($sectionsParsers[$sectionId][$lang])){
            $propsTemplate = $this->getProductSectionTemplateOrFail($sectionId, $lang);
            /**
             * @var AbstractMTParser $parser ;
             */
            $parser = app('MultiTemplateParser', array($propsTemplate));
            $sectionsParsers[$sectionId][$lang] = $parser;
        }
        return $sectionsParsers[$sectionId][$lang];
    }

    public function getProductSectionOrFail($section)
    {
        if(!is_numeric($section)){
            $section = $this->getProductSectionByCode($section);
        }
        $sections = $this->getProductSections();
        if(!$sections[$section]){
            throw new CatalogPropertiesServiceException('Invalid product section "'.$section.'"');
        }
        return $section;
    }

    public function getProductSectionTemplateOrFail($sectionId, $lang)
    {
        $lang = strtoupper($lang);
        if(is_null(self::$propTemplates[$sectionId][$lang])){
            $propsTemplate = $this->multiTemplateRepository->getBySection($sectionId, constant('IB_PROP_MULTI_'.$lang));
            if(!$propsTemplate){
                throw new CatalogPropertiesServiceException('Properties template for section "'.$sectionId.'" not found');
            }
            self::$propTemplates[$sectionId][$lang] = $propsTemplate;
        }
        return self::$propTemplates[$sectionId][$lang];
    }

    public function getProductSectionByCode($code)
    {
        foreach($this->getProductSections() as $sectionId => $section){
            if($section->getCode() == $code){
                return $section;
            }
        }
        return false;
    }

    /**
     * @return ProductSection[]
     */
    public function getProductSections($sort = array())
    {
        if(is_null($this->sections)){
            $this->sections = array();
            if(empty($sort)){
                $sort = array('SORT' => 'ASC');
            }
           $sections = $this->productsSectionsRepository->getList($sort, array('LID' => app()->getSiteId()));
            /**
             * @var ProductSection[] $sections
             */
            foreach($sections as $section){
                $this->sections[$section->getId()] = $section;
            }
        }
        return $this->sections;
    }

    public function getProductSectionsUrl()
    {
        static $sectionsUrl;
        if(!isset($sectionsUrl)){
            $sectionsUrl = array();
            $sections = $this->getProductSections();
            foreach($sections as $section){
                if($section->RIGHT_MARGIN - $section->LEFT_MARGIN == 1) {
                    $sectionsUrl[] = $section->getUrl();
                }
            }
        }
        return $sectionsUrl;
    }

    public function getProductSectionsBXPropertiesByNames($sectionId, array $propNames, $lang = 'ru')
    {
        $props = array();
        if(!empty($propNames)) {
            $hodgepodgeProps = $this->hodgepodgePropertiesRepository->getBySectionId($sectionId);
            foreach ($hodgepodgeProps as $prop) {
                if (in_array($prop->getNameInGroup($sectionId, $lang), $propNames)) {
                    $props[] = $prop;
                }
            }
        }
        return $this->sortPropsByNamesArray($props, $propNames, $sectionId, $lang);
    }

    /**
     * @param HPProperty[] $props
     * @param $propNames
     * @param $sectionId
     * @param string $lang
     * @return HPProperty[]
     */
    private function sortPropsByNamesArray($props, $propNames, $sectionId, $lang = 'ru')
    {
        $result = array();
        foreach($propNames as $name){
            foreach($props as $i => $prop){
                if($name == $prop->getNameInGroup($sectionId, $lang)){
                    $result[] = $prop;
                    unset($props[$i]);
                    break;
                }
            }
        }
        return $result;
    }
}