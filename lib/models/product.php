<?
namespace Aniart\Main\Models;
use Aniart\Main\Interfaces\DiscountPricebleInterface;
use Aniart\Main\Traits\DiscountPricebleTrait;

class Product extends IblockElementModel implements DiscountPricebleInterface
{

    use DiscountPricebleTrait;

    /**
     * @var \Aniart\Main\Repositories\OffersRepository
     */
    private $offerRepositoryInstance;

    /**
     * @var \Aniart\Main\Repositories\TypesRepository
     */
    private $typesRepositoryInstance;

    /**
     * @var \Aniart\Main\Repositories\CollectionsRepository
     */
    private $collectionsRepositoryInstance;
    private $type;
    private $collection;
    private $price;
    private $basePrice;
    private $offers;
    private $sibling;

    public function __construct(array $fields = array())
    {
        $this->offerRepositoryInstance = app("OffersRepository");
        $this->typesRepositoryInstance = app("TypesRepository");

        parent::__construct($fields);
    }

    protected function createSection(array $fields = [])
    {
        return app('ProductSectionsRepository')->newInstance($fields);
    }

    public function getAplication()
    {
        global $APPLICATION;
        return $APPLICATION;
    }

    public function getMinPicture($id, $width = 60, $height = 90)
    {
        return $this->getPictureInfo($id, $width, $height);
    }

    private function getPictureInfo($pictureId, $width, $height)
    {
        $pictureId = (int)$pictureId;
        if(!$pictureId)
        {
            return false;
        }
        $images = getResizedImages(
            [$pictureId],
            ['picture' => ['width' => $width, 'height' => $height]]
        );
        return $images[$pictureId]['picture'];
    }

    public function getAllImagesId($count = false)
    {
        $imagesID = [];
        if($this->getPropertyValue('MORE_PHOTO'))
        {
            $imagesID = array_merge($imagesID, (array) $this->getPropertyValue('MORE_PHOTO'));
        }
        if($count)
        {
            $imagesID = array_slice($imagesID, 0, $count);
        }

        return $imagesID;
    }

    public function getFirstLevelSection()
    {
        $section = array_shift($this->getSections());
        return $section->IBLOCK_SECTION_ID ?: $section->getId();
    }

    public function getBasePrice($format = false)
    {
        if(is_null($this->basePrice))
        {
            $this->basePrice = $this->getPropertyValue('MIN_PRICE');
            if(!$this->basePrice && $offer = $this->getMinPriceOffer())
            {
                $this->basePrice = $offer->getBasePrice();
            }
        }
        return $this->format($this->basePrice, $format);
    }

    public function getPrice($format = false)
    {

        if(is_null($this->price))
        {
            if($offer = $this->getMinPriceOffer())
            {
                $this->price = $offer->getPrice();

            }
            $this->price = $this->price ?: $this->getPropertyValue('MIN_PRICE');

        }

        return $this->format($this->price, $format);
    }

    /**
     * @return Offer
     */
    public function getMinPriceOffer()
    {
        $this->getOffers();
        return $this->offers['minPrice'];
    }

    /**
     * @return Offer[]
     */
    public function getOffers()
    {
        if(is_null($this->offers))
        {
            $this->obtainOffers();
        }
        return $this->offers['all'];
    }

    public function obtainOffers()
    {
        $this->setOffers(
            $this->offerRepositoryInstance->getByProductId($this->getId(), true)
        );
        return $this;
    }

    /**
     * @param Offer[] $offers
     * @return $this
     */
    public function setOffers(array $offers = [])
    {
        $this->offers = [
            'all' => [],
            'minPrice' => false
        ];
        if(!empty($offers))
        {
            $offer = current($offers);
            $offer = array_reduce($offers, function(Offer $offer, Offer $o)
            {
                if(($o->getPrice() < $offer->getPrice()))
                {
                    $offer = $o;
                }
                return $offer;
            }, $offer);

            $this->offers['all'] = $offers;
            $this->offers['minPrice'] = $offer;
        }
        return $this;
    }

    public function getCurrency()
    {
        return 'UAH';
    }

    private function format($price, $format = false)
    {
        return $format ? \CCurrencyLang::CurrencyFormat($price, $this->getCurrency()) : $price;
    }

    /**
     * @return Type|false
     */
    public function getType()
    {
        if(is_null($this->type))
        {
            $this->type = $this->typesRepositoryInstance->getByXmlId(
                $this->getTypeId()
            );
        }
        return $this->type;
    }

    public function getTypeId()
    {
        return $this->getPropertyValue('VID');
    }

    public function setCollection(Collection $collection)
    {
        $this->collection = $collection;
        return $this;
    }

    public function setSibling($sibling)
    {
        $this->sibling = $sibling;
        return $this;
    }

    /**
     * @return Collection|false
     */
    public function getCollection()
    {
        if(is_null($this->collection))
        {
            $this->collection = $this->collectionsRepositoryInstance->getById(
                $this->getCollectionId()
            );
        }
        return $this->collection;
    }

    public function getSibling()
    {
        return $this->sibling;
    }

    public function getCollectionId()
    {
        return $this->getPropertyValue('COLLECTION');
    }

    public function getArticle()
    {
        return $this->getPropertyValue('CML2_ARTICLE');
    }

    public function getColor()
    {
        return $this->getPropertyValueName('COLOR');
    }
    public function getModel()
    {
        return $this->getPropertyValue('MODEL');
    }

    public function getConsist()
    {
        return $this->getPropertyValue('CONSIST');
    }
    public function getCare()
    {
        return $this->getPropertyValue('CARE');
    }

    public function getColorData()

    {
        $data = $this->getPropertyValueData('COLOR');
        if(empty($data))
        {
            return false;
        }
        return array(
            'FILE' => $this->getFilePath($data['UF_FILE']),
            'NAME' => $data['UF_NAME'],
            'CODE' => $data['UF_CODE']
        );
    }

    public function getColorImg()
    {
        $data = $this->getPropertyValueData('COLOR');
        if(empty($data))
        {
            return false;
        }
        return $this->getFilePath($data['UF_FILE']);
    }

    public function getRecomendedProduct(){

        $data = $this->getPropertyValueData('RECOMMENDED_PRODUCT');

        if(empty($data))
        {
            return false;
        }
        return array_keys ($data);
    }

    public function getSizes(){
        $data = array();
        $arData = $this->getPropertyValueData('SIZE');

        if($arData){
            foreach ($arData as $arItem){
                $data[] = $arItem;
            }
        }
        return $data;
    }

}
