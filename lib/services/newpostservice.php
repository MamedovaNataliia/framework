<?php


namespace Aniart\Main\Services;


use Aniart\Main\Exceptions\NewPostServiceException;
use Aniart\Main\NovaPoshta\Exceptions\CitiesRepositoryException;
use Aniart\Main\NovaPoshta\Exceptions\DepartmentsRepositoryException;
use Aniart\Main\NovaPoshta\Interfaces\CitiesRepositoryInterface;
use Aniart\Main\NovaPoshta\Interfaces\DepartmentsRepositoryInterface;

class NewPostService
{
    /**
     * @var CitiesRepositoryInterface
     */
    protected $citiesRepository;
    /**
     * @var DepartmentsRepositoryInterface
     */
    protected $departmentsRepository;

    public function __construct()
    {
        $this->citiesRepository = app('NewPostCitiesRepository');
        $this->departmentsRepository = app('NewPostDepartmentsRepository');
    }

    public function getCitiesByQuery($query)
    {
        try{
            $cities = $this->citiesRepository->getByName($query);
        }
        catch(CitiesRepositoryException $e){
            throw new NewPostServiceException($e->getMessage(), $e->getCode(), $e);
        }
        return $cities;
    }

    public function getDepartmentsByCityRef($cityRef)
    {
        try{
            $departments = $this->departmentsRepository->getByCityRef($cityRef);
        }
        catch(DepartmentsRepositoryException $e){
            throw new NewPostServiceException($e->getMessage(), $e->getCode(), $e);
        }
        return $departments;
    }
}