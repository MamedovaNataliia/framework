<?php


namespace Aniart\Main\NovaPoshta\Interfaces;


interface CitiesRepositoryInterface
{
    public function newInstance(array $fields = []);
    public function getByName($name);
}