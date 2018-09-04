<?php


namespace Aniart\Main\Models;


use Aniart\Main\Interfaces\BasketInterface;
use Aniart\Main\Interfaces\PricebleInterface;
use Aniart\Main\Repositories\SaleDeliveriesRepository;
use Aniart\Main\Repositories\SaleDeliveryServicesRepository;
use Aniart\Main\Repositories\SalePaySystemsRepository;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\ShipmentCollection;

class Order extends AbstractModel implements PricebleInterface
{
    protected $fields;
    protected $propsValues;
    protected $propsMeta;
    /**
     * @var BasketInterface
     */
    protected $basket;
    protected $paySystem;
    protected $delivery;

    public function __construct(array $fields = [])
    {
	    if(isset($fields['BASKET_ITEMS'])){
		    $this->initBasket($fields['BASKET_ITEMS']);
		    unset($fields['BASKET_ITEMS']);
	    }
        parent::__construct($fields);
    }

	public function setBasket(Basket $basket)
	{
		$this->basket = $basket;
		return $this;
	}

	public function getBasket()
	{
		if(is_null($this->basket)){
			$this->obtainBasket();
		}
		return $this->basket;
	}

	public function obtainBasket()
	{
		if(!$this->isNew()){
			$basketItems = app('BasketItemsRepository')->getList([], ['ORDER_ID' => $this->getId()]);
			$this->initBasket($basketItems);
		}
		return $this;
	}

	public function initBasket(array $basketItems)
	{
		$this->basket = app('Basket', [['BASKET_ITEMS' => $basketItems]]);
		return $this;
	}

    public function setPaySystemId($paySystemId)
    {
        $this->fields['PAY_SYSTEM_ID'] = $paySystemId;
        $this->paySystem = null;
        return $this;
    }

    public function setPaySystem(SalePaySystem $paySystem)
    {
        $this->paySystem = $paySystem;
        return $this;
    }

    /**
     * @return SalePaySystem|false
     */
    public function getPaySystem()
    {
        if(
            is_null($this->paySystem) &&
            ($paySystemId = $this->getPaySystemId())
        ){
            /**
             * @var SalePaySystemsRepository $paySystemsRepository
             */
            $paySystemsRepository =  app('PaySystemsRepository');
            $this->paySystem = $paySystemsRepository->getById($paySystemId);
        }
        return $this->paySystem;
    }

    public function getPaySystemId()
    {
        return $this->fields['PAY_SYSTEM_ID'];
    }

    public function setDeliveryId($deliveryId)
    {
        $this->fields['DELIVERY_ID'] = $deliveryId;
        $this->delivery = null;
        return $this;
    }

    public function setDelivery(SaleDelivery $delivery)
    {
        $this->delivery = $delivery;
        return $this;
    }

    public function getDelivery()
    {
        if(
            is_null($this->delivery) &&
            ($deliveryId = $this->getDeliveryId())
        ){
            /**
             * @var SaleDeliveriesRepository $deliveriesRepository
             */
            $deliveriesRepository =  app('DeliveriesRepository');
            $this->delivery = $deliveriesRepository->getById($deliveryId);
            if(!$this->delivery){ //возможно имеем дело с профилем доставки
                /**
                 * @var SaleDeliveryServicesRepository $deliveryServicesRepository
                 */
                $deliveryServicesRepository = app('DeliveryServicesRepository');
                $this->delivery = $deliveryServicesRepository->getParentService($deliveryId);
            }
        }
        return $this->delivery;
    }

    public function getDeliveryId()
    {
        return $this->fields['DELIVERY_ID'];
    }

    public function getAmount()
    {
        $amount = $this->getPrice();
        if(!$this->isNew()){
            /**
             * @var \Bitrix\Sale\Order $bxOrder
             */
            $bxOrder = \Bitrix\Sale\Order::load($this->getId());
            if($bxOrder->getPaymentCollection()->count() > 0){
                $paymentSum = $bxOrder->getPaymentCollection()->getSum();
                if($paymentSum){
                    $amount = $paymentSum;
                }
            }
        }
        return $amount;
    }

    public function getUserId()
    {
        return $this->fields['USER_ID'];
    }

    public function getPrice($format = false)
    {
	    $price = $this->getBasketPrice() + $this->getDeliveryPrice();
	    return $this->formatPrice($price, $format);
    }

    public function getBasketPrice($format = false)
    {
        return $this->formatPrice($this->getBasket()->getPrice(), $format);
    }

	public function getDeliveryPrice($format = false)
	{
		return $this->formatPrice($this->fields['PRICE_DELIVERY'], $format);
	}

	protected function formatPrice($price, $format)
	{
		$price = round($price, CATALOG_VALUE_PRECISION);
		return $format ? SaleFormatCurrency($price, $this->getCurrency()) : (float)$price;
	}

	public function getCurrency()
	{
	    $this->fields['CURRENCY'] = $this->fields['CURRENCY'] ?: $this->getBasket()->getCurrency();
		return $this->fields['CURRENCY'];
	}

    public function getPropertyValue($code)
    {
        $this->getPropsValues();
        return $this->propsValues[$code];
    }

    public function getPropsValues()
    {
        if(is_null($this->propsValues)){
            $this->obtainPropsValues();
        }
        return $this->propsValues;
    }

    public function getPropsMeta($primary = 'ID')
    {
        if(is_null($this->propsMeta)){
            $this->propsMeta = [];
            $rsProps = OrderPropsTable::getList();
            while($arProp = $rsProps->fetch()){
                $this->propsMeta[$arProp['ID']] = $arProp;
            }
        }
        if($primary == 'ID'){
            return $this->propsMeta;
        }
        return array_combine(array_map(function($propMeta) use ($primary){
            return isset($propMeta[$primary]) ? $propMeta[$primary] : $propMeta['ID'];
        }, $this->propsMeta), $this->propsMeta);
    }

    public function setPropsMeta(array $propsValues)
    {
        $this->propsMeta = array_combine(array_map(function($propValue){
            return $propValue['ID'];
        }, $propsValues), $propsValues);

        return $this;
    }

    public function obtainPropsValues()
    {
        $this->propsValues = array();
        $rsPropsValues = \CSaleOrderPropsValue::GetOrderProps($this->getId());
        while($arPropValue = $rsPropsValues->Fetch()){
            $propCode = $arPropValue['CODE'];
            if(!$propCode){
                $propCode = $arPropValue['ID'];
            }
            $this->propsValues[$propCode] = $arPropValue['VALUE'];
        }
        return $this;
    }

    public function getAccountNumber()
    {
        return $this->fields['ACCOUNT_NUMBER'];
    }

    public function getCoupons() {
        DiscountCouponsManager::load();
        $coupons = DiscountCouponsManager::get();
        return $coupons;
    }
}