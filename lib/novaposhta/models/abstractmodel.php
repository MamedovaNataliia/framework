<?php


namespace Aniart\Main\NovaPoshta\Models;


abstract class AbstractModel
{
    protected $fields = [];

    public function __construct(array $fields = [])
    {
        $this->setFields($fields);
    }

    /**
     * @return array
     */
    abstract public function getAvailableFields();

    public function setFields(array $fields = [])
    {
        $availableFields = array_flip($this->getAvailableFields());
        $this->fields = array_intersect_key($fields, $availableFields);
    }

    public function toJSON()
    {
        return json_decode($this->toArray());
    }

    public function toArray()
    {
        return $this->fields;
    }
}