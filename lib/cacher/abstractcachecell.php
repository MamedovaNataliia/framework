<?php


namespace Aniart\Main\Cacher;


abstract class AbstractCacheCell
{
    protected $key;
    protected $ttl;

    public function __construct($key, $ttl = 3600)
    {
        $this->setKey($key);
        $this->ttl = (int)$ttl;
    }

    abstract public function save($value);

    abstract public function load();

    abstract public function clean();

    protected function setKey($key)
    {
        if(!is_string($key)){
            $key = md5(serialize($key));
        }
        $this->key = $key;
    }
}