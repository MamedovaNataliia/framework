<?php


namespace Aniart\Main\Multilang;

/**
 * Class MultiLangSeoParamsTrait
 * @package Aniart\Main\Multilang
 */
trait MultiLangSeoParamsTrait
{
    protected $seoLang;

    public function setSeoLang($lang)
    {
        $this->seoLang = $lang;
        return $this;
    }

    public function getSeoLang()
    {
        return $this->seoLang ?: $this->i18n->lang();
    }

    public function getMetaTitle()
    {
        return $this->extractLangSeoParamValue(parent::getMetaTitle());
    }

    public function getPageTitle()
    {
        return $this->extractLangSeoParamValue(parent::getPageTitle());
    }

    public function getKeywords()
    {
        return $this->extractLangSeoParamValue(parent::getKeywords());
    }

    public function getDescription()
    {
        return $this->extractLangSeoParamValue(parent::getDescription());
    }

    public function getImageAlt($isPreview = false)
    {
        return $this->extractLangSeoParamValue(parent::getImageAlt($isPreview));
    }

    public function getImageTitle($isPreview = false)
    {
        return $this->extractLangSeoParamValue(parent::getImageTitle($isPreview));
    }

    protected function extractLangSeoParamValue($value)
    {
        $lang = $this->getSeoLang();
        $regExp = '/\['.$lang.'\](.*)\[\/'.$lang.'\]/i';
        if(!empty($value)){
            if(preg_match($regExp, $value, $matches)){
                $value = $matches[1];
            }
            else{
                foreach(array_keys($this->i18n->getLangs()->all()) as $langCode){
                    if(strpos($value, '['.$langCode.']') !== false){
                        $value = null;
                        break;
                    }
                }
            }
        }
        return $value;
    }
}