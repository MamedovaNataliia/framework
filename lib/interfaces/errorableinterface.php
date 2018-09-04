<?php


namespace Aniart\Main\Interfaces;


use Aniart\Main\Error;

interface ErrorableInterface
{
    /**
     * Добавляет ошибку
     *
     * @param Error|string $error объект Error или строка с сообщением об ошибке
     * @param null $code код ошибки
     * @param array $data - дополнительные данные
     * @return $this
     */
    public function addError($error, $code = null, $data = []);

    /**
     * Устанавливает несколько ошибок предварительно очищая, все что было добавлено до этого
     *
     * @param Error[]|array $errors массив оьъектов Error или массив с массивом параметров ['message' => '', 'code' => null, 'data' => []]
     * @return $this
     */
    public function setErrors(array $errors);

    /**
     * Возвращает массив установленных ошибок
     *
     * @return array
     */
    public function getErrors();

    /**
     * Возвращает объект последней добавленной ошибки
     *
     * @return Error
     */
    public function getLastError();

    /**
     * Проверяет наличие ошибок
     *
     * @return bool
     */
    public function hasErrors();

    /**
     * Очищает ранее установленные ошибки
     * @return $this
     */
    public function clearErrors();

    /**
     * Возврщает количество ошибок
     * @return int
     */
    public function errorsCount();

    /**
     * Копирует ошибки из другого объекта
     * @param ErrorableInterface $obj
     * @param bool|true $append - определяет должны ли ошибки дополниться либо перезаписать текущие
     * @return mixed
     */
    public function copyErrors(ErrorableInterface $obj, $append = true);
}