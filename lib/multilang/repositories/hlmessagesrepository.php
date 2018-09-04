<?php


namespace Aniart\Main\Multilang\Repositories;


use Aniart\Main\Multilang\Interfaces\MessagesRepositoryInterface;
use Aniart\Main\Multilang\Models\LangsList;
use Aniart\Main\Multilang\Models\Message;
use Bitrix\Highloadblock\HighloadBlockTable;

class HLMessagesRepository implements MessagesRepositoryInterface
{
    protected $entity;
    /**
     * @var \Aniart\Main\Multilang\Models\LangsList
     */
    protected $langs;

    public function __construct($hlblock_id, LangsList $langs)
    {
        $hlblock       = HighloadBlockTable::getById($hlblock_id)->fetch();
        $this->entity  = HighloadBlockTable::compileEntity($hlblock)->getDataClass();
        $this->langs   = $langs;
    }

    public function getMessage($messageText, $group = null)
    {
        $message = false;
        $entityClass = $this->entity;
        $filter = array('=UF_HASH' => $this->getHash($messageText));
        if($group){
            $filter['=UF_GROUP'] = $group;
        }
        if($fields = $entityClass::getList(array('filter' => $filter))->fetch()){
            $message = $this->newInstance($fields);
        }
        return $message;
    }

    public function getByGroup($group)
    {
        $messages = array();
        $entityClass = $this->entity;
        //если группа не задана то вытягиваем все
        $params = $group ? array('filter' => array('=UF_GROUP' => $group)) : array();
        $rs = $entityClass::getList($params);
        while($fields = $rs->fetch()){
            $messages[] = $this->newInstance($fields);
        }
        return $messages;
    }

    public function newInstance(array $fields)
    {
        $messages = array();
        foreach($this->langs->all() as $lang){
            $langCode = &$lang->code;
            $fieldName = 'UF_MESSAGE_'.strtoupper($langCode);
            if(!isset($fields[$fieldName])){
                $fieldName = 'UF_MESSAGE';
            }
            $messages[$langCode] = $fields[$fieldName];
        }
        return new Message(
            $messages,
            $fields['UF_GROUP'],
            $fields['UF_HASH']
        );
    }

    public function save(Message $message)
    {
        if(!$message->id){
            $entityClass = $this->entity;
            $result = $entityClass::add($this->hydrate($message));
            if($result->isSuccess()){
                $message->id = $result->getId();
                return true;
            }
            else{
                //для дебага можно включать
                //throw new \RuntimeException(implode("\n", $result->getErrorMessages()));
            }
        }
        return false;
    }

    public function checkMode($mode)
    {
        if($mode != 'hash'){
            throw new \RuntimeException('Class "'.get_class($this).'" supports only "hash" mode');
        }
    }

    protected function hydrate(Message $message)
    {
        $fields = array(
            'UF_GROUP' => $message->group
        );
        foreach($message->getMessages() as $lang => $text){
            $fieldName = 'UF_MESSAGE';
            if(!$this->langs->isLangDefault($lang)){
                $fieldName.='_'.strtoupper($lang);
            }
            else{
                $fields['UF_HASH'] = $this->getHash($text);
            }
            $fields[$fieldName] = $text;
        }
        return $fields;
    }

    protected function getHash($messageText)
    {
        return md5($messageText);
    }
}