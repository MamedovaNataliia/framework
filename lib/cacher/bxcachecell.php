<?php


namespace Aniart\Main\Cacher;


use Bitrix\Main\Data\Cache;

class BXCacheCell extends AbstractCacheCell
{
    /**
     * @var Cache
     */
    protected $cacher;

    public function __construct($key, $ttl = 3600)
    {
        $this->cacher = Cache::createInstance();
        parent::__construct($key, $ttl);
    }

    public function save($value)
    {
        if($this->cacher->startDataCache($this->ttl, $this->key, '/')){
            $this->cacher->endDataCache(array('VALUE' => $value));
            return true;
        }
        return false;
    }

    public function load()
    {
        if($this->cacher->initCache($this->ttl, $this->key, '/')){
            $vars = $this->cacher->getVars();
            return $vars['VALUE'];
        }
        return null;
    }

    public function clean()
    {
        $this->cacher->clean($this->key, '/');
    }
}