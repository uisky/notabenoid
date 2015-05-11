<?php
/**
 * @property Book[] $books
 * @property int $n_invites
 * @property string $ahref, $url
 */
class User extends CActiveRecord {
	/**
	 * @static
	 * @param string $className
	 * @return User
	 */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function tableName() {
		return 'users';
	}
	public function primaryKey() { return "id"; }

	const CAN_LOGIN    = 0;
	const CAN_RATE     = 1;
	const CAN_COMMENT  = 2;
	const CAN_PMAIL    = 3;
	const CAN_POST     = 4;
	const CAN_MODERATE = 7;

	const CAN_TRANSLATE    = 8;
	const CAN_CREATE_BOOKS = 9;
	const CAN_ANNOUNCE     = 10;

	const INI_MYTALKS_NEW   = 0;  // в "моих обсуждениях" показывать посты только с новыми комментариями
	const INI_INBOX_NEW     = 1;  // в списке инбоксов показывать только инбоксы с новыми комментариями

	const INI_MAIL_NOTICES  = 4;  // слать на почту уведомления
	const INI_MAIL_PMAIL    = 5;  // слать на почту личные сообщения
	const INI_MAIL_COMMENTS = 6;  // слать на почту ответы на посты и комментарии
	const INI_MAIL_NEWS     = 7;  // присылать новости сайта

	const INI_AD_OFF		= 8;
	const INI_ADDTHIS_OFF   = 9;

	public $id, $cdate, $lastseen, $can;
	public $login, $pass, $email, $sex, $lang, $upic, $ini;
	public $rate_t, $rate_c, $rate_u, $n_trs, $n_comments, $n_karma;

	private $_identity;

	// on => "login"
	public $remember = true;

	// on => "register"
	public $verifyCode, $pass2, $tos;

	// используется в BookController::actionMembers при выводе очереди в группу
	public $q_cdate, $q_message;

	// BookController::actionMembers $invited_dp
	public $from_uid, $from_login;

	public function attributeLabels() {
		return array(
			"verifyCode" => "Код проверки",
			"login" => "Логин",
			"pass" => "Пароль",
			"email" => "E-mail",
			"sex" => "Пол",
			"lang" => "Родной язык",
			"tos" => "Я прочитал <a href='/site/TOS/'>Правила использования сервиса</a> и согласен с ними.",

			'id' => 'ID',
			'cdate' => 'Зарегистрирован',
			'lastseen' => 'Последний раз был на сайте',
			'can' => 'Права',
			'upic' => 'Аватар',
			'ini' => 'Настройки',
			'rate_t' => 'Рейтинг переводов',
			'rate_c' => 'Рейтинг комментариев',
			'rate_u' => 'Карма',
			'n_trs' => 'Версий перевода',
			'n_comments' => 'Комментариев',

			"pass2" => "Ещё раз",
			"remember" => "Запомнить меня на сайте",
		);
	}

	public function rules() {
		return array(
			// on => "login"
			array('login, pass', 'required', "on" => "login", "message" => "Пожалуйста, введите {attribute}"),
			array('remember', 'boolean', "on" => "login"),

			// on => "register"
			array("login, pass, email, sex, lang", "required", "message" => "Укажите {attribute}.", "on" => "register"),
			array("login, email", "filter", "filter" => "trim", "on" => "register"),
			array("login", "length", "min" => 2, "max" => 16, "tooShort" => "Слишком короткий логин", "tooLong" => "Слишком длинный логин", "on" => "register"),
			array('login', 'match', 'pattern' => '/^[A-Za-z\d_]+$/', "message" => "Недопустимый символ", "on" => "register"),
			array("login", "unique", "caseSensitive" => false, "message" => "Такой логин уже используется, придумайте другой.", "on" => "register"),
			array("pass, pass2", "length", "min" => 5, "max" => 32, "tooShort" => "Слишком короткий пароль", "tooLong" => "Слишком длинный пароль", "on" => "register"),
			array("pass", "compare", "compareAttribute" => "pass2", "message" => "Пароли не совпадают.", "on" => "register"),
			array("pass2", "safe", "on" => "register"),
			array("email", "length", "max" => 255, "tooLong" => "Слишком длинный адрес электронной почты", "on" => "register"),
			array("email", "email", "checkPort" => false, "message" => "Неверный адрес электронной почты.", "on" => "register, edit-admin"),
			array("email", "unique", "caseSensitive" => false, "message" => "Пользователь с таким адресом электронной почты уже зарегистрирован.", "on" => "register, edit-admin"),
			array("sex", "in", "range" => array("m", "f"), "message" => "Вы должны быть либо мужчиной, либо, ещё лучше, женщиной.", "on" => "register"),
			array("sex", "in", "range" => array("m", "f", "x", "-"), "message" => "Пол - это m, f, x или дефис.", "on" => "edit-admin"),
			array("lang", "numerical", "integerOnly" => true, "min" => 1, "message" => "Этот язык нам, увы, неизвестен.", "on" => "register"),
			array("verifyCode", "captcha", "message" => "Вы неправильно ввели буквы с картинки.", "on" => "register"),
			array("tos", "compare", "compareValue" => "1", "message" => "Вы должны прочитать и согласиться с правилами.", "on" => "register"),

			array("ini", "type", "type" => "array", "on" => "settings"),

			["n_invites", "numerical", "on" => "edit-admin"],
		);
	}

	public function relations() {
		return array(
			"books"      => array(self::HAS_MANY, "Book",        "owner_id"),
			"userinfo"   => array(self::HAS_MANY, "Userinfo",    "user_id"), // , "order" => "prop_id", "select" => "prop_id, value"
			"membership" => array(self::HAS_ONE,  "GroupMember", "user_id"),
			"invitedBy" => array(self::BELONGS_TO,  "User", "invited_by"),
		);
	}

	public function membership($book_id) {
		$this->getDbCriteria()->mergeWith(array(
			"with" => array("membership" => array("on" => "membership.book_id = {$book_id}")),
		));

		return $this;
	}

	public function members_of($book_id) {
		$this->getDbCriteria()->mergeWith(array(
			"with" => array("membership" => array("condition" => "membership.book_id = {$book_id}")),
		));

		return $this;
	}

	public function moderators($book_id) {
		$book_id = (int) $book_id;
		$c = $this->getDbCriteria();
		$c->join = "RIGHT JOIN groups ON t.id = groups.user_id";
		$c->addCondition("groups.book_id = {$book_id} AND groups.status = " . GroupMember::MODERATOR);
		return $this;
	}

	public function byLogin($login) {
		$this->getDbCriteria()->mergeWith(array(
			"condition" => "LOWER(login) = :login",
			"params" => array(":login" => trim(strtolower($login))),
		));
		return $this;
	}

	public function watchers($book_id) {
		$this->dbCriteria->mergeWith([
			"join" => "RIGHT JOIN bookmarks b ON b.user_id = t.id",
			"condition" => "b.book_id = :book_id AND b.watch AND b.orig_id IS NULL",
			"params" => [":book_id" => $book_id]
		]);
		return $this;
	}

	/**
	 * Хеширует пароль
	 * @param $value
	 * @return string
	 */
	public static function hashPass($value) {
		return md5($value . Yii::app()->params["passwordSalt"]);
	}

	/**
	* Если $this->ini не массив, то распаковывает его в массив
	*/
	private function bits_unpack() {
		if(!is_array($this->ini)) {
			$this->ini = str_split(strrev($this->ini));
		}
		if(!is_array($this->can)) {
			$this->can = str_split(strrev($this->can));
		}
	}

	private function bits_pack() {
		if(is_array($this->ini)) {
			$this->ini = strrev(join("", $this->ini));
		}
		if(is_array($this->can)) {
			$this->can = strrev(join("", $this->can));
		}
	}

	protected function afterFind() {
		if(!is_array($this->upic)) $this->upic = sscanf($this->upic, "{%d,%d,%d}");
		$this->bits_unpack();

		parent::afterFind();
	}

	protected function beforeSave() {
		if(!parent::beforeSave()) return false;

		if($this->isNewRecord) {
			$this->pass = self::hashPass($this->pass);
		}

		 $this->bits_pack();
		 if(is_array($this->upic)) $this->upic = "{" . join(",", $this->upic) . "}";

		return true;
	}

	protected function afterSave() {
		// Распаковываем ini, который запаковался в int в beforeSave()
		$this->bits_unpack();
	}

	public function login() {
		if($this->_identity === null) {
			$this->_identity = new UserIdentity($this->login, $this->pass);
			$this->_identity->authenticate();
		}

		if($this->_identity->errorCode === UserIdentity::ERROR_NONE) {
			$duration = $this->remember ? 3600*24*365 : 0; // 365 days
			Yii::app()->user->login($this->_identity, $duration);
			return true;
		} elseif($this->_identity->errorCode == UserIdentity::ERROR_USER_DELETED) {
			$this->addError("pass", "Ваш аккаунт удалён.");
		} elseif($this->_identity->errorCode == UserIdentity::ERROR_USER_INACTIVE) {
			$this->addError("pass", "Логин и пароль верны, однако, вы не член клуба. Чтобы попасть внутрь, вам нужно получить приглашение от члена клуба. ВНИМАНИЕ! Не надо писать в техподдержку, пожалуйста.");
		} else {
			$this->addError("pass", "Нет, неправильно.");
		}

		return false;
	}

	public function can($what) {
		return $this->can[$what];
	}

	public function getIsDeleted() {
		return $this->sex == "-";
	}

	public function ini_get($pos) {
		return $this->ini[$pos];
	}

	public function ini_set($pos, $val = 1, $save = true) {
		$this->ini[$pos] = $val;
		if($save) $this->save(false, ["ini"]);
	}

	public function can_set($pos, $val = 1) {
		$this->can[$pos] = $val;
	}

	public function sexy($m = "", $f = "а", $x = "о") {
		return $this->sex == "f" ? $f : ($this->sex == "x" ? $x : $m);
	}

	public function getRate_tFormatted() {
		return str_replace("-", "&minus;", $this->rate_t);
	}

	public function getRate_uFormatted() {
		return str_replace("-", "&minus;", $this->rate_u);
	}

	public function url($area = "") {
		Yii::log("DEPRECATED FUNCTION User::url()", "warning");
		return "/users/" . $this->id . ($area != "" ? "/{$area}" : "");
	}

	public function getUrl($area = "") {
		return "/users/{$this->id}" . ($area != "" ? "/{$area}" : "");
	}

	public function getAhref($area = "") {
		$class = "user";
		if(!$this->can(self::CAN_LOGIN)) $class .= " user-inactive";
		if($this->isDeleted) $class .= " user-deleted";
		return "<a href='" . $this->getUrl($area) . "' class='{$class}'>" . $this->login . "</a>";
	}

	protected function getUpicName() {
		return "{$this->id}-{$this->upic[0]}";
	}

	public function getUpicDir() {
		return $_SERVER["DOCUMENT_ROOT"] . "/i/upic/" . floor($this->id / 1000);
	}

	public function getUpicPath() {
		if($this->upic[0] == 0) return "";
		return $this->upicDir . "/{$this->upicName}.jpg";
	}

	public function getUpicPathBig() {
		if($this->upic[0] == 0) return "";
		return $this->upicDir . "/{$this->upicName}_big.jpg";
	}

	public function getUpicUrl() {
		if($this->upic[0] == 0) return "/i/upic/0.jpg";
		return "/i/upic/" . floor($this->id / 1000) . "/" . $this->upicName . ".jpg";
	}

	public function getUpicUrlBig() {
		if($this->upic[0] == 0) return "";
		return "/i/upic/" . floor($this->id / 1000) . "/" . $this->upicName . "_big.jpg";
	}

	public function upicUnlink() {
		if($this->upic[0] == 0) return false;
		@unlink($this->upicPath);
		@unlink($this->upicPathBig);
		$this->upic = array(0, 0, 0);
		return true;
	}

	public function upicCheckDir() {
		if(!is_dir($this->upicDir)) mkdir($this->upicDir);
	}

	/**
	 * Эта адова хуйня отправляет оповещение пользователю. Кто-то в будущем должен задуматься о её разумности.
	 * @param int $typ тип оповещения, Notice::*
	 * @param mixed $param1 зависит от типа оповещения, обычно - Book
	 * @param mixed $param2 зависит от типа, обычно - User, если null - то текущий юзер
	 * @param mixed $param3 зависит от типа
	 * @return bool
	 */
	public function Notify($typ, $param1, $param2 = null, $param3 = null) {
		if($this->isDeleted) {
			return true;
		}

		$Notice = new Notice();
		$Notice->typ = $typ;
		$Notice->user_id = $this->id;
		$msg = "";
		switch($typ) {
			case Notice::INVITE:
			case Notice::JOIN_REQUEST:
				if($param2 === null) $param2 = Yii::app()->user;
				$msg = "{$param1->id}\n{$param1->fullTitle}\n{$param2->id}\n{$param2->login}";
				break;
			case Notice::JOIN_ACCEPTED:
			case Notice::JOIN_DENIED:
			case Notice::EXPELLED:
			case Notice::BANNED:
			case Notice::UNBANNED:
			case Notice::CROWNED:
			case Notice::DEPOSED:
				$msg = "{$param1->id}\n{$param1->fullTitle}";
				break;
			case Notice::CHAPTER_ADDED:
				$msg = "{$param1->id}\n{$param1->fullTitle}\n{$param2->id}\n{$param2->title}";
				break;
			case Notice::CHAPTER_STATUS:
				$msg = "{$param1->id}\n{$param1->fullTitle}\n{$param2->id}\n{$param2->title}\n{$param3}";
				break;
			default:
				$msg = $param1;
		}
		$Notice->msg = $msg;
		if(!$Notice->save()) return false;

		// Уведомление по мылу, если надо
		if($this->ini_get(self::INI_MAIL_NOTICES)) {
			$subj = array(
				Notice::INVITE => "Приглашение в группу перевода",
				Notice::JOIN_REQUEST => "Заявка на вступление в группу перевода",
				Notice::JOIN_ACCEPTED => "Заявка принята",
				Notice::JOIN_DENIED => "Заявка отклонена",
				Notice::EXPELLED => "Исключение из группы",
				Notice::BANNED => "Бан",
				Notice::UNBANNED => "Бан снят",
				Notice::CROWNED => "Назначение модератором",
				Notice::DEPOSED => "Лишение модераторских полномочий",
				Notice::CHAPTER_ADDED => "Новая глава в переводе, за которым вы следите",
				Notice::CHAPTER_STATUS => "Изменился статус перевода, за которым вы следите"
			);
			$msg = new YiiMailMessage($subj[$Notice->typ]);
			$msg->view = "notice";
			$msg->setFrom(array(Yii::app()->params["systemEmail"] => "Оповещение"));
			$msg->addTo($this->email);
			$msg->setBody(array("Notice" => $Notice, "user" => $this), "text/html");
			Yii::app()->mail->send($msg);
		}

		return true;
	}
}
