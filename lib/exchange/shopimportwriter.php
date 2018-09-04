<?php

namespace Aniart\Main\Exchange;

use \Aniart\Main\Tools\XmlToArray;
use \Aniart\Main\Tools\ArrayToXml;

/**
 * ShopImportWriter
 * 
 */
abstract class ShopImportWriter
{
    protected $importer;
    protected $path;
    protected $parse;

    public function __construct(ShopImport $importer, $path)
    {
        AddMessage2Log("in shopImportWriterConstruct");
        if(empty($importer))
        {
            throw new \Exception('importer is not set');
        }
        if(empty($path))
        {
            throw new \Exception('path is not set');
        }
        $this->importer = $importer;
        $this->path = $path;
    }
    
    protected function setParse(array $data)
    {
        if(empty($data))
        {
            throw new \Exception('parse is not set');
        }
        return $this->parse = $data;
    }

    protected function getFile()
    {
        $file = file_get_contents($this->path);
        if(!$file)
        {
            throw new \Exception('file is not found');
        }
        return $file;
    }
    
    protected function getBaseXml()
    {
        $file = $this->getFile();
        return $file;
    }
    
    protected function getXmlToArray()
    {
        return XmlToArray::createArray($this->getBaseXml());
    }
    
    protected function getArrayToXml()
    {
        $data = ArrayToXml::createXML('КоммерческаяИнформация', $this->parse);
        return $data->saveXML();
    }
    
    protected function saveData()
    {
        $file = file_put_contents($this->path, $this->getArrayToXml());
        if(!$file)
        {
            throw new \Exception('converted file is not saved');
        }
        return true;
    }

    public function getImporter()
    {
        return $this->importer;
    }
    
    public function getPath()
    {
        return $this->path;
    }

    abstract public function write();
}
