<?php


namespace Aniart\Main\Multilang\Models;


class Message
{
    public $id;
    public $group;
    public $code;
    protected $messages = array();

    /**
     *
     * @param array $messages массив сообщений вида array('ru' => 'Текст', 'en' => 'Text', ..., 'lang' => 'text')
     * @param null $group группа, которой принадлежит сообщение
     * @param null $id - идентификатор сообщения в хранилищие
     */
    public function __construct(array $messages, $group = null, $id = null)
    {
        $this->messages = $messages;
        $this->group = $group;
        $this->id = $id;
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function getText($lang)
    {
        return (string)$this->messages[$lang];
    }
}