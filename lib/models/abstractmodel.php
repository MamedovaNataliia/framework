<?php
namespace Aniart\Main\Models;


abstract class AbstractModel
{
	protected $fields = array();

    public function isNew() {
        $id = $this->getId();
        return empty($id);
    }

	public function getId() {
		return $this->fields['ID'];
	}

	public function __construct(array $fields = array()) {
		$this->setFields($fields);
	}

	public function __set($name, $value) {
		$this->fields[$name] = $value;
	}

	public function __get($name) {
		return $this->fields[$name];
	}

	public function getFields() {
		return $this->fields;
	}

	public function setFields(array $fields = array()) {
		$this->fields = $fields;
	}

	public function mergeFields(array $fields = array()) {
		$this->fields = array_merge($this->fields, $fields);
	}

	public function toArray() {
		return $this->fields;
	}
}