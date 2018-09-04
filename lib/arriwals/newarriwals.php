<?php
/**
 * Created by PhpStorm.
 * User: pc
 * Date: 8/7/2017
 * Time: 4:20 PM
 */

namespace Aniart\Main\Arriwals;


class NewArriwals
{
	/*
	 * Усли полученное название имеет длинну больше чем 35 символов, то функция обрезает название до 35 символов и добавляет,
	 * в конце, "...", в противном случае возвращается полное название.
	 */
	static function nameCutting($name) {
		define("ENCODING", "UTF-8");
		define("END_OF_LINE", "...");
		define("MAX_LENGTH_OF_NAME", 26);

		if(mb_strlen($name, ENCODING) > MAX_LENGTH_OF_NAME)
			return mb_substr($name, 0, MAX_LENGTH_OF_NAME, ENCODING).END_OF_LINE;
		else
			return $name;
	}

	/*
	 * Строка, которая состоит из цветов, разделенных пробелом, разделяется на категории SOLID и BACKGROUND.
	 * Первый елемент заносится в SLID, а второй - в BACKGROUND.
	 */
	static function separateColors($colors) {
		$pairs = array();

		foreach($colors as $colorLine) {
			$colorsPair = array();
			$expodedColors = explode(" ", $colorLine);
			$colorsPair["SOLID"] = $expodedColors[0];
			$colorsPair["BACKGROUND"] = $expodedColors[1];
			$pairs[] = $colorsPair;
		}

		return $pairs;
	}
}