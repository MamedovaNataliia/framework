<?php
/**
 * Created by PhpStorm.
 * User: pc
 * Date: 8/7/2017
 * Time: 4:23 PM
 */

namespace Aniart\Main\Instagram;


class Controller
{
	/*
	 * Получение значений множественного свойства у виде массива.
	 */
	static function getMultyProperty($iblockId, $itemId, $propertyCode) {
		$values = array();
		$res = \CIBlockElement::GetProperty($iblockId, $itemId, "sort", "asc", array("CODE" => $propertyCode));

		$ob = $res->GetNext();
		$values["LINK_IBLOCK_ID"] = $ob["LINK_IBLOCK_ID"];
		do {
			$values["VALUES"][] = $ob["VALUE"];
		} while($ob = $res->GetNext());

		return $values;
	}

	/*
	 * Преобразование набора ID файлов в набор ссылок, которые ведут на к картинкам.
	 */
	static function convertFileIdsToPaths($ids) {
		$urls = array();
		foreach($ids as $id) {
			$urls[] = \CFile::GetPath($id);
		}

		return $urls;
	}
}