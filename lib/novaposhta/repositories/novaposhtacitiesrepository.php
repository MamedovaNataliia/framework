<?php


namespace Aniart\Main\NovaPoshta\Repositories;


use Aniart\Main\NovaPoshta\Interfaces\CitiesRepositoryInterface;
use Aniart\Main\NovaPoshta\Exceptions\CitiesRepositoryException;
use Aniart\Main\NovaPoshta\Models\City;
use LisDev\Delivery\NovaPoshtaApi2;

class NovaPoshtaCitiesRepository implements CitiesRepositoryInterface
{
    /**
     * @var NovaPoshtaApi2
     */
    protected $api;

    public function __construct(NovaPoshtaApi2 $api)
    {
        $this->api = $api;
    }

    public function newInstance(array $fields = [])
    {
        return new City($fields);
    }

    public function getByName($name)
    {
        $result = [];
        try{
            $cities = $this->api->getCities(0, $name);
        }
        catch(\Exception $e){
            throw new CitiesRepositoryException($e->getMessage(), $e->getCode(), $e);
        }
        if($cities && is_array($cities['data'])){
            foreach($cities['data'] as $cityData){
                $city = $this->newInstance($this->hydrate($cityData));
                $result[$city->getRefId()] = $city;
            }
        }
        return $result;
    }

    private function hydrate($data)
    {
        return [
            'ID' => $data['CityID'],
            'REF_ID' => $data['Ref'],
            'NAME_RU' => $data['DescriptionRu'],
            'NAME_UA' => $data['Description'],
            'AREA_REF_ID' => $data['Area']
        ];
    }
}