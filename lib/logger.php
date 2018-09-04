<?php

namespace Aniart\Main;


class Logger {
    private $LogPath		= "";
    private $Prefix			= "";
    private $Mode			= "";
    private $RawLog			= false;
    private $Encoding		= "";

    const MT_ERROR			= 1;
    const MT_WARNING		= 2;
    const MT_NOTICE			= 3;

    /**
     * Конструктор, создает экземляр класса
     *
     * @param string $LogPath - путь к файлу с логом, может быть как абсолютным, так и относительным от начала сайта
     * @param bool $new_file - флаг, определяющий, должен ли лг все время перезаписываться(true), либо дополняться(false)
     * @param bool $use_raw_log - флаг определяющий, нужно ли дополнительно использовать скрытый, сырой лог
     */
    public function __construct($LogPath, $new_file = false, $use_raw_log = false)
    {
        $LogPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $LogPath);
        $LogPath = trim($LogPath, "/");
        $LogPath	= $_SERVER['DOCUMENT_ROOT'].'/'.$LogPath;
        if(is_dir(dirname($LogPath))){
            $this->LogPath	= $LogPath;
        }
        else{
            $this->LogPath	= $_SERVER['DOCUMENT_ROOT'].'/log.txt';
        }

        if($new_file){
            $this->Mode = 'w+';
        }
        else{
            $this->Mode = "a+";
        }

        //Наряду с обычным пользовательским логом, дополнительно будет вестись скрытый сырой лог
        //В который будут записываться сериализированные данные
        if($use_raw_log){
            $RawLogPath		= $this->CreateRawLogPath();
            $this->RawLog	= new CFileLogger($RawLogPath, $new_file);
        }
        $this->Encoding = 'UTF-8';
    }

    /**
     * Вносит в лог строку уведомления
     *
     * @param string $String - текст уведомления
     * @param bool $FromEncoding - кодировка текста
     * @param bool $add_time - флаг, определяющий нужно ли в начале строчки указывать время
     *
     * @return bool
     */
    public function WriteNotice($String, $FromEncoding = '', $add_time = true)
    {
        return $this->PushString($String, self::MT_NOTICE, $FromEncoding, $add_time);
    }

    /**
     * Вносит в лог строку предупреждения
     *
     * @param string $String
     * @param bool $FromEncoding
     * @param bool $add_time
     *
     * @return bool
     */
    public function WriteWarning($String, $FromEncoding = '', $add_time = true)
    {
        return $this->PushString($String, self::MT_WARNING, $FromEncoding, $add_time);
    }

    /**
     * Вносит в лог строку ошибки
     *
     * @param $String
     * @param string $FromEncoding
     * @param bool $add_time
     *
     * @return bool|int
     */
    public function WriteError($String, $FromEncoding = '', $add_time = true)
    {
        return $this->PushString($String, self::MT_ERROR, $FromEncoding, $add_time);
    }

    /**
     * Вносит в лог строку произволного типа (по умолчанию - ошибку)
     *
     * @param $String
     * @param int $MessageType
     * @param string $FromEncoding
     * @param bool $add_time
     *
     * @return bool|int
     */
    public function PushString($String, $MessageType = self::MT_ERROR, $FromEncoding = '', $add_time = true)
    {
        if($this->RawLog){
            $this->RawLog->PushData($String, $MessageType);
        }
        $Prefix	= $this->GetPrefix();
        if(!empty($Prefix)){
            $String = $Prefix.$String;
        }
        $String = self::GetMessageText($MessageType)." ".$String;
        if($add_time){
            $Time	= self::GetCurrentTime();
            $String = "[".$Time."]".$String;
        }
        return $this->WriteIntoLog($String.PHP_EOL, $FromEncoding);
    }

    public function PushFromArray($arData, $MessageType = self::MT_ERROR, $Selector = "; ", $FromEncoding = '', $add_time = true)
    {
        if($this->RawLog){
            $this->RawLog->PushData($arData, $MessageType);
        }
        if(is_array($arData) && !empty($arData)){
            $String = implode($Selector, $arData);
            return $this->PushString($String, $MessageType, $FromEncoding, $add_time);
        }
        return false;
    }

    public function PushData($UserData, $MessageType = self::MT_ERROR, $FromEncoding = '')
    {
        if(!empty($UserData)){
            $Prefix = $this->GetPrefix();
            $Data = array(
                'TIME'	=> time(),
                'TYPE'	=> $MessageType,
                'DATA'	=> $UserData,
                'PREFIX'=> $Prefix
            );
            $String = serialize($Data);
            $this->SetPrefix();
            $Result	= $this->PushString($String, false, $FromEncoding, false);
            if($this->RawLog){
                $this->RawLog->PushString($String, false, $FromEncoding, false);
            }
            $this->SetPrefix($Prefix);
            return $Result;
        }
        return false;
    }

    public function GetEncoding(){
        return $this->Encoding;
    }

    public function GetPrefix(){
        return $this->Prefix;
    }

    public function SetEncoding($Encoding){
        $this->Encoding = $Encoding;
    }

    public function SetPrefix($Prefix = ""){
        $this->Prefix = $Prefix;
    }

    public function ClearLog(){
        $this->WriteIntoLog('','','w+');
    }

    public function WriteIntoLog($Text, $FromEncoding = '', $Mode = false)
    {
        if($Mode === false){
            $Mode = $this->Mode;
        }
        $FileHandler = fopen($this->LogPath, $Mode);
        if($FileHandler){
            if(!empty($FromEncoding) && $FromEncoding != $this->Encoding){
                $Text = iconv($FromEncoding, $this->Encoding, $Text);
            }
            $BytesCount = fwrite($FileHandler, $Text);
            fclose($FileHandler);
            return $BytesCount;
        }
        return false;
    }

    private function CreateRawLogPath()
    {
        $PathParts	= explode('/', $this->LogPath);
        $FileName	= array_pop($PathParts);
        $PathParts[]= ".".$FileName;

        return implode('/', $PathParts);
    }

    private static function GetCurrentTime()
    {
        return date('d-m-Y H:i:s');
    }

    private static function GetMessageText($MessageType)
    {
        switch ($MessageType){
            case 1: return '[ERROR]'; break;
            case 2: return '[WARNING]'; break;
            case 3: return '[NOTICE]'; break;
            default: return ''; break;
        }
    }

} 