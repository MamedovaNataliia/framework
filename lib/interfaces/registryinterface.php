<?php


namespace Aniart\Main\Interfaces;


interface RegistryInterface
{
    public function set($key, $value, $const = false);
    public function get($key);
    public function remove($key);
    public function isExists($key);
    public function save($key, $value);
    public function load($key);
    public function delete($key);
    public function inStorage($key);
    public function extract($key);
}