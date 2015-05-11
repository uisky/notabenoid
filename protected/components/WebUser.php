<?php
class WebUserIni implements ArrayAccess, Serializable {
	const COOKIE_NAME = "ini";
	private $_modified = false;

	/* Добавляя новые свойства, обязательно добавить их в global.js CUser() */
	private $ini = array(
		"hot.img" => 1,
		"hot.s_lang" => 0,
		"hot.t_lang" => 0,

		"l.bgcolor" => "ffffff",
		"l.color" => "000000",
		"l.fontsize" => 13,
		"l.lineheight" => 18,
		"l.metascheme" => 0,

		"t.iface" => 1,
		"t.hlr" => 1,
		"t.oe_hide" => 1,
		"t.dict" => 0,
		"t.textfontsize" => 13,
		"t.copy" => 0,

		"c.sc" => 0,	// схема выделения новых комментариев

		"chat.on" => 0,		// Открыт ли чат
		"chat.h" => 300,	// Высота окна чата

		"poll.done" => 0,

		"blog.topics" => "",
	);

	public static $newCommentsSchemes = array(
		0 => array("_title" => "Тэд Мосби",          "backGround" => "#AAD7EE", "metaColor" => "#656B6A"),
		1 => array("_title" => "Магистр Йода",       "backGround" => "#C7C77A", "metaColor" => "#6B664C"),
		4 => array("_title" => "Мистер Бин",         "backGround" => "#cccccc", "metaColor" => "#777777"),
		3 => array("_title" => "Дейнерис Таргариен", "backGround" => "#FFFFBF", "metaColor" => "#B5AB77"),
		2 => array("_title" => "Константин Кинчев",	 "backGround" => "#38100A", "textColor" => "#f00", "metaColor" => "#920000"),
	);

	public function getCss() {
		$css = "";

		$css .= <<<TTT
body {
	background-color: #{$this->ini["l.bgcolor"]};
	color: #{$this->ini["l.color"]};
}
body, label, input, button, select, textarea {
	font-size: {$this->ini["l.fontsize"]}px;
	line-height: {$this->ini["l.lineheight"]}px;
}
#chat-box {
	height: {$this->ini["chat.h"]}px;
}
TTT;

	// Цвета .meta: [обычный цвет, :hover]
	if($this->ini["l.metascheme"] == 1) $metaColors = ["#888", "#888"];
	elseif($this->ini["l.metascheme"] == 2) $metaColors = ["#888", "#000"];
	elseif($this->ini["l.metascheme"] == 3) $metaColors = ["#000", "#000"];
	else $metaColors = ["#bbb", "#888"];

		$css .= <<<TTT
.comments .comment .meta, .comments .comment .meta a, .translator .info, .translator .info a { color: {$metaColors[0]}; }
.comments .comment:hover .meta, .comments .comment:hover .meta a, .translator tr:hover .info, .translator tr:hover .info a { color: $metaColors[1]; }
TTT;

		if($this->ini["c.sc"] != 0) $css .= $this->getCssComments($this->ini["c.sc"]);

		return $css;
	}

	public function getCssComments($scheme_id, $containerClass = ".comments") {
		$css = "";

		$scheme = self::$newCommentsSchemes[$scheme_id];
		if(isset($scheme["backGround"])) $css .= "{$containerClass} .comment.new {background-color:{$scheme["backGround"]}}\n";
		if(isset($scheme["metaColor"]))  $css .= "{$containerClass} .comment.new .meta, {$containerClass} .comment.new .meta a {color:{$scheme["metaColor"]}}\n{$containerClass} .comment.new a.ajax { border-bottom-color:{$scheme["metaColor"]}}\n";
		if(isset($scheme["textColor"]))  $css .= "{$containerClass} .comment.new {color:{$scheme["textColor"]}}\n";

		return $css;
	}


	public function __construct() {
		if(isset(Yii::app()->request->cookies[self::COOKIE_NAME])) {
			$cookie = Yii::app()->request->cookies[self::COOKIE_NAME]->value;
			$prefix = substr($cookie, 0, 2);
			if($prefix == "2@") {
				$this->unserialize($cookie);
			} else {
				// Старый формат кукиса, парсим, сохраняем в новом формате
				$ini = unserialize(base64_decode($cookie));
				if(is_array($ini)) foreach($ini as $k => $v) {
					$this[$k] = $v;
				}
				$this->save();
			}
		};
	}

	public function serialize() {
		$cookie = "2@";
		foreach($this->ini as $k => $v) {
			if($cookie != "2@") $cookie .= "\n";
			$cookie .= $k . "\t" . $v;
		}
		return $cookie;
	}

	public function unserialize($text) {
		$A = explode("\n", substr($text, 2));
		if(!is_array($A)) return false;
		foreach($A as $pair) {
			list($k, $v) = explode("\t", $pair, 2);
			$this[$k] = $v;
		}
		return true;
	}

	public function offsetGet($offset) {
		return $this->ini[$offset];
	}
	public function offsetSet($key, $value) {
		if(!isset($this->ini[$key])) return;

		$this->ini[$key] = $this->clean($key, $value);
		$this->_modified = true;
	}
	public function offsetExists($offset) {
		return isset($this->ini[$offset]);
	}
	public function offsetUnset($offset) {
		unset($this->ini[$offset]);
	}


	public function clean($key, $value) {
		if(in_array($key, ["l.bgcolor", "l.color"])) {
			sscanf($value, "%x", $v);
			return sprintf("%06x", $v);
		} elseif(in_array($key, ["blog.topics"])) {
			return trim(strip_tags($value));
		}
		return (int) $value;
	}

	public function set($key, $value) {
		$this[$key] = $value;
	}

	public function save() {
//		if(!$this->_modified) return;

		$cookie = new CHttpCookie(self::COOKIE_NAME, $this->serialize());
		$cookie->expire = mktime(0, 0, 0, 8, 8, 2037);
		$cookie->path = "/";
		Yii::app()->request->cookies[self::COOKIE_NAME] = $cookie;

		$this->_modified = false;
	}

	public function dump() {
		echo "<pre>";
		foreach($this->ini as $k => $v) {
			echo "{$k} = '{$v}'\n";
		}
		echo "</pre>";
	}
}

class WebUser extends CWebUser {
	private $_model = null;
	private $newNotices = null;
	private $newComments = null;
	private $newMail = null;

	public $ini;

	public function init() {
		parent::init();

		$this->ini = new WebUserIni();
	}

	public function getModel() {
		if($this->isGuest) return null;
		if($this->_model === null) $this->_model = User::model()->findByPk($this->id);
		return $this->_model;
	}

	public function dump_model() {
		$m = $this->getModel();
		if(!$m) return "NULL";
		return print_r($m->attributes, true);
	}

	public function getUrl($area = "") {
		return "/users/" . $this->id . ($area != "" ? "/{$area}" : "");
	}
	public function getAhref($area = "") {
		return "<a href='" . $this->getUrl($area) . "' class='user'>" . $this->login . "</a>";
	}

	public function ini_get($field) {
		return $this->getModel()->ini_get($field);
	}

	public function ini_set($field, $value = 1) {
		return $this->getModel()->ini_set($field, $value);
	}

	private static $roles = [
		// Модераторы блога
		"blog_moderate" => ["notabenoid" => 1],
		"blog_topic_moderate" => ["notabenoid" => 1, "wishera" => 1, "Jolka" => 1],

		// Модераторы каталога
		"cat_moderate" => ["notabenoid" => 1, "wishera" => 1],

		// Бета-тестеры новых хуен
		"betatest" => [
			"notabenoid" => 1, "kostya_testing" => 1, "iimuhin" => 1, "parfenov" => 1,
			"bbn" => 1, "tetka" => 1, "splinterx" => 1, "jolka" => 1, "sun_eyed_girl" => 1,
			"geragod" => 1, "aist" => 1, "cepylka" => 1, "izolenta" => 1, "xandra" => 1,
			"esperanza" => 1, "wishera" => 1, "vipere" => 1, "truetranslate" => 1, "stylesmile" => 1,
			"caranemica" => 1, "peritta" => 1, "2be_real" => 1, "nikopol" => 1, "z23" => 1,
			"freex25" => 1, "julias" => 1, "alex_ander" => 1, "podruga" => 1, "sorc" => 1,
			"svetyska" => 1, "nataliya" => 1, "macymissa" => 1, "vovka" => 1, "mih83" => 1,
			"molli" => 1, "zlae4ka" => 1, "monster" => 1, "blanes" => 1, "lisok" => 1,
			"veste" => 1, "chudoyudo" => 1, "vitalogy" => 1, "luizot" => 1, "merrzavka" => 1,
			"_highflyer" => 1, "fucshia" => 1, "d722tar" => 1, "quatra" => 1, "valentinakorea" => 1,
			"nnn99" => 1, "thesam" => 1, "nd404" => 1, "knoppka" => 1, "mydimka" => 1,
			"antoniolagrande" => 1, "aredhel" => 1, "prestige1905" => 1, "kpymo" => 1, "18061987" => 1,
			"grenada" => 1, "parabashka" => 1, "lori2014" => 1, "doe" => 1,
		],

		// Программисты, сисадмины - доступ к технической информации и её редактирование
		"geek" => ["notabenoid" => 1],

		// Суперадмины
		"admin" => ["notabenoid" => 1],
	];

	public function can($role) {
		if(Yii::app()->user->isGuest) return false;

		if(is_int($role)) return $this->model->can($role);

		if($role == "karma") {
			return (time() - strtotime($this->model->cdate)) / (60*60*24) > 180;
		}

		return (bool) self::$roles[$role][strtolower($this->login)];
	}

	public static function getRoles($role) {
		return array_keys(self::$roles[$role]);
	}

	public function accessFilterUsers($role) {
		if(!isset(self::$roles[$role])) return array();
		return array_keys(self::$roles[$role]);
	}

	public function getIsPaid() {
		if($this->id == 1) return true;
		else return false;
	}

	public function getNewComments() {
		if($this->isGuest) return 0;

		if($this->newComments === null) {
			$this->newComments = Yii::app()->db->createCommand("
				SELECT
					COALESCE(SUM(o.n_comments),0) + COALESCE(SUM(p.n_comments),0) - COALESCE(SUM(seen.n_comments),0) as new_comments
				FROM seen
					LEFT JOIN blog_posts as p ON seen.post_id = p.id
					LEFT JOIN orig as o ON seen.orig_id = o.id
				WHERE
					seen.user_id = :user_id AND seen.track = true
			")->queryScalar(array(":user_id" => $this->id));
		}

		return $this->newComments;
	}

	public function getNewNotices() {
		if($this->isGuest) return 0;

		if($this->newNotices === null) {
			$this->newNotices = Yii::app()->db->createCommand("SELECT COUNT(*) FROM notices WHERE user_id = :user_id AND not seen")->queryScalar(array(":user_id" => $this->id));
		}

		return $this->newNotices;
	}

	public function getNewMail() {
		if($this->isGuest) return 0;

		if($this->newMail === null) {
			$this->newMail = Yii::app()->db->createCommand("SELECT COUNT(*) FROM mail WHERE user_id = :user_id AND folder = :folder AND not seen")
				->queryScalar(array(":user_id" => $this->id, "folder" => Mail::INBOX));
		}

		return $this->newMail;
	}
}
