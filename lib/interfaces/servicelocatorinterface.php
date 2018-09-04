<?php


namespace Aniart\Main\Interfaces;


interface ServiceLocatorInterface
{
    /**
     * Привязывает класс сущности к её абстрактному представлению
     * @param string|array $abstract название абстрактной сущности
     * @param string|callable|null $concrete класс существующей сущности, если null - то название совпадает с $abstract
     * @param bool|false $shared расшарить сущность, т.е. в рамках абстрактной сущности при вызове make
     * всегда будет возвращаться одна и та жа конкретная
     * @throws \InvalidArgumentException
     * @return null
     */
    public function bind($abstract, $concrete = null, $shared = false);

    /**
     * Привязывает класс сущности к её абстрактному представлению, делая из неё синглтон
     * @param string|array $abstract название абстрактной сущности
     * @param mixed|callable|null $concrete класс существующей сущности, если null - то название совпадает с $abstract
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public function singleton($abstract, $concrete = null);

    /**
     * Привязывает конретную сущность к её абстрактному представлению
     * @param string|array $abstract название абстрактной сущности
     * @param mixed $concrete существующая сущность, если null - то название совпадает с $abstract
     * @return mixed
     */
    public function instance($abstract, $concrete);

    /**
     * Создает и(или) возвращает конретную сущность, по её абстрактному представлению
     * @param string $abstract название абстрактной сущности
     * @param array $params доп. параметры, которые будут использованы для создания сущности
     * @return mixed
     */
    public function make($abstract, array $params = array());
}