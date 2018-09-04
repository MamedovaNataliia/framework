<?php
/**
 * Created by PhpStorm.
 * User: pc
 * Date: 8/7/2017
 * Time: 3:12 PM
 */

namespace Aniart\Main\Instagram;
use Aniart\Main\Instagram\Controller;


class InstagramPopap
{
	/*
	 * Здесь идет проверка на пустоту. Если нету картинки для превью - то значит, что елемент с продуктом пустой.
	 * Будет работать таким-же принципом, как если количество продуктов больше нуля.
	 */
	static function checkPreviwImage($url, &$arItem) {
		if(!empty($url))
			$arItem["IS_NOT_EMPTY"] = true;
		else $arItem["IS_NOT_EMPTY"] = false;

		return $url;
	}

	/*
	 * Фукнция получает массив, который состоит из LINK_IBLOCK_ID(ID информационного блока товара),
	 * ID товаров и код совйства - возвращает массив ссылок на картинки товаров, которые привязаны к етому ID.
	 */
	static function getUrlsOfProductImages($arProduct, $propertyCode, &$arItem) {
		$arProductInfo = array();

		foreach($arProduct["VALUES"] as $productId) {
			$arImageId = Controller::getMultyProperty(intval($arProduct["LINK_IBLOCK_ID"]), intval($productId), $propertyCode);
			$arRes = \CIBlockElement::GetByID(intval($productId));
			$url = $arRes->GetNext();
			$arUrl["DETAIL_PAGE_URL"] = $url["DETAIL_PAGE_URL"];
			$arUrl["PREVIEW_PRODUCT_IMAGE"]["SRC"] = self::checkPreviwImage(\CFile::GetPath($arImageId["VALUES"][0]), $arItem);
			$arUrl["PRODUCT_IMAGES_URLS"] = Controller::convertFileIdsToPaths($arImageId["VALUES"]);
			$arProductInfo[] = $arUrl;
		}

		return $arProductInfo;
	}
}