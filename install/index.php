<?php

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
if (class_exists('aniart_main')) {
    return;
}
class aniart_main extends CModule
{
    public $MODULE_ID = 'aniart.main';
    public $MODULE_VERSION = '0.9';
    public $MODULE_VERSION_DATE = '2014-11-13';
    public $MODULE_NAME = 'Базовый модуль Aniart';
    public $MODULE_DESCRIPTION = 'Служит для автоподключение классов. Использует движок D7.';
    public $MODULE_GROUP_RIGHTS = 'N';
    public $PARTNER_NAME = "AniaArt";
    public $PARTNER_URI = "http://aniart.com.ua";

    public function DoInstall()
    {
        global $APPLICATION;
        RegisterModule($this->MODULE_ID);
    }
    public function DoUninstall()
    {
        global $APPLICATION;
        UnRegisterModule($this->MODULE_ID);
    }
}
?>