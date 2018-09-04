<?

namespace Aniart\Main\Repositories;

class ProductsRepository extends AbstractIblockElementRepository
{

    protected $selectedFields = [
        '*',
        'PROPERTY_CML2_ARTICLE',
        'PROPERTY_MORE_PHOTO',
        'PROPERTY_BASE_PRICE',
        'PROPERTY_DISCOUNT_PRICE',
        'PROPERTY_COLLECTION',
        'PROPERTY_COLOR',
        'PROPERTY_MODEL'
    ];

    /**
     * @param array $fields
     * @return \Aniart\Main\Models\Product
     */
    public function newInstance(array $fields = array())
    {
        return app('Product', array($fields));
    }

    public function getProductsModelId($modelId, $onlyActive = true)
    {
        $result = [];
        if(empty($modelId))
        {
            return $result;
        }
        $filter = ["PROPERTY_MODEL" => $modelId];

        if($onlyActive)
        {
            $filter["ACTIVE"] = "Y";
        }
        return $this->getList(["SORT" => "ASC"], $filter);
    }

    public function getProductsByCollectionId($collectionId, $onlyActive = true)
    {
        $filter = ["PROPERTY_COLLECTION" => $collectionId];

        if($onlyActive)
        {
            $filter["ACTIVE"] = "Y";
        }
        return $this->getList(["SORT" => "ASC"], $filter);
    }

    public function getProductsBySectionId($sectionId, $onlyActive = true)
    {
        $filter = ["SECTION_ID" => $sectionId, "INCLUDE_SUBSECTIONS" => "Y"];

        if($onlyActive)
        {
            $filter["ACTIVE"] = "Y";
        }
        return $this->getList(["SORT" => "ASC"], $filter, false, ['nTopCount' => 19]);
    }

}
