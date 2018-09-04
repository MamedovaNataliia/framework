<?
namespace Aniart\Main\Multilang;

/**
 * Класс, использующий hl-блоки для извлечения данных о переводе сообщений. 
 * Использует класс \Aniart\Main\Tools\Highload
 *
 * N-ая версия класса :) Изменнил индекс для сообщений. Решил использовать md5-сумму от фразы. Это позволит однозначно 
 * идентифицировать её
 * 
 * 04.11.2015
 * Добавлен метод select
 * 
 * 29.05.2015
 * Переписал класс для использовния с hl-блоками
 *
 * 02.02.2015
 * Изменил структуру инфоблока. Теперь индекс базовой фразы хранится в свойстве INDEX
 *
 * @author ak@aniart.com.ua 
 * @version 0.4
 * @package Aniart\Main
 */

class TranslateMessage {
	/**
	 * Идентифкатор HL-блока, содержащего переводы сообщений 
	 * @var integer 
	 */
	var $hlBlockId;
	
	/**
	 * Признако того, что сообщения загружены в глобальный массив $MESS
	 * @var boolean
	 */
	var $loaded;

	/**
	 * В конструктор класса передаётся массив со следующими параметрами
	 *   HLBLOCK_ID integer -- идентифкатор HL-блока для хранения переводов 
	 *   READ_ONLY string -- признак того нужно заблокировать запись новых сообщений 
	 *     для переводов в HL-блок (Y) 
	 * @param array $arParams
	 */
	function  __construct($arParams) {
		$this->hlBlockId = $arParams["HLBLOCK_ID"];
		$this->readOnly =  ($arParams["READ_ONLY"] == 'Y')?true:false;
		$this->loaded = false;
		$this->load();
	}
	
	/**
	 * Метод возвращает перевод фразы. Данные берутся из инфоблока $this->hlBlockId. Поиск выполняется
	 * по свойству INDEX, где хранится md5-сумма фразы
	 * 
	 * @param string $message -- фраза, которая переводится
	 * @param string $area -- символьный код раздела, где хранится перевод фразы  !!! ОСТАВЛЕН ДЛЯ СОВМЕСТИМОСТИ. В НАСТОЯЩИЙ МОМЕНТ НЕ ИСПОЛЬЗУЕТСЯ !!!
	 * @param array() $pattern -- шаблон вида array("#MASK_1#" => "значение маски", "#MASK_2#" => "значение маски"), 
	 * 														который используется для замены паттерна (см. функцию Битрикс GetMessage())
	 * @param boolean $readOnly -- признак того добавлять ли в инфоблок фразу для перевода
	 * @return string
	 */
	function get($message, $area = "template", $pattern = array()) {
		global $MESS, $LANG;

		$result = $message;
		
		if (trim($message) == '') return $message;
		
		if (!is_array($pattern)) $pattern = array();

		// Если это числа, то не переводим
		if (is_numeric($message) || is_float($message)) return $message;

		$index = md5($message);
		
		if($LANG->getCurrent() == $LANG->getDefault()) {
			// если выбран язык по умолчанию, то используем текущее сообщение 
			if (!isset($MESS[$index]) || empty($MESS[$index]))
				$MESS[$index] = $message;

			$result = GetMessage($index, $pattern);
			
			return $result;
		}
		
		if (!isset($MESS[$index]) || empty($MESS[$index])) {
			//if ($_REQUEST['DEBUG'] == 'Y') pre_dump_clr(array($message, $index, $MESS[$index], $this->readOnly, !$this->readOnly));
			
			// Если по какой-то причине сообщения нет в $MESS там нет, то добавляем его в hl-блок и помещаем в массив
			if (!$this->readOnly)
				$this->addMessage($index, $message);
			
			$MESS[$index] = $message;
		}
		
		$result = GetMessage($index, $pattern);
		
		return htmlspecialchars_decode($result);
	}

	/**
	 * Метод возвращает перевод фразы на основе вышестоящего метода Get 
	 * + используется экранирование спецсимволов в строке
	 */
	function getJs($message, $area = "template", $pattern = array()){
		return addslashes($this->Get($message, $area = "template", $pattern = array()));
	}
	
	/**
	 * Метод загружает в глобальный массив $MESS переводы из раздела с символьным кодом
	 * $sectionCode
	 *  
	 * @param string $sectionCode !!! ОСТАВЛЕНА ДЛЯ СОВМЕСТИМОСТИ !!!
	 */
	function load($sectionCode) {
		if ($this->loaded) return;
		
		global $LANG;
		
		// Загрузку выполняем только, если выбран язык отличный от языка по умолчанию
		if($LANG->getCurrent() != $LANG->getDefault()) {
			global $MESS;
			
			$hl = new \Aniart\Main\Tools\Highload;
			
			$rsMessages = $hl->GetDataFromHL($this->hlBlockId, array('>ID' => 0));
			
			$upperLang = $LANG->getCurrent(true);
			
			foreach ($rsMessages as $arMessage) {
				// Если нет перевода сообщения, то используемый базовый
				if (empty($arMessage["UF_MESSAGE_".$upperLang]))
					$MESS[$arMessage["UF_INDEX"]] = $arMessage["UF_MESSAGE"];
				else
					$MESS[$arMessage["UF_INDEX"]] = $arMessage["UF_MESSAGE_".$upperLang];
			}
		}
		
		$this->loaded = true;
	}
	
	/**
	 * Добавляем сообщение в hl-блок переводов сообщений 
	 *
	 * @param string $index
	 * @return boolean
	 */
	function AddMessage($index, $message) {
		$result = false;
	
		if (!empty($index)) {
			$hl = new \Aniart\Main\Tools\Highload;
				
			$resOp = $hl->Add(
				$this->hlBlockId,
				array(
					'UF_INDEX' => $index,
					'UF_MESSAGE' => $message,
				)
			);

			$result = $resOp['SUCCESS'];
		}

		return $result;
	}
	
	/**
	 * Выбираем из базовой фразы и её перевода непустое значение. Предпочтение отдаётся
	 * переводу.
	 * 
	 * @param string $baseValue
	 * @param string $translateValue
	 * @return string
	 */
	function select($baseValue, $translateValue) {
		return empty($translateValue) ? $baseValue : $translateValue;
	}
}
