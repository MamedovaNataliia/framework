<?php

namespace Aniart\Main\Exchange;

/**
 * ProductsImport
 * 
 */
class ProductsImport extends ShopImport
{   
    public function init()
    {
        $this->reference = $this->setReferenceData();
        $this->reference = $this->setReferenceValues();
        
        $this->parse = $this->parseData();
        $this->parse = $this->parseReferenceData();
        $this->parse = $this->parseProperties();
        
        //new \dBug($this->reference);
        //new \dBug($this->parse);
    }

    protected function parseData()
    {
        $data = $this->data['КоммерческаяИнформация'];
        $data['Классификатор']['Свойства'] = [];
        return $data;
    }
    
    protected function getDataRoot()
    {
        return $this->data['КоммерческаяИнформация'];
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
        $offers = $data['Каталог']['Товары']['Товар'];
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
                $data['Каталог']['Товары']['Товар']['ЗначенияСвойств']['ЗначенияСвойства'] = $item;
            }
            else
            {
                $data['Каталог']['Товары']['Товар'][$key]['ЗначенияСвойств']['ЗначенияСвойства'] = $item;
            }
        }
        return $data;
    }
    
    /**
     * Sets the property values of offers ID of the reference
     * Adds a property image 
     * 
     * @return array
     */
    protected function getDataProperties()
    {
        $data = $this->parse['Каталог']['Товары']['Товар'];
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
    
    
}