<?php

use Aniart\Main\Multilang\I18n;
use Aniart\Main\Multilang\Models\Lang;
use Aniart\Main\Multilang\Models\LangsList;
use Aniart\Main\Multilang\Repositories\HLCMessagesRepository;
use Aniart\Main\Seo\CustomFilterSEFController;
use Aniart\Main\ServiceLocator;

$modulePath = dirname(__FILE__);

include $modulePath.'/lib/dBug.php';

include $modulePath . '/vars.php';
include $modulePath . '/utils.php';
include $modulePath . '/misc.php';
include $modulePath . '/events.php';

Bitrix\Main\Loader::includeModule('iblock');
Bitrix\Main\Loader::includeModule('highloadblock');
Bitrix\Main\Loader::includeModule('catalog');
Bitrix\Main\Loader::includeModule('sale');

include_once $_SERVER['DOCUMENT_ROOT'].'/.composer/vendor/autoload.php';

Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    '\Aniart\Main\Ajax\Handlers\OrderAjaxHandler' => '/local/components/aniart/sale.order/ajax.php',
]);

$langs = new LangsList([
    new Lang('ru', 'Русский', ['iso' => 'ru']),
    new Lang('ua', 'Украинский', ['iso' => 'ua']),
], 'ru');

app()->bind([
    'CacheCell' => '\Aniart\Main\Cacher\BXCacheCell',
    'logger' => '\Aniart\Main\Logger',
    'Basket' => 'Aniart\Main\Models\Basket',
    'BasketItem' => 'Aniart\Main\Models\BasketItem',
    'Product' => 'Aniart\Main\Models\Product',
    'ProductSection' => 'Aniart\Main\Models\ProductSection',
    'Offer' => 'Aniart\Main\Models\Offer',
    'Order' => '\Aniart\Main\Models\Order',
    'SaleDelivery' => 'Aniart\Main\Models\SaleDelivery',
    'SaleDeliveryService' => 'Aniart\Main\Models\SaleDeliveryService',
    'SalePaySystem' => 'Aniart\Main\Models\SalePaySystem',
    'Review' => 'Aniart\Main\Models\Review'
]);

app()->singleton([
    'LangMessagesRepository' => function() use ($langs){
        return new HLCMessagesRepository(HL_LANG_MESSAGES_ID, $langs);
    },
    'I18n' => function(ServiceLocator $locator) use ($langs){
        return new I18n(
            $locator->make('LangMessagesRepository'),
            $langs,
            'code'
        );
    },
    'ReviewsRepository' => function(ServiceLocator $locator){
        return new \Aniart\Main\Repositories\ReviewsRepository(REVIEWS_IBLOCK_ID);
    },
    'ProductsRepository' => function(ServiceLocator $locator){
        return new \Aniart\Main\Repositories\ProductsRepository(PRODUCTS_IBLOCK_ID);
    },
    'ProductSectionsRepository' => function(ServiceLocator $locator){
        return new \Aniart\Main\Repositories\ProductSectionsRepository(PRODUCTS_IBLOCK_ID);
    },
    'OffersRepository' => function(ServiceLocator $locator){
        return new \Aniart\Main\Repositories\OffersRepository(OFFERS_IBLOCK_ID, [BASE_CODE_PRICE]);
    },
    'TypesRepository' => function(ServiceLocator $locator){
        return new \Aniart\Main\Repositories\TypesRepository(HL_MODELS_ID);
    },
    'BasketItemsRepository' => function(ServiceLocator $locator){
        return new \Aniart\Main\Repositories\BasketItemsRepository(app('BasketItem', [[]]));
    },
    'SaleOrdersRepository' => function(ServiceLocator $locator){
        return new \Aniart\Main\Repositories\SaleOrdersRepository(app('Order',[[]]));
    },
    'PaySystemsRepository' => function(ServiceLocator $locator){
        return new \Aniart\Main\Repositories\SalePaySystemsRepository(
            $locator->make('SalePaySystem', [[]])
        );
    },
    'DeliveriesRepository' => function(ServiceLocator $locator){
        return new Aniart\Main\Repositories\SaleDeliveriesRepository(
            $locator->make('SaleDelivery', [[]])
        );
    },
    'DeliveryServicesRepository' => 'Aniart\Main\Repositories\SaleDeliveryServicesRepository',
    'NovaPoshtaApi' => function(){
        return new \LisDev\Delivery\NovaPoshtaApi2(NEW_POST_API_KEY, i18n()->lang());
    },
    'NewPostCitiesRepository' => function(ServiceLocator $locator){
        return new \Aniart\Main\NovaPoshta\Repositories\NovaPoshtaCitiesRepository(
            $locator->make('NovaPoshtaApi')
        );
    },
    'NewPostDepartmentsRepository' => function(ServiceLocator $locator){
        return new \Aniart\Main\NovaPoshta\Repositories\NovaPoshtaDepartmentsRepository(
            $locator->make('NovaPoshtaApi')
        );
    },
    'StoresRepository' => 'Aniart\Main\Repositories\StoresRepository',
    //SEO
    'SeoParamsCollector' => '\Aniart\Main\Seo\SeoParamsCollector',
    //Services
    'IBlockTools' => function (ServiceLocator $locator) {
        return new \Aniart\Main\Tools\IBlock();
    },
    'FilterProperty' => function(ServiceLocator $locator){
        return new \Aniart\Main\Tools\FilterProperty(PRODUCTS_IBLOCK_ID, OFFERS_IBLOCK_ID);
    },
    'CatalogService' => '\Aniart\Main\Services\CatalogService',
    'BasketService' => '\Aniart\Main\Services\BasketService',
    'NewPostService' => '\Aniart\Main\Services\NewPostService',
]);
    
//Доп параметры для чпу-фильтров
CustomFilterSEFController::setAdditionalFilteredProps(['sizes']);

\Aniart\Main\Ajax\AjaxHandlerFactory::init([
	'common' => '\Aniart\Main\Ajax\Handlers\CommonAjaxHandler',
    'auth' => '\Aniart\Main\Ajax\Handlers\AuthAjaxHandler',
    'catalog' => '\Aniart\Main\Ajax\Handlers\CatalogAjaxHandler',
    'subscribe' => '\Aniart\Main\Ajax\Handlers\SubscribeAjaxHandler',
    'basket' => '\Aniart\Main\Ajax\Handlers\BasketAjaxHandler',
    'order' => '\Aniart\Main\Ajax\Handlers\OrderAjaxHandler',
    'favorites' => '\Aniart\Main\Ajax\Handlers\FavoritesAjaxHandler'
]);

$jsExtConfig = [
    'jquery_1' => [
        'js' => '/local/modules/aniart.main/js/jquery-1.10.2.min.js'
    ]
];
foreach($jsExtConfig as $extName => $extParams)
{
    \CJSCore::RegisterExt($extName, $extParams);
}

\CJSCore::Init(['jquery_1']);
