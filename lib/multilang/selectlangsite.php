<?
namespace Aniart\Main\Multilang;

/**
 * Класс использующийся для работы с несколькими языковыми версиями сайта
 * @author ak
 *
 */
class SelectLangSite {
	var $defaultLang;
	var $currentLang;
	var $virtualCurDir; // текущий каталог без привязки к языку
	var $listLang;

	/**
	 * Инициализируем класс. Определяем текущий язык согласно $_SERVER["REQUEST_URI"]
	 */
	function  __construct($arParams) 
	{
		$this->defaultLang = (empty($arParams["DEFAULT_LANG"]))?"ru":$arParams["DEFAULT_LANG"];
		
		$this->listLang = (empty($arParams["LIST_LANG"]))?array():$arParams["LIST_LANG"];
		 
		$this->currentLang = $this->defaultLang;
		$this->virtualCurDir = "/";
		
		// Чтобы не перегружать php работой с регулярными выражениями, распарсим строку и найдём необходимое.
		// Также рассчитаем абсолютный путь к странице
		$arUrl = parse_url($_SERVER["REQUEST_URI"]);
		
		if (!empty($arUrl["path"]))
		{
			$arUrlSection = explode("/", $arUrl["path"]);
			// В элементе с индексом 1 будет содержаться языковый префикс
			$currentLang = $arUrlSection[1];
			if (!empty($currentLang) && isset($this->listLang[$currentLang]))
			{
				$this->currentLang = $currentLang;
				for ($i = 2; $i < count($arUrlSection); $i++) {
					if (empty($arUrlSection[$i])) break;
					$this->virtualCurDir .= $arUrlSection[$i]."/";
				}
			}
			else 
			{
				$this->virtualCurDir = $arUrl["path"];
			}
		}
	}

	/**
	 * Возвращает языковый идентификатор текущей версии сайта
	 * @return string
	 */
	function GetCurrent($upperCase = false) {	return ($upperCase) ? strtoupper($this->currentLang) : $this->currentLang; }

	/**
	 * Возвращает языковый идентификатор по умолчанию
	 * @return string 
	 */
	function GetDefault() {	return $this->defaultLang; }
	
	/**
	 * Возвращает список элементов вида "идентифкатор_языка" => "путь_к_иконке"
	 * @return array
	 */
	function GetList() { return $this->listLang; }
	
	/**
	 * Возвращает дополнительный данные заданные языку
	 * 
	 * @param string $key - ключ, по которому можно получить определенный языковый параметр
	 * @param string $lang - язык, данные которого мы хотим получить
	 * @return mixed
	 */
	public function getLangData($key = false, $lang = false)
	{
		if($lang === false){
			$lang = $this->GetCurrent();
		}
		$langData = $this->listLang[$lang];
		if($key){
			return $langData[$key];
		}
		
		return $langData;
	}
	
	public function IsDefault()
	{
		return $this->GetCurrent() == $this->GetDefault();
	}
	
	/**
	 * Возвращает URL для указанного языка
	 * @param string $lang
	 * @return string
	 */
	function GetCurDirByLang($lang) 
	{
		if ($lang == $this->defaultLang)
			return $this->virtualCurDir;
		else
			return "/".$lang.$this->virtualCurDir; 
	}
	
	/**
	 * Возвращает URL для указанного языка и каталога сайта
	 * @param string $dir
	 * @param string $lang
	 * @return string
	 */
	function GetDirByLang($dir, $lang = false)
	{
		if (!$lang) $lang = $this->currentLang;
		
		if ($lang == $this->defaultLang)
			return $dir;
		else
			return "/".$lang.$dir;
	}

	function GetCurDirVirtual() { return $this->virtualCurDir; }
	
	/**
	 * Устанавливает текущий язык, может быть полезна для ajax
	 * @param string $lang
	 */
	function SetCurrent($lang){
		if(isset($this->listLang[$lang])){
			$this->currentLang = $lang;
		}
	}
}

