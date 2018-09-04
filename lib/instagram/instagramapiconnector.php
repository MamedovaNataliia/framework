<?php
/**
 * Created by PhpStorm.
 * User: pc
 * Date: 8/7/2017
 * Time: 4:48 PM
 */

namespace Aniart\Main\Instagram;
use \Aniart\Main\Instagram\Instagram;

/*
 * Синглтон
 */
class InstagramAPIConnector
{
	private static $instance = null;
	private static $config = array();

	/*
	 * Ниже задаются количество постов, которые можно будет загрузить. Количество загруженых постов может быть от 22 до 33,
	 * для того, чтобы получить больше постов нужно пользоватся пагинацией.
	 * https://rudrastyh.com/javascript/instagram-more-than-33.html - ссылка на статью про пагинацию.
	 */
	private $numberOfImages = 22;
	private $instagram = NULL;

	/*
	 * Здесь задаются параметры для работы с API.
	 * Для работы с API нужно зарегестрировать разработчика на https://www.instagram.com/developer/ , потом,
	 * в Manage Clients нужно добавить нового клиента, где будут дано CLIENT_ID и указано REDIRECT_URI.
	 * Чтобы можно было брать данные с API, нужно сгенерировать ACCESS_TOKEN, для етого нужно вставить ссылку в браузер
	 * и задать CLIENT_ID и REDIRECT_URI, потом перейти по ссылке и будет сгенерирован ACCESS_TOKEN
	 * https://instagram.com/oauth/authorize/?client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&response_type=token
	 * В user_id задается ID пользователя, в которого будут братся данные. В данном случае я зарегестрировал заказчика, как
	 * разработчика и указал ID как self - ето значит что будут выгружатся данные из зарегестрированого пользователя,
	 * если нужно взять в какого-то другого пользователя, то нужно указать его ID профиля в INSTAGRAMM.
	 */
	private function __construct() {
		self::$config["client_id"] = "28999d3b888844b1babd21a2117df762";
		self::$config["client_secret"] = "e67159f3043348a3942321869542e57d";
		self::$config["grant_type"] = "authorization_code";
		$serverName = $_SERVER['SERVER_NAME'];
		self::$config["redirect_uri"] = "'http://'.$serverName.'test'";
		self::$config["access_token"] = "327567107.28999d3.f056c3c691a84de5b643f1fe8499d0cc";
		self::$config["code"] = "";
		self::$config["user_id"] = "self";

		$this->instagram = new Instagram(self::$config);
		$this->instagram->setAccessToken(self::$config["access_token"]);
	}
	
	public static function getInstance() {
		if(!self::$instance)
			self::$instance = new InstagramAPIConnector();

		return self::$instance;
	}

	/*
	 * Вывод данных из апи.
	 */
	public function getMedia() {
		return $this->instagram->getUserRecent(self::$config["user_id"], "", "", "",
			"", $this->numberOfImages);
	}
}