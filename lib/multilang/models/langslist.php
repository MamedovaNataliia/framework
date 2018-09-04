<?php


namespace Aniart\Main\Multilang\Models;

class LangsList
{
    protected $langs = array();
    protected $defaultLangCode;
    protected $currentLangCode;
    /**
     * @param Lang[] $langs
     */
    public function __construct(array $langs, $defaultLangCode, $currentLangCode = null)
    {
        foreach($langs as $i => $lang){
            $this->langs[$lang->code] = $lang;
            if($i == 0 || $lang->code == $defaultLangCode){
                $this->defaultLangCode = $defaultLangCode;
            }
            if($lang->code == $currentLangCode){
                $this->currentLangCode = $currentLangCode;
            }
        }
    }

    /**
     * @return Lang[]
     */
    public function all()
    {
        return $this->langs;
    }

    public function getLang($langCode)
    {
        return $this->langs[$langCode];
    }

    public function langExists($langCode)
    {
        return isset($this->langs[$langCode]);
    }

    public function setDefaultLang($lang)
    {
        $this->defaultLangCode = $lang;
        return $this;
    }

    /**
     * Достает язык определенный "по умолчанию"
     * @throws \RuntimeException
     * @return Lang
     */
    public function getDefaultLang()
    {
        if(empty($this->langs)){
            throw new \RuntimeException('Languages not found');
        }
        return $this->langs[$this->defaultLangCode];
    }

    public function isLangDefault($langCode)
    {
        return $this->defaultLangCode == $langCode;
    }

    public function setCurrentLang($langCode)
    {
        if(!$this->langExists($langCode)){
            throw new \RuntimeException('Language "'.$langCode.'" not found');
        }
        $this->currentLangCode = $langCode;
        return $this;
    }

    public function getCurrentLang()
    {
        if(empty($this->langs)){
            throw new \RuntimeException('Languages not found');
        }
        return $this->langs[$this->currentLangCode];
    }

    public function isLangCurrent($langCode)
    {
        return $this->currentLangCode == $langCode;
    }
}