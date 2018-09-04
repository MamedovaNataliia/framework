<?php


namespace Aniart\Main\NovaPoshta\Interfaces;


interface DepartmentsRepositoryInterface
{
    public function newInstance(array $fields = []);
    public function getByCityRef($cityRef);
}