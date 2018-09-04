<?php

/**
 * Преобразует первый символ строки в верхний регистр (для UTF-8)
 * @param string $str
 * @return string
 */
function mbUcFirst($str)
{
    $fc = mb_strtoupper(mb_substr($str, 0, 1));
    return $fc.mb_substr($str, 1);
}

/*
 * Функция склонения числительных в рус. языке
 *
 * @param int    $number Число которое нужно просклонять
 * @param array  $titles Массив слов для склонения
 * @return string
 */

function declOfNum($number, $titles)
{
    $cases = array(2, 0, 1, 1, 1, 2);
    return $number." ".$titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
}

/**
 * Функция для ведения упрощенного файла логов
 *
 * @param string $message
 */
function Add2Log($message, $fileLog = false, $timestamp = true)
{
    if(defined("FILE_LOG"))
        $fileLog = FILE_LOG;
    elseif($fileLog === false)
        $fileLog = $_SERVER["DOCUMENT_ROOT"]."/upload/file.log";

    $fp = fopen($fileLog, "a+");
    fwrite($fp, (($timestamp) ? date('d.m.Y H:i:s').' ' : '').$message."\n");
    fclose($fp);
}

/**
 * Выводит на странице var_dump обрамленный в тег <pre>
 *  - если последний передаваемый параметр === true, то вызывается die;
 */
function pre_dump()
{
    $arguments = func_get_args();
    $die = array_pop($arguments);
    if(!is_bool($die))
    {
        $arguments[] = $die;
    }
    echo "<br clear='all' />";
    echo "<pre>";
    call_user_func_array('var_dump', $arguments);
    echo "</pre>";
    if($die === true)
    {
        die;
    }
}

/**
 *  Выводит на странице var_dump обрамленный в тег <pre>, удаляет весь предшествующий вывод (для битрикса)
 *   - если последним параметром не указано false, то вызывается die;
 */
function pre_dump_clr()
{
    static $notToDiscard;
    global $APPLICATION;
    if(is_object($APPLICATION) && !$notToDiscard)
    {
        $APPLICATION->RestartBuffer();
        $notToDiscard = true;
    }
    $arguments = func_get_args();
    $arg_count = count($arguments);
    if(!is_bool($arguments[$arg_count - 1]))
    {
        $arguments[] = true;
    }
    call_user_func_array('pre_dump', $arguments);
}

/**
 * Функция выводит отладочную информацию (замена pre+print_r+pre) на экран
 *
 * @param any $obj -- объект, значение которого выводят
 * @param boolean $admOnly -- функция доступна только администартору
 * @param boolean $die -- остановить выполнение скрипта
 * @return boolean
 */
function p($obj, $admOnly = true, $d = false)
{
    global $USER, $arAccessDebugFromIP;

    if(($USER->IsAdmin() || $admOnly === false) && (empty($arAccessDebugFromIP) || in_array($_SERVER["REMOTE_ADDR"], $arAccessDebugFromIP)))
    {
        echo "<pre>";
        print_r($obj);
        echo "</pre>";

        if($d === true)
            die();
    }
}

/**
 * Функция выводит отладочную информацию (замена pre+print_r+pre) в файл
 *
 * @param any $obj -- объект, значение которого выводят
 * @param boolean $admOnly -- функция доступна только администартору
 * @param boolean $die -- остановить выполнение скрипта
 * @param boolean $fileName -- файл куда будет писаться dump
 * @return boolean
 */
function p2f($obj, $admOnly = false, $die = false, $fileName = "_dump.html")
{
    global $USER;
    if($admOnly === false || $USER->IsAdmin())
    {
        $dump = "<pre style='font-size: 11px; font-family: tahoma;'>".print_r($obj, true)."</pre>";
        $files = $_SERVER["DOCUMENT_ROOT"]."/".$fileName;
        $fp = fopen($files, "a+");
        fwrite($fp, $dump);
        fclose($fp);
        if($die)
            die();
    }
}

function checkPhone($phone, $code = '380')
{
    $phone = _normalizePhone($phone, $code);
    return (is_numeric($phone) && strlen($phone) == 12);
}

function _normalizePhone($phone, $code = '380')
{
    $phone = preg_replace('/[^\d]/', '', $phone);
    if($code && strpos($phone, $code) === 0 && strlen($phone) == 12)
    {
        return $phone;
    }
    return $code.$phone;
}
