<?php


namespace Aniart\Main\NovaPoshta\Repositories;


use Aniart\Main\NovaPoshta\Exceptions\DepartmentsRepositoryException;
use Aniart\Main\NovaPoshta\Interfaces\DepartmentsRepositoryInterface;
use Aniart\Main\NovaPoshta\Models\Department;
use LisDev\Delivery\NovaPoshtaApi2;

class NovaPoshtaDepartmentsRepository implements DepartmentsRepositoryInterface
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
        return new Department($fields);
    }

    public function getByCityRef($cityRef)
    {
        $result = [];
        try{
            $departments = $this->api->getWarehouses($cityRef);
        }
        catch (\Exception $e){
            throw new DepartmentsRepositoryException($e->getMessage(), $e->getCode());
        }
        if($departments && is_array($departments['data'])){
            foreach($departments['data'] as $depData){
                $department = $this->newInstance($this->hydrate($depData));
                $result[$department->getRefId()] = $department;
            }
        }
        return $result;
    }

    private function hydrate($data)
    {
        return [
            'ID' => $data['Ref'],
            'SITE_KEY' => $data['SiteKey'],
            'NAME_RU' => $data['DescriptionRu'],
            'NAME_UA' => $data['Description'],
            'PHONE' => $data['Phone'],
            'TYPE_REF_ID' => $data['TypeOfWarehouse'],
            'REF_ID' => $data['Ref'],
            'NUMBER' => $data['Number'],
            'CITY_REF_ID' => $data['CityRef'],
            'CITY_NAME_RU' => $data['CityDescriptionRu'],
            'CITY_NAME_UA' => $data['CityDescription'],
            'LONGITUDE' => $data['Longitude'],
            'LATITUDE' => $data['Latitude'],
            'POST_FINANCE' => $data['PostFinance'],
            'BICYCLE_PARKING' => $data['BicycleParking'],
            'POS_TERMINAL' => $data['POSTerminal'],
            'INTERNATIONAL_SHIPPING' => $data['InternationalShipping'],
            'TOTAL_MAX_WEIGHT_ALLOWED' => $data['TotalMaxWeightAllowed'],
            'PLACE_MAX_WEIGH_ALLOWED' => $data['PlaceMaxWeightAllowed'],
            'RECEPTION' => $data['Reception'],
            'DELIVERY' => $data['Delivery'],
            'SCHEDULE' => $data['Schedule']
        ];
    }
}