<?php

namespace Aniart\Main\Exchange;

use Bitrix\Highloadblock as HL,
    Aniart\Main\Logger;

/**
 * ProductsImportWriter
 * 
 */
class ProductsImportWriter extends ShopImportWriter
{
    
    const CATALOG = PRODUCTS_IBLOCK_ID;
    
    public function __construct(ProductsImport $importer, $path)
    {
        parent::__construct($importer, $path);
    }
    
    public function getReferenceList()
    {
        return [
            'MODEL',
            'COLOR'
        ];
    }
    
    public function getReferenceName()
    {
        $result = [];
        $list = $this->getReferenceList();
        foreach($list as $item)
        {
            $result[] = str_replace('_', '', $item);
        }
        return $result;
    }
    
    public function getPropertiesData()
    {
        $result = [];
        $request = \CIBlockProperty::GetList(
            ['id'=>'asc'], 
            ['IBLOCK_ID'=>self::CATALOG]
        );
        while($row = $request->Fetch())
        {
            $result[$row['CODE']] = $row;
        }
        return $result;
    }
    
    public function getReferenceData()
    {
        $list = $this->getReferenceList();
        $props = $this->getPropertiesData();
        $result = array_fill_keys($list, []);
        $xmlIdAlias = 'XML_ID';
        $nameAlias = 'NAME';
        $hlblockData = HL\HighloadBlockTable::getList([
            'filter'=>['NAME'=>$this->getReferenceName()]
        ]);
        while($hlblockRow = $hlblockData->fetch())
        {
            $tableData = [];
            $endPosition = '';
            $entity = HL\HighloadBlockTable::compileEntity($hlblockRow);
            $entityClass = $entity->getDataClass();
            $tableReques = $entityClass::GetList([
                'select'=>[
                    $xmlIdAlias=>'UF_XML_ID', 
                    $nameAlias=>'UF_NAME'
                ],
                'order'=>[
                    $xmlIdAlias=>'asc'
                ]
            ]);
            while($tableRow = $tableReques->Fetch())
            {
                $tableData[] = $tableRow;
                $endPosition = $tableRow[$xmlIdAlias];
            }
            $hlblockIndex = strtoupper(str_replace('b_hlbd_', '', $hlblockRow['TABLE_NAME']));
            $hlblockRow['PROP_NAME'] = $props[$hlblockIndex]['NAME'];
            $hlblockRow['PROP_XML_ID'] = $props[$hlblockIndex]['XML_ID'];
            $hlblockRow['END_'.$xmlIdAlias] = $endPosition;
            $hlblockRow['DATA'] = $tableData;
            $result[$hlblockIndex]['TABLE'] = $hlblockRow;
        }
        return $result;
    }

    public function write()
    {
        $logger = new Logger('local/logs/exchange/'.date('Y_m_d').'.log');
        $logger->SetPrefix('[import] ');
        try
        {
            $data = $this->getXmlToArray();
            $params = ['name'=>'Каталог товаров'];
            $reference = $this->getReferenceData();
            
            $this->importer->setData($data);
            $this->importer->setParams($params);
            $this->importer->setReference($reference);
            $this->importer->init();
            
            $this->setParse($this->importer->getParse());
            $this->saveData();
            $logger->WriteNotice('Conversion completed success');
        }
        catch (\Exception $e)
        {
            $logger->WriteError('Exception: '.$e->__toString());
            return 'conversion is completed error';
        }
    }
}