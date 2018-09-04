<?php


namespace Aniart\Main\Traits;


use Aniart\Main\Error;
use Aniart\Main\Interfaces\ErrorableInterface;

trait ErrorTrait
{
    private $errors = [];

    public function getErrors()
    {
        return $this->errors;
    }

    public function getLastError()
    {
        return end($this->errors);
    }

    public function setErrors(array $errors)
    {
        $this->clearErrors();
        foreach($errors as $error){
            if($error instanceof Error){
                $this->addError($error);
            }
            else{
                $this->addError($error['message'], $error['code'], $error['data']);
            }
        }
        return $this;
    }

    public function clearErrors()
    {
        $this->errors = [];
        return $this;
    }

    public function addError($error, $code = null, $data = [])
    {
        if($error instanceof Error){
            $error->code = $code ?: $error->code;
            $error->data = empty($data) ? $error->data : $data;
            $this->errors[] = $error;
        }
        else{
            $this->errors[] = new Error($error, $code, $data);
        }
        return $this;
    }

    public function errorsCount()
    {
        return count($this->errors);
    }

    public function hasErrors()
    {
        return $this->errorsCount() > 0;
    }

    public function copyErrors(ErrorableInterface $obj, $append = true)
    {
        if($append){
            foreach($obj->getErrors() as $error){
                $this->addError($error);
            }
        }
        else{
            $this->setErrors($obj->getErrors());
        }
        return $this;
    }
}