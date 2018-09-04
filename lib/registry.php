<?php


namespace Aniart\Main;


use Aniart\Main\Interfaces\RegistryInterface;

class Registry implements RegistryInterface
{
    protected $vars;
    protected $consts;

    protected $sessionKey = 'ANIART_VARS';

    public function set($key, $value = null, $const = false)
    {
        if(is_array($key)){
            foreach($key as $v){
                $this->set($v[0], $v[1], !!$v[2]);
            }
        }
        elseif(!isset($this->consts[$key])){
            $this->vars[$key] = $value;
            if($const){
                $this->consts[$key] = true;
            }
        }
    }

    public function get($key)
    {
        return $this->vars[$key];
    }

    public function remove($key)
    {
        unset($this->vars[$key]);
    }

    public function isExists($key)
    {
        return isset($this->vars[$key]);
    }

    //Storage methods

    public function save($key, $value)
    {
        $_SESSION[$this->sessionKey][$key] = $value;
    }

    public function load($key)
    {
        return $_SESSION[$this->sessionKey][$key];
    }

    public function delete($key)
    {
        unset($_SESSION[$this->sessionKey][$key]);
    }

    public function inStorage($key)
    {
        return isset($_SESSION[$this->sessionKey][$key]);
    }

    public function extract($key)
    {
        $result = null;
        if($this->inStorage($key)){
            $result = $this->load($key);
            $this->delete($key);
        }
        return $result;
    }
}