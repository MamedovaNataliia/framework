<?

namespace Aniart\Main\Models;

use Aniart\Main\Interfaces\DiscountPricebleInterface;
use Aniart\Main\Traits\DiscountPricebleTrait;

class Offer extends IblockElementModel implements DiscountPricebleInterface {

    use DiscountPricebleTrait;

    private $basePrice;
    private $price;
    private $prices;

    function __construct($fields) {
        parent::__construct($fields);
    }

    public function getBasePrice($format = false) {
        $this->getPrices();
        return $this->format($this->basePrice, $format);
    }

    public function getPrice($format = false) {
        $this->getPrices();
        return $this->format($this->price, $format);
    }

    private function format($price, $format = false) {
        return $format ? \CCurrencyLang::CurrencyFormat($price, $this->getCurrency()) : $price;
    }

    public function getCurrency($format = false) {
        return 'UAH';
    }

    public function getPrices() {

        if (is_null($this->prices)) {
            $this->obtainPrices();
        }
        return $this->prices;
    }

    public function obtainPrices() {
        $this->prices = [];
        $arPrices = \CIBlockPriceTools::GetItemPrices($this->getIBlockId(), $this->fields['PRICE_TYPES'], $this->toArray(), false, []);
        
        foreach ($arPrices as $code => $price) {

            if ($price['MIN_PRICE'] == 'Y') {
                $this->basePrice = $price['VALUE'];
                $this->price = $price['DISCOUNT_VALUE'];
            }
        }

        return $this;
    }

    public function obtainCatalogData() {
        $this->catalogData = array();
        if ($productId = $this->getId()) {
            $arProduct = \CCatalogProduct::GetByID($this->getId());
            if (!empty($arProduct)) {
                $this->catalogData = $arProduct;
            }
        }
        return $this;
    }

    public function isAvailable() {
        return (
                $this->getQuantity() > 0 &&
                $this->getPrice() > 0
                );
    }

    public function getQuantity() {
        if (is_null($this->catalogData)) {
            $this->obtainCatalogData();
        }
        return $this->catalogData['QUANTITY'];
    }

    public function getProductId() {
        return $this->getPropertyValue("CML2_LINK");
    }

    public function getSize() {
        return $this->getPropertyValueName('SIZE');
    }

    public function getArticle() {
        return $this->getPropertyValue("CML2_ARTICLE");
    }


}
