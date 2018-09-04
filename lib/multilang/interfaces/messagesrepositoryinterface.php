<?php


namespace Aniart\Main\Multilang\Interfaces;


use Aniart\Main\Multilang\Models\Message;

interface MessagesRepositoryInterface
{
    /**
     * Возвращает сообщение по его тексту
     * @param $message - текст сообщения
     * @param null $group - название группы сообщений
     * @return Message|false
     */
    public function getMessage($message, $group = null);

    /**
     * Сохраняет сообщение
     * @param Message $message
     * @return bool
     */
    public function save(Message $message);

    /**
     * Возвращает все сообщения для заданной группы, если группа  не задана, то возвращает все сообщения
     * @param $group
     * @return Message[]
     */
    public function getByGroup($group);

    /**
     * Проверяет в каком режиме может работать класс, если режим не поддерживается, то вызывается исключение
     * @param $mode
     * @return void
     * @throws \RuntimeException
     */
    public function checkMode($mode);
}