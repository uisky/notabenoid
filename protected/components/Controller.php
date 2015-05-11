<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Controller extends CController {
	public function filterUsersOnly($filterChain) {
		if(Yii::app()->user->isGuest) {
			throw new CHttpException(403, "Вы должны войти на сайт или зарегистрироваться, чтобы попасть на эту страницу.");
		}

		$filterChain->run();
	}

	/**
	* Макет
	*/
	public $layout='//layouts/column2';
	public $layout_layout = "v3";
	public $layoutOptions = array(
		"fluid" => false,
	);

	public $side_view = "";
	public $side_params = null;

	/**
	* Меню и области сайта
	*/
	public $siteAreas = array(
		"films"   => array("url" => '/search/?SearchFilter[typ]=S', "label" => 'ПЕРЕВОДИМ ФИЛЬМЫ'),
		"books"   => array("url" => '/search/?SearchFilter[typ]=A', "label" => 'ПЕРЕВОДИМ КНИГИ'),
		"phrases" => array("url" => '/search/?SearchFilter[typ]=P', "label" => 'ПЕРЕВОДИМ ФРАЗЫ'),
		"blog"       => array("url" => '/blog/',                    "label" => 'БЛОГ'),
		"users"      => array("url" => '/users/',                   "label" => 'ПЕРЕВОДЧИКИ'),
	);

	public $siteArea = "";

	/**
	* Реклама
	*/
	const AD_PLACE_BOTTOM = 1;
	const AD_PLACE_SIDE = 2;
	const AD_PLACE_FACE1 = 3;
	public $ad_deny = array(
		self::AD_PLACE_BOTTOM => false,
		self::AD_PLACE_SIDE => false,
		self::AD_PLACE_FACE1 => false,
	);
	private $ad_html = array(
		self::AD_PLACE_SIDE => <<<TTT
TTT
,
		self::AD_PLACE_BOTTOM => <<<TTT
TTT
,
	);
	public function ad($place_id) {
		return "<!-- A: {$place_id} -->" . $this->ad_html[$place_id];
	}

	public $breadcrumbs=array();
	public $menu = array();

	public function init() {
		parent::init();

		$user = Yii::app()->user;

		Yii::app()->clientScript
			->registerPackage("jquery")

			// я вот не ебу, остались ли где-нибудь вызовы этого богомерзкого поделия
//			->registerScriptFile("http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js")
//			->registerCssFile("/css/ui-lightness/jquery-ui-1.8.23.custom.css")

			->registerScriptFile("/js/jquery.form.js")
			->registerScriptFile("/js/jquery.cookie.js")
			->registerScriptFile("/js/global.js?16")
			->registerCssFile(Yii::app()->user->ini["t.iface"] == 1 ? "/css/v3.1.css?3" : "/css/v3.css?13")    // чтобы в конец списка CSS ёбнулось
		;

		if(!$user->isGuest) {
			Yii::app()->clientScript->registerScriptFile("/js/user.js?3");

			Yii::app()->clientScript->registerScriptFile("/js/chat.js?3");

			if($user->can("betatest")) {
				$A = Yii::app()->params["blog_topics"];
				$A["common"][70] = "Стройплощадка";
				Yii::app()->params["blog_topics"] = $A;
			}

			Yii::app()->clientScript->registerScript("user_init", "var User = new CUser({id: " . Yii::app()->user->id . ", login: '" . Yii::app()->user->login . "'});\n", CClientScript::POS_HEAD);

			// Статистика использования интерфейса
			$higgsStat = Yii::app()->cache->get("higgsStat");
			$higgsStat[$user->id] = $user->ini["t.iface"];
			Yii::app()->cache->set("higgsStat", $higgsStat);
			unset($higgsStat);
		} else {
			Yii::app()->clientScript->registerScript("user_init", "var User = new CUser({id: 0, login: 'anonymous'});\n", CClientScript::POS_HEAD);
		}

		CHtml::$afterRequiredLabel = "";

		if(Yii::app()->user->isPaid) {
			Yii::app()->clientScript->registerScriptFile("/js/paiduser.js");
		}

		if(Yii::app()->user->ini["t.iface"] == 1) Yii::app()->clientScript->registerCss("user_ini_css", Yii::app()->user->ini->getCss());
	}

	public function beforeAction($action) {
		$user = Yii::app()->user;
		if(p()["registerType"] == "INVITE") {
			if(!$user->isGuest) {
				if(!$user->model->can(User::CAN_LOGIN)) {
					$user->logout();
					Yii::app()->user->setFlash("error", "Сожалеем, но вы не член клуба.");
					$this->redirect("/");
				}

				$banned_until = Yii::app()->db
					->createCommand("SELECT until FROM ban WHERE user_id = :user_id AND until >= current_date")
					->queryScalar(array(":user_id" => Yii::app()->user->id));

				if($banned_until) {
					$user->setFlash("warning", "Вы забанены на сайте до " . Yii::app()->dateFormatter->formatDateTime($banned_until, "medium", "") . " г. включительно.");
					$user->logout();
				}
			} else {
				$freePages = [
					"site/index" => true, "register/index" => true, "register/captcha" => true,
					"register/remind" => true, "register/reset" => true, "register/done" => true,
					"site/error" => true,
				];
				if(!isset($freePages[$this->id . "/" . $this->action->id])) $this->redirect("/");
			}
		}
		return parent::beforeAction($action);
	}
}