<?php


namespace Aniart\Main;


class Error
{
    public $message;
    public $code;
    public $data;

    public function __construct($message, $code = null, $data = [])
    {
        $this->message = $message;
        $this->code = $code;
        $this->data = $data;
    }
}