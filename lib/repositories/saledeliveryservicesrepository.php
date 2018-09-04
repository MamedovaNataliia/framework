<?php


namespace Aniart\Main\Repositories;

use Aniart\Main\Models\SaleDeliveryService;
use Bitrix\Sale\Delivery\Services\Manager;

class SaleDeliveryServicesRepository
{
    public function newInstance(array $fields = [])
    {
        return app('SaleDeliveryService', [$fields]);
    }

    public function getParentService($id)
    {
        $result = false;
        if($service = $this->getById($id)){
            $result = ($parentId = $service->getParentId())
                ? $this->getById($parentId)
                : $service
            ;
        }
        return $result;
    }

    /**
     * @param $id
     * @return SaleDeliveryService|false
     */
    public function getById($id)
    {
        $result = false;
        $id = (int)$id;
        if($id && $serviceData = Manager::getById($id)){
            $result = $this->newInstance($serviceData);
        }
        return $result;
    }
}