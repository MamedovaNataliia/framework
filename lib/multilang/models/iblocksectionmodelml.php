<?php


namespace Aniart\Main\Multilang\Models;


use Aniart\Main\Models\IblockSectionModel;
use Aniart\Main\Multilang\I18n;
use Aniart\Main\Multilang\Interfaces\MultilangInterface;
use Aniart\Main\Multilang\MultiLangSeoParamsTrait;

class IblockSectionModelML extends IblockSectionModel implements MultilangInterface
{
    use MultiLangSeoParamsTrait;
    /**
     * @var I18n
     */
    protected  $i18n;

    public function __construct(array $fields = array(), I18n $i18n = null)
    {
        parent::__construct($fields);
        $this->i18n = $i18n ?: app()->make('I18n');
    }

    public function getName($lang = null)
    {
        return $this->i18n('NAME', $lang);
    }

    public function i18n($code, $lang = null)
    {
        $lang = $lang ?: $this->i18n->getCurrentLang()->code;
        if($lang != 'ru'){
            $code.= '_'.strtoupper($lang);
        }
        if(!($message = $this->fields[$code])){
            $message = $this->getPropertyValue($code);
        }
        return $message;
    }    
}