<?php


namespace Aniart\Main\Multilang\Models;


class Lang implements \ArrayAccess
{
    public $code;
    public $name;

    private $data; //additional data

    /**
     * @param string $code код языка
     * @param string $name название языка
     * @param array $data дополнительные параметры
     */
    public function __construct($code, $name, $data = array())
    {
        $this->code = $code;
        $this->name = $name;
        $this->data = $data;
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}