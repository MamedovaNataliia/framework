<?php
/**
 * Created by PhpStorm.
 * User: pc
 * Date: 8/8/2017
 * Time: 10:46 AM
 */

namespace Aniart\Main\Instagram;


class InstagramIBlockController
{
  /*
   * Метод для проверки уникальности имени файла. На вход подается само имя и ID информационного блока.
   * Используется для того, чтобы не создавались одинаковые елементы.
	 */
	private static function isUnique($filename, $iblockId) {
		$arSelect = Array("NAME");
		$arFilter = Array(
			"IBLOCK_ID" => $iblockId,
			"ACTIVE_DATE" => "Y",
			"ACTIVE" => "Y"
		);
		$res = \CIBlockElement::GetList(Array(), $arFilter, false, Array(), $arSelect);
		while($ob = $res->GetNextElement()) {
			$arFields = $ob->GetFields();
			if(strcmp($filename, $arFields["NAME"]) == 0)
				return false;
		}
		return true;
	}

	/*
	 * Метод для добавления елемента в информационный блок. Елемент состоит из имени, картинки, которая заносится
	 * в картинку для анонса, описания - в описании для анонса, и набора тегов. Картинка с расширением IMAGE_EXTENSION
	 * выгружается из папки $imageFolder.
	 */
	private static function addItem($userId, $iblockId, $tags, $imageFolder, $name, $imageExtension, $description) {
		$element = new \CIBlockElement;

		$PROP = array();
		$PROP["TAGS"] = $tags;
		$arLoadProductArray = Array(
			"MODIFIED_BY"    => $userId,
			"IBLOCK_SECTION_ID" => false,
			"IBLOCK_ID"      => $iblockId,
			"PROPERTY_VALUES"=> $PROP,
			"NAME"           => $name,
			"ACTIVE"         => "Y",
			"PREVIEW_TEXT"   => $description,
			"PREVIEW_PICTURE" => \CFile::MakeFileArray($imageFolder.$name.$imageExtension)
		);

		$element->Add($arLoadProductArray);
	}

	/*
	 * Сохраняет фотографии низкого расширения из постов инстаграма в указаную папку. За имя файла берется
	 * код created_time, который прикреплен к каждому посту.
	 */
	public static function savePhotos($arMedia, $imageFolder, $imageExtension) {
		foreach($arMedia["data"] as $arItem) {
			$imageLink = $arItem["images"]["low_resolution"]["url"];
			$filename = $arItem["created_time"];
			@copy($imageLink, $imageFolder.$filename.$imageExtension);
		}
	}

	/*
	 * Вытягивает из апи описание поста, код created_time и теги. Код берется как имя файла, и фото выгружаются
	 * из заданой папки.
	 */
	public static function loadPostsToIBlockInstagram($arMedia, $userId, $instagramIBlockId,
																										 $imageFolder, $imageExtension) {
		foreach($arMedia["data"] as $arItem) {
			$description = $arItem["caption"]["text"];
			$filename = $arItem["created_time"];
			$arTag = $arItem["tags"];
			if(self::isUnique($filename, $instagramIBlockId)) {
				self::addItem($userId, $instagramIBlockId, $arTag, $imageFolder, $filename,
					$imageExtension, $description);
			}
		}
	}
}