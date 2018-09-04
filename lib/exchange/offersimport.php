<?php

namespace Aniart\Main\Exchange;

/**
 * OffersImport
 * 
 */
class OffersImport extends ShopImport
{
    protected $reference;
    
    public function init()
    {
        $this->reference = $this->setReferenceData();
        $this->reference = $this->setReferenceValues();
        //new \dBug($this->reference);
        
        $this->parse = $this->parseData();
        $this->parse = $this->parseReferenceData();
        $this->parse = $this->parseProperties();
        //new \dBug($this->parse);
    }
    
    protected function getDataRoot()
    {
        return $this->data['КоммерческаяИнформация'];
    }
    
    public function setReference(array $data)
    {
        $this->reference = $data;
    }
    
    public function setDataOffers(array $offers)
    {
        $data = $this->parse;
        $data['ПакетПредложений']['Предложения']['Предложение'] = $offers;
        return $data;
    }
    
    protected function getDataOffers()
    {
        $data = $this->parse['ПакетПредложений']['Предложения']['Предложение'];
        if(!empty($data['Ид']))
        {
            $data = [$data];
        }
        return $data;
    }

    /**
     * Sets the key parameters of the exchange format
     * 
     * @return array
     */
    protected function parseData()
    {
        $result = [];
        $data = $this->data['КоммерческаяИнформация'];
        
        //return $data;
        $result['@attributes'] = $data['@attributes'];
        $result['Классификатор'] = [];
        $result['Классификатор']['Ид'] = $data['ПакетПредложений']['Ид'];
        $result['Классификатор']['Наименование'] = $this->params['name'];
        $result['Классификатор']['Свойства'] = [];
        $result['ПакетПредложений'] = $data['ПакетПредложений'];
        unset($data);
        return $result;
    }
    
    /**
     * It creates an array of reference of non-existent values
     * It creates and populates the ID values for new reference
     * 
     * @return array
     */
    protected function parseReferenceData()
    {
        $data = $this->parse;
        $reference = $this->reference;
        foreach($reference as $key => $item)
        {
            if(empty($item['DATA']))
            {
                continue;
            }
            $counter = 0;
            $result = [];
            $endXmlId = $item['TABLE']['END_XML_ID'];
            if(!empty($endXmlId))
            {
                $endXml =  explode('_', $endXmlId);
                $counter = (int)$endXml[1];
            }
            foreach($item['DATA'] as $i => $val)
            {
                if(!empty($val['ID']))
                {
                    continue;
                }
                $index = strtolower($key).'_'.str_pad(++$counter, 5, '0', STR_PAD_LEFT);
                $reference[$key]['DATA'][$i]['ID'] = $index;
                $result[] = [
                    'ИдЗначения'=>$index,
                    'Значение'=>$val['VALUE']
                ];
            }
            if(count($result) <= 0)
            {
                continue;
            }
            $data['Классификатор']['Свойства']['Свойство'][] = [
                'Ид'=>$item['TABLE']['PROP_XML_ID'],
                'Наименование'=>$item['TABLE']['PROP_NAME'],
                'БитриксКод'=>$key,
                'Множественное'=>$item['PARAMS']['MULTI'],
                'ТипЗначений'=>'Справочник',
                'Внешний'=>true,
                'ВариантыЗначений'=>[
                    'Справочник'=>$result
                ]
            ];
        }
        $data['Классификатор']['Свойства']['Свойство'][] = [
            'Ид'=>'CML2_PICTURES',
            'Наименование'=>'Картинки',
            'ДляТоваров'=>true,
            'Множественное'=>true
        ];
        $this->reference = $reference;
        return $data;
    }
    
    public function parseProperties()
    {
        $data = $this->parse;
        $properties = $this->getDataProperties();
        foreach($properties as $key => $item)
        {
            if(!empty($data['ПакетПредложений']['Предложения']['Предложение']['Ид']))
            {
                $data['ПакетПредложений']['Предложения']['Предложение']['ЗначенияСвойств']['ЗначенияСвойства'] = $item;
            }
            else
            {
                $data['ПакетПредложений']['Предложения']['Предложение'][$key]['ЗначенияСвойств']['ЗначенияСвойства'] = $item;
            }
        }
        return $data;
    }

    /**
     * Sets the reference values of the original data
     * Sets the parameter properties multiplicity
     * 
     * @return array
     */
    protected function setReferenceData()
    {
        $data = $this->getDataRoot();
        $offers = $data['ПакетПредложений']['Предложения']['Предложение'];
        $reference = $this->reference;
        
        if(empty($offers))
        {
            return $reference;
        }
        //one offer
        if(!empty($offers['Ид']))
        {
            $offers = [$offers];
        }
        foreach($offers as $offer)
        {
            $props = $offer['ЗначенияСвойств']['ЗначенияСвойства'];
            if(empty($props))
            {
                continue;
            }
            if(!empty($props['Ид']))
            {
                $props = [$props];
            }
            $multi = false;
            
            foreach($reference as $i=>$ref)
            {
                $xmlId = $ref['TABLE']['PROP_XML_ID'];
                
                foreach($props as $prop)
                {
                    $name = $prop['Ид'];
                    
                    if($xmlId != $name || empty($prop['Значение']))
                    {
                        continue;
                    }
                    if(is_array($prop['Значение']))
                    {
                        $multi = true;

                        foreach($prop['Значение'] as $subval)
                        {
                            $reference[$i]['DATA'][$subval] = [
                                'ID'=>'',
                                'VALUE'=>$subval
                            ];
                        }
                    }
                    else
                    {//saves a plurality of
                        if($reference[$i]['PARAMS']['MULTI'])
                        {
                            $multi = true;
                        }
                        $reference[$i]['DATA'][$prop['Значение']] = [
                            'ID'=>'',
                            'VALUE'=>$prop['Значение']
                        ];
                    }
                    $reference[$i]['PARAMS']['MULTI'] = $multi;
                }
            }
        }
        return $reference;
    }
    
    /**
     * Sets the ID for the values that already exist in reference
     * 
     * @return array
     */
    protected function setReferenceValues()
    {
        $reference = $this->reference;
        foreach($reference as $key => $item)
        {
            $data = $item['TABLE']['DATA'];
            if(empty($data))
            {
                continue;
            }
            foreach($data as $val)
            {
                if(!isset($reference[$key]['DATA'][$val['NAME']]))
                {
                    continue;
                }
                $reference[$key]['DATA'][$val['NAME']]['ID'] = $val['XML_ID'];
            }
        }
        return $reference;
    }
    
    /**
     * Sets price params
     * 
     * @return array
     */
    protected function getDataPrice()
    {
        AddMessage2Log('getDataPrice');
        $offers = $this->getDataOffers();
        $result = [];
        if(empty($offers))
        {
            return $result;
        }
        foreach($offers as $key => $offer)
        {
            $prices = $offer['Цены']['Цена'];
            if(empty($prices))
            {
                continue;
            }
            foreach($prices as $i => $price)
            {
                $prices[$i]['Валюта'] = 'UAH';
            }
            $offers[$key]['Цены']['Цена'] = $prices;
        }
        AddMessage2Log($offers);
        return $this->setDataOffers($offers);
    }

    /**
     * Sets the property values of offers ID of the reference
     * Adds a property image 
     * 
     * @return array
     */
    protected function getDataProperties()
    {
        $data = $this->parse['ПакетПредложений']['Предложения']['Предложение'];
        if(!empty($data['Ид']))
        {
            $data = [$data];
        }
        $reference = $this->reference;
        $result = [];
        if(empty($data))
        {
            return $result;
        }
        foreach($data as $key => $offer)
        {
            $props = $offer['ЗначенияСвойств']['ЗначенияСвойства'];
            if(empty($props))
            {
                continue;
            }
            if(!empty($props['Ид']))
            {
                $props = [$props];
            }
            foreach($reference as $r=>$ref)
            {
                $xmlId = $ref['TABLE']['PROP_XML_ID'];
                foreach($props as $i=>$prop)
                {
                    $list = [];
                    if($xmlId != $prop['Ид'] || empty($prop['Значение']))
                    {
                        continue;
                    }
                    if(is_array($prop['Значение']))
                    {
                        $setPropValue = [];
                        foreach($prop['Значение'] as $subval)
                        {
                            $setPropValue[] = $reference[$r]['DATA'][$subval]['ID'];
                        }
                        $list = [
                            'Ид'=>$prop['Ид'],
                            'Значение'=>$setPropValue
                        ];
                    }
                    else
                    {
                        $list = [
                            'Ид'=>$prop['Ид'],
                            'Значение'=>$reference[$r]['DATA'][$prop['Значение']]['ID']
                        ];
                    }
                    if($list)
                    {
                        $props[$i] = $list;
                    }
                }
            }
            $result[$key] = $props;
        }
        return $result;
    }

    public function getReference()
    {
        return $this->reference;
    }
}