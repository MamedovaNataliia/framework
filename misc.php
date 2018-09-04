<?php

/**
 * Определяет главную страницу
 * 
 * @return boolean
 */
function detectMain()
{
    global $APPLICATION;
    if($APPLICATION->GetCurDir(false) === i18n()->getLangDir('/'))
    {
        return true;
    }
    return false;
}

/**
 * Если задан $abstract, то выполняется метод \Aniart\Main\App::make($abstract, $parans), в противном случае
 * возвращается экземпляр класса \Aniart\Main\App
 * @param string|null $abstract абстрактное представление сущности
 * @param array $params дополнительные параметры для создания сущности
 * @return \Aniart\Main\App|mixed
 */
function app($abstract = null, $params = array())
{
    $app = Aniart\Main\App::getInstance();
    
    if(is_null($abstract))
    {
        return $app;
    }
    return $app->make($abstract, $params);
}

/**
 * @param null $key
 * @param null $value
 * @param bool|false $const
 * @return mixed|\Aniart\Main\Interfaces\RegistryInterface
 */
function registry($key = null, $value = null, $const = false)
{
    $registry = app()->getRegistry();
    
    //new \dBug($registry);
    
    if(is_null($key))
    {
        return $registry;
    }
    elseif(is_string($key) && is_null($value))
    {
        return $registry->get($key);
    }
    else
    {
        $registry->set($key, $value, $const);
    }
}

/**
 * Если задан $message, то выполняется метод \Aniart\Main\Multilang\I18n::message, в противном случае возвращается
 * экземпляр класса \Aniart\Main\Multilang\I18n
 * @param string|null $message текст сообщения на базовом языке
 * @param string|null $group группа сообщения
 * @param string|null $lang код языка на котором необходимо вывести сообщение
 * @param array $replace
 * @return \Aniart\Main\Multilang\I18n|string
 */
function i18n($message = null, $group = null, $lang = null, array $replace = array())
{
    /**
     * @var \Aniart\Main\Multilang\I18n $i18n
     */
    $i18n = app('I18n');
    //new \dBug(app());
    //include.php
    if(is_null($message))
    {
        return $i18n;
    }
    return $i18n->message($message, $group, $lang, $replace);
}


function getPageClass()
{
    global $APPLICATION;
    return $APPLICATION->GetPageProperty('pageClass');
}

function i18nSelect($arg1, $arg2)
{
    return ( i18n()->getDefaultLang()->code === i18n()->lang()) ? $arg1 : $arg2;
}

/**
 * Формирует древовидный массив.
 * 
 * @param array $arItems - входящий массив.
 * @param int $ParentID - id привязка к родителю.
 * @param int $ChildID - id элемента.
 * @return array.
 */
function buildTree($arItems, $ParentID = 'PARENT_ID', $ChildID = 'ID') {
    $Childs= array();
    if(!is_array($arItems) || empty($arItems)) {
        return array();
    }
    foreach($arItems as &$Item) {
        if(!$Item[$ParentID]) {
            $Item[$ParentID] = 0;
        }
        $Childs[$Item[$ParentID]][] = &$Item;
    }
    unset($Item);
    foreach($arItems as &$Item) {
        if (isset($Childs[$Item[$ChildID]])) {
            $Item['CHILDS'] = $Childs[$Item[$ChildID]];
        }
    }
    return $Childs[0];
}

function getResizedImages($imagesID, $sizes)
{
    $result = [];
    foreach((array) $imagesID as $id)
    {
        foreach($sizes as $sizeName => $size)
        {
            $imageParams = \CFile::ResizeImageGet($id, $size, BX_RESIZE_IMAGE_PROPORTIONAL, true);
            if($imageParams["src"])
            {
                $result[$id][$sizeName] = $imageParams;
            }
        }
    }
    return $result;
}

function getLinkWithQueryString($link)
{
    if(empty($link) || strpos($link, '?') !== false)
    {
        return $link;
    }
    return $link.($_SERVER['QUERY_STRING'] ? '?'.$_SERVER['QUERY_STRING'] : '');
}
