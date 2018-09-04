<?

namespace Aniart\Main\Models;

use Aniart\Main\Interfaces\DiscountPricebleInterface;

class BasketItem extends AbstractModel implements DiscountPricebleInterface
{
    /**
     * @var \Aniart\Main\Repositories\ProductsRepository
     */
    protected $productsRepository = null;

    /**
     * @var \Aniart\Main\Repositories\OffersRepository
     */
    protected $offersRepository = null;

    /**
     * @var \Aniart\Main\Models\Product
     */
    protected $product = null;

    /**
     * @var \Aniart\Main\Models\Offer
     */
    protected $offer = null;
    protected $siblings = null;
    protected $fields = [];

    protected function getPropertyByName($propertyName)
    {
        if(isset($this->fields[$propertyName]))
        {
            return $this->fields[$propertyName];
        }
        return null;
    }
    public function getDate(){
        return $this->fields['DATE_UPDATE'];
    }

    /**
     * @return \Aniart\Main\Models\Product
     */
    public function getProduct()
    {
        if(is_null($this->product))
        {
            $this->productsRepository = app('ProductsRepository');
            $this->offersRepository = app('OffersRepository');

            $offer = $this->offersRepository->getById($this->getProductId());

            $productId = $offer->getProductId();

            $this->product = $this->productsRepository->getById($productId);
        }
        return $this->product;
    }

    /**
     * @return \Aniart\Main\Models\Offer
     */
    public function getOffer()
    {
        if(is_null($this->offer))
        {
            $this->offersRepository = app('OffersRepository');

            $this->offer = $this->offersRepository->getById($this->getProductId());
        }

        return $this->offer;
    }

    public function getSiblings()
    {
        $this->productsRepository = app('ProductsRepository');
        $modelId = $this->product->getModel();
        $this->siblings = $this->productsRepository->getProductsModelId($modelId);
        return $this->siblings;
    }

    /**
     * @param \Aniart\Main\Models\Product|array $product - объект товар или массив полей товара
     * @return $this
     */
    public function setProduct($product)
    {
        if(!empty($product))
        {
            if($product instanceof Product)
            {
                $this->product = $product;
            }elseif(is_array($product))
            {
                $this->product = $this->productsRepository->newInstance($product);
            }
        }
        return $this;
    }

    public function getProductId()
    {
        return $this->getPropertyByName('PRODUCT_ID');
    }

    public function getName()
    {
        return $this->getPropertyByName('NAME');
    }

    public function getQuantity()
    {
        return $this->getPropertyByName('QUANTITY');
    }

    public function getQuantityList()
    {
        $result = [];
        $limit = 5;
        $quantity = $this->getPropertyByName('QUANTITY');
        $list = round($quantity * 1.5);
        for($i = 1; $i <= ($list < $limit ? $limit : $list); $i++)
        {
            $result[] = [
                'ID'=>$i,
                'SELECTED'=>($quantity==$i?true:false)
            ];
        }
        return $result;
    }

    public function getPrice($format = false)
    {
        return $this->formatPrice($this->getPropertyByName('PRICE'), $format);
    }

    public function getBasePrice($format = false)
    {
        return $this->formatPrice($this->getPropertyByName('BASE_PRICE'), $format);
    }

    public function getDiscountPrice($format = false)
    {
        return $this->formatPrice($this->getPropertyByName('DISCOUNT_PRICE'), $format);
    }

    public function getCurrency()
    {
        return $this->getPropertyByName('CURRENCY');
    }

    public function getTotalPrice($format = false)
    {
        $totalPrice = $this->getPrice() * $this->getQuantity();
        return $this->formatPrice($totalPrice, $format);
    }

    public function getTotalBasePrice($format = false)
    {
        $totalBasePrice = $this->getBasePrice() * $this->getQuantity();
        return $this->formatPrice($totalBasePrice, $format);
    }

    public function getTotalDiscountPrice($format = false)
    {
        $totalDiscountPrice = $this->getDiscountPrice() * $this->getQuantity();
        return $this->formatPrice($totalDiscountPrice, $format);
    }

    protected function formatPrice($price, $format = false)
    {
        $price = round($price, CATALOG_VALUE_PRECISION);
        return $format ? SaleFormatCurrency($price, $this->getCurrency()) : $price;
    }

    public function getPreviewPictureSrc($width = 70, $height = 70)
    {
        $previewPictureSrc = $this->getPropertyByName('PREVIEW_PICTURE_SRC');
        if($previewPictureSrc)
        {
            return $previewPictureSrc;
        }
        $previewPicture = $this->getPreviewPicture();
        if(isset($previewPicture['src']))
        {
            return $previewPicture['src'];
        }
        $pictureId = $this->getProduct()->getPreviewPictureId();
        $pictures = getResizedImages($pictureId, ["small" => ["width" => $width, "height" => $height]]
        );
        return $pictures[$pictureId]['small']['src'];
    }

    protected function getPreviewPicture()
    {
        return $this->getPropertyByName('PREVIEW_PICTURE');
    }

    public function getDetailPictureSrc()
    {
        $detailPictureSrc = $this->getPropertyByName('DETAIL_PICTURE_SRC');
        if(isset($detailPictureSrc))
        {
            return $detailPictureSrc;
        }
        return $this->getProduct()->getDetailPictureSrc();
    }

    protected function getDetailPicture()
    {
        return $this->getPropertyByName('DETAIL_PICTURE');
    }

    public function hasDiscount()
    {
        return $this->getDiscountPercentage() > 0;
    }

    public function getDiscountPercentage($format = false)
    {
        $percentage = $this->getPropertyByName('DISCOUNT_PRICE_PERCENT');
        return $format ? $percentage . '%' : $percentage;
    }

    public function getSize()
    {
        $props = $this->getPropertyByName("PROPS");
        $size = array_shift(array_filter($props, function($a)
        {
            return $a["CODE"] == "SIZE";
        }));

        return $size["VALUE"];
    }

}
