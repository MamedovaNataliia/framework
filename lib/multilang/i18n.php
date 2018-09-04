<?php

namespace Aniart\Main\Multilang;

use Aniart\Main\Multilang\Models\LangsList;
use Aniart\Main\Multilang\Models\Message;
use Aniart\Main\Multilang\Interfaces\MessagesRepositoryInterface;

/**
 * Class I18n
 * @package Aniart\Main\Multilang
 * TODO реализовать внедрение внутреннего хранилища (массив, кеш, ...)
 */
class I18n
{
    protected $repository;
    protected $langs;
    protected $currentGroup = '';
    protected $readonly = false;
    protected $mode = false;

    protected $messages = array();
    protected $groups = array();

    /**
     * @param MessagesRepositoryInterface $repository
     * @param LangsList $langs
     * @param string $mode - режим работы, может принимать значение hash и code. В режиме hash, в качестве идентификатора
     * указывается текст базового языка, в то время как в режиме code - указывается символьный код:
     * <code>
     *      $lang->message('Привет мир'); //поволяет менять текстовку только переводов
     *      $lang->message('HELLO_WORLD'); //позволяет менять любую текстовку
     * </code>
     */
    public function __construct(MessagesRepositoryInterface $repository, LangsList $langs, $mode = 'hash')
    {
        $this->repository = $repository;
        $this->repository->checkMode($mode);
        $this->langs = $langs;
        if(!$langs->getCurrentLang()){
            $this->setCurrentLangFromPageUri();
        }
        $this->mode = (($mode == 'hash') ? 'hash' : 'code');
    }

    public function getLangs()
    {
        return $this->langs;
    }

    public function setCurrentLangFromPageUri()
    {
        $uri = str_replace(array('http://', 'https://', $_SERVER['SERVER_NAME']), '', $_SERVER['REQUEST_URI']);
        $uri = ltrim($uri, '/');
        $uriParts = explode('/', $uri);
        $currentLang = is_array($uriParts) ? $uriParts[0] : '';
        if(!$this->langs->langExists($currentLang)){
            $currentLang = $this->getDefaultLang()->code;
        }
        $this->setCurrentLang($currentLang);
        return $this;
    }

    public function setCurrentLang($lang)
    {
        $this->langs->setCurrentLang($lang);
        return $this;
    }

    public function getCurrentLang()
    {
        return $this->langs->getCurrentLang();
    }

    public function lang()
    {
        return $this->langs->getCurrentLang()->code;
    }

    public function isLangCurrent($lang)
    {
        return $this->langs->isLangCurrent($lang);
    }

    public function setGroup($group)
    {
        $this->currentGroup = (string)$group;
        return $this;
    }

    public function getGroup()
    {
        return $this->currentGroup;
    }

    public function readonly($value)
    {
        $value = !!$value;
        $this->readonly = $value;
        return $this;
    }

    public function setDefaultLang($lang)
    {
        $this->langs->setDefaultLang($lang);
        return $this;
    }

    public function isLangDefault($lang)
    {
       return $this->langs->isLangDefault($lang);
    }

    public function getDefaultLang()
    {
        return $this->langs->getDefaultLang();
    }

    public function getLangDir($dir = null, $lang = null)
    {
        $dir  = is_null($dir) ? $this->getRawDir() : '/'.ltrim($dir, '/');
        $lang = $lang ?: $this->lang();
        if(!$this->isLangDefault($lang)){
            $dir = '/'.$lang.$dir;
        }
        return $dir;
    }

    public function getRawDir($dir = null)
    {
        $dir = $dir ?: $_SERVER['REQUEST_URI'];
        $dirParts = explode('/', $dir);
        if(is_array($dirParts)){
            if(isset($dirParts[1]) && $this->langs->langExists($dirParts[1])){
                unset($dirParts[1]);
            }
            return implode('/', $dirParts);
        }
        return '';
    }

    /**
     * Возвращает сообщение с учетом языка и группы
     * @param $messageText - текст сообщения на языке по умолчанию
     * @param string|null $group - группа сообщений, среди которых искать нужное, если установлена, то загрузки всех
     * сообщений данной группы происходить не будет, а будет выполнен точечный запрос, в противном случае зугрузятся все сообщения
     * заданные с помощью функции setGroup()
     * @param string|null $lang - язык сообщения
     * @param array $replace
     * @return string
     */
    public function message($messageText, $group = null, $lang = null, array $replace = array())
    {
        $messageText = trim($messageText);
        $lang = $lang ?: $this->lang();

        if(!$messageText || ($this->isLangDefault($lang) && $this->mode == 'hash')){
            return $this->__replace($messageText, $replace);
        }
        if(is_null($group)) {
            $group = $this->getGroup();
        }
        //если группа не задана и раньше не производилась загрузка группы сообщений из внешнего хранилища, то
        //сделаем это сейчас
        if(!isset($this->groups[$group])){
            $this->obtainGroupMessages($group);
        }
        //ищем в собственном хранилище
         if($message = $this->getMessage($messageText, $group, $lang)){
            return $this->__replace($message, $replace);
        }
        //ищем во внешнем хранилище
        $message = $this->repository->getMessage($messageText, (string)$group);
        //если нигде нет сообщения и есть разрешение на запись - сохраним его во внешнем хранилище
        if($message === false && !$this->readonly){
            $message = $this->saveMessage($messageText, $group);
        }

        $messageText = $message ? $message->getText($lang) : '';
        $this->addMessage($messageText, $lang);

        return $this->__replace($messageText);
    }

    protected function obtainGroupMessages($group)
    {
        $messages = $this->repository->getByGroup($group);
        foreach($messages as $message){
            $this->addMessage($message, $group);
        }
        $this->groups[$group] = true;
    }

    protected function saveMessage($messageText, $group)
    {
        $message = new Message(
            array($this->getDefaultLang()->code => $messageText),
            $group
        );
        $this->repository->save($message);
        return $message;
    }

    protected function addMessage($message, $group, $lang = null)
    {
        if($message instanceof Message){
            $messagesTexts = $message->getMessages();
            $hash = ($this->mode == 'hash') ? $this->getHash($messagesTexts[$this->getDefaultLang()->code]) : $message->id;
            foreach($messagesTexts as $lang => $text){
                $this->__message($hash, $group, $lang, $text);
            }
        }
        else{
            $hash = $this->getHash($message);
            $this->__message($hash, $group, $lang, $message);
        }
        return $this;
    }

    protected function getMessage($messageText, $group, $lang)
    {
        $hash = $this->getHash($messageText);
        $message = $this->__message($hash, $group, $lang);

        return is_null($message) ? false : $message;
    }

    protected function __replace($text, array $replace = array())
    {
        return empty($replace) ? $text : str_replace(array_keys($replace), array_values($replace), $text);
    }

    protected function __message($hash, $group, $lang, $messageText = null)
    {
        if($this->mode == 'code'){
            $message = &$this->messages[$group][$lang][$hash];
        }
        else{
            $message = &$this->messages[$hash][$lang];
        }
        if(!is_null($messageText)){
            $message = $messageText;
        }
        return $message;
    }

    protected function getHash($messageText)
    {
        return ($this->mode == 'code') ? $messageText :  md5($messageText);
    }

    public function updateLangForCurrentUser($lang)
    {
        global $USER;
        $enumValue = $this->getUserLangEnumID($lang);

        $USER->Update($USER->GetID(), ["UF_LANG" => $enumValue["ID"]]);
    }

    public function getLangByUser($userID = false)
    {
        global $USER;
        $userID = $userID ?: $USER->GetID();
        $userData = \CUser::GetList($by, $sort, ["ID" => $userID], ["SELECT" => ["UF_LANG"]])->Fetch();
        if ($userData["UF_LANG"]) {
            $enumValue = \CUserFieldEnum::GetList([], ["ID" => $userData["UF_LANG"]])->Fetch();
        }

        return $enumValue["VALUE"] ?: $this->getDefaultLang()->code;
    }

    public function getUserLangEnumID($lang) {
        return \CUserFieldEnum::GetList([], ["VALUE" => $lang, "USER_FIELD_ID" => USER_PROPERTY_LANG])->Fetch();
    }
}
