<?php


namespace Aniart\Main\Multilang\Repositories;

class HLCMessagesRepository extends HLMessagesRepository
{
    protected function getHash($messageText)
    {
        return $messageText;
    }

    public function checkMode($mode)
    {
        if($mode != 'code'){
            throw new \RuntimeException('Class "'.get_class($this).'" supports only "code" mode');
        }
    }
}