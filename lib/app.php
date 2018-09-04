<?php


namespace Aniart\Main;


use Aniart\Main\Interfaces\RegistryInterface;
use Aniart\Main\Interfaces\ServiceLocatorInterface;

/**
 * Class App
 * @package Aniart\Main
 * @method bind($abstract, $concrete = null, $shared = false)
 * @method singleton($abstract, $concrete = null)
 * @method instance($abstract, $instance =  null)
 * @method make($abstract, array $params = array())
 */
final class App
{
    private static $instance;
    /**
     * @var ServiceLocatorInterface
     */
    private $locator;
    /**
     * @var RegistryInterface
     */
    private $registry;
    /**
     * @var View
     */
    private $view;

    private function __construct()
    {
        if(class_exists('\Aniart\Main\Registry')) {
            $this->setRegistry(new Registry());
        }
        if(class_exists('\Aniart\Main\ServiceLocator')){
            $this->setLocator(new ServiceLocator());
        }
    }

    private function __clone(){}

    public static function getInstance()
    {
        if(!isset(self::$instance)){
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getRegistry()
    {
        return $this->registry;
    }

    public function setRegistry(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    public function getLocator()
    {
        return $this->locator;
    }

    public function setLocator(ServiceLocatorInterface $locator)
    {
        $this->locator = $locator;
    }

    public function getView()
    {
        return $this->view;
    }

    public function setView(View $view)
    {
        $this->view = $view;
    }

    public function getCurrentSite()
    {
        return (substr($_SERVER['SERVER_NAME'], 0, 7) == 'samsung' ? 's2' : 's1');
    }

    public function isDev()
    {
        return $this->getEnvironment() == 'DEVELOP';
    }

    public function isProd()
    {
        return $this->getEnvironment() == 'PRODUCTION';
    }

    public function getEnvironment()
    {
        if($_SERVER['SERVER_NAME'] == '!!!maxi.az'){
            return 'PRODUCTION';
        }
        else{
            return 'DEVELOP';
        }
    }

    public function getHttpProtocol()
    {
        $protocol = 'http';
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off'){
            $protocol = 'https';
        }
        return $protocol;
    }

    public function __call($name, array $params = array())
    {
        if(method_exists($this->locator, $name)){
            return call_user_func_array(array($this->locator, $name), $params);
        }
    }
}