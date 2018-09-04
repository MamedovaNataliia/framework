<?php

namespace Aniart\Main\Exchange;

/**
 * ShopImport
 * 
 */
abstract class ShopImport
{
    protected $data;
    protected $params;
    protected $parse;
    
    public function setData(array $data)
    {
        if(empty($data))
        {
            throw new \Exception('data is not set');
        }
        return $this->data = $data;
    }
    
    public function setParams(array $params)
    {
        if(empty($params))
        {
            throw new \Exception('params is not set');
        }
        return $this->params = $params;
    }
    
    public function setReference(array $data)
    {
        $this->reference = $data;
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    public function getParams()
    {
        return $this->params;
    }
    
    public function getParse()
    {
        return $this->parse;
    }
    
    abstract public function init();

    abstract protected function parseData();
}