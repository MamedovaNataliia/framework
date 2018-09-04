<?php
/**
 * Created by PhpStorm.
 * User: damian
 * Date: 10.12.14
 * Time: 11:45
 */

namespace Aniart\Main\Ajax;

/**
 * Class AjaxHandlerFactory
 * @package Aniart\Main\Ajax
 */
final class AjaxHandlerFactory {
    private static $handlers = array();

    /**
     * Регистрирует обработчики
     * @param array $handlers массив вида (идентификатор обработчика => класс обработчика)
     */
    public static function init(array $handlers)
    {
        foreach($handlers as $handlerName => $handlerClass){
            self::registerHandler($handlerName, $handlerClass);
        }
    }

    /**
     * Регистрирует отдельный обработчик
     * @param string $handlerName
     * @param string $handlerClass
     */
    public static function registerHandler($handlerName, $handlerClass)
    {
        if(!isset(self::$handlers[$handlerName])){
            self::$handlers[$handlerName] = $handlerClass;
        }
    }

    /**
     * Возвращает название класса обработчика по его идентификатору
     * @param string $handlerName
     * @return string
     */
    public static function getHandlerClass($handlerName)
    {
        if(isset(self::$handlers[$handlerName])){
            return self::$handlers[$handlerName];
        }
        return false;
    }

    /**
     * Возвращает созданный экземпляр класса обработчика
     * @param $handlerName
     * @return AjaxHandlerInterface|bool
     */
    public static function build($handlerName)
    {
        $handlerClass = self::getHandlerClass($handlerName);
        if($handlerClass && class_exists($handlerClass)){
            return new $handlerClass;
        }
        return false;
    }
} 