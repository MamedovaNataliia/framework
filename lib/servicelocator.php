<?php


namespace Aniart\Main;


use Aniart\Main\Interfaces\ServiceLocatorInterface;

class ServiceLocator implements ServiceLocatorInterface
{
    protected $bindings = array();
    protected $instances = array();

    const GET_BINDING = 0;
    const GET_INSTANCE = 1;

    public function bind($abstract, $concrete = null, $shared = false)
    {
        $this->checkAbstract($abstract);
        if(is_array($abstract)){
            foreach($abstract as $abs => $con){
                $this->bind($abs, $con, $shared);
            }
            return;
        }
        if(is_null($concrete)){
            $concrete = $abstract;
        }
        $this->checkConcrete($concrete);

        $shared = !!$shared;

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance($abstract, $instance = null)
    {
        $this->checkAbstract($abstract);
        if(is_array($abstract)){
            foreach($abstract as $abs => $ins){
                $this->instance($abs, $ins);
            }
            return;
        }
        $this->instances[$abstract] = $instance;
    }

    private function checkAbstract($abstract)
    {
        if(!is_array($abstract) && !is_string($abstract)){
            throw new \InvalidArgumentException('Type must be array or string');
        }
        return $abstract;
    }

    private function checkConcrete($concrete)
    {
        if(is_string($concrete) ){
            if(!class_exists($concrete)) {
                throw new \InvalidArgumentException('Class "'.$concrete.'" not found');
            }
        }
        elseif(!is_callable($concrete)){
            throw new \InvalidArgumentException('Type for $concrete must be string or callable');
        }
    }

    /**
     * @param string $abstract
     * @param array $params
     * @param array $bindings
     * @return mixed|object
     */
    public function make($abstract, array $params = array(), $bindings = array())
    {
        $this->checkAbstract($abstract);

        if(isset($this->instances[$abstract])){
            return $this->instances[$abstract];
        }

        if(!isset($this->bindings[$abstract])){
            throw new \RuntimeException('Abstract object("'.$abstract.'") not bound');
        }

        extract($this->bindings[$abstract]);
        if(is_string($concrete)){
            $object = $this->newInstance($concrete, $params);
        }
        elseif(is_callable($concrete)){
            $params = array_merge(array($this), $params);
            $object = call_user_func_array($concrete, $params);
        }
        else{
            $object = $concrete;
        }

        if($shared){
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    private function newInstance($className, $args)
    {
        if(!method_exists($className, '__construct')){
            return new $className;
        }
        $refMethod = new \ReflectionMethod($className,  '__construct');
        $params = $refMethod->getParameters();
        $re_args = array();
        foreach($params as $key => $param)
        {
            if ($param->isPassedByReference()){
                $re_args[$key] = &$args[$key];
            }
            else{
                if(is_null($args[$key]) && $param->isDefaultValueAvailable()){
                    $args[$key] = $param->getDefaultValue();
                }
                $re_args[$key] = $args[$key];

            }
        }

        $refClass = new \ReflectionClass($className);
        return $refClass->newInstanceArgs($re_args);
    }

    public function get($abstract, $get = self::GET_BINDING)
    {
        if($get === self::GET_INSTANCE){
            return $this->instances[$abstract];
        }
        return $this->bindings[$abstract];
    }
}