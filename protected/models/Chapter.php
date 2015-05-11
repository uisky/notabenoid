<?php
/**
 * @property Book $book
 *
 * @property string $url via getUrl()
 * @property string $ahref via getAhref()
 * @property string deniedWhy via getDeniedWhy()
 * @property string errorsString via getErrorsString()
 */
class Chapter extends CActiveRecord {
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function tableName() {
		return 'chapters';
	}

	public $id, $book_id, $cdate, $last_tr, $n_verses = 0, $n_vars = 0, $d_vars = 0;
	public $ord, $status, $title;
	public $ac_read, $ac_trread, $ac_gen, $ac_rate, $ac_comment, $ac_tr;

	// Только для редактора
	public $has_override;

	// EXTRACT(EPOCH FROM now() - last_tr)::int as idle_time
	public $idle_time;

	const STATUS_NONE = 0;
	const STATUS_TRANSLATING = 1;
	const STATUS_EDITING = 2;
	const STATUS_READY = 3;

	public function attributeLabels() {
		return array(
			"title" => "Название",
			"ord" => "Порядок",
			"status" => "Статус"
		);
	}

	public function rules() {
		return array(
			array("title", "required", "message" => "Пожалуйста, введите заголовок"),
			array("ord", "numerical", "integerOnly" => true),
			array("status", "in", "range" => array_keys(Yii::app()->params["translation_statuses"])),
			array("ac_read, ac_trread, ac_gen, ac_rate, ac_comment, ac_tr", "filter", "filter" => "trim"),
			array("ac_read, ac_trread, ac_gen, ac_rate, ac_comment, ac_tr", "in", "range" => array("a", "g", "m", "o")),
			array("has_override", "boolean"),
			array("has_override", "rule_has_override"),
		);
	}

	public function rule_has_override() {
		if(!$this->has_override) {
			foreach(array("ac_read", "ac_trread", "ac_gen", "ac_rate", "ac_comment", "ac_tr") as $ac) $this->$ac = null;
		}
	}

	public function relations() {
		$rel = array(
			"book" => array(self::BELONGS_TO, "Book", "book_id"),
		);

		return $rel;
	}

	protected function afterValidate() {
		foreach(array_keys(Yii::app()->params["ac_areas_chap"]) as $ac) {
			if($this->$ac == "") $this->$ac = null;
		}

		// Если нам указали особые права доступа, проверяем, есть ли уже такие в переводе?
		if($this->ac_read . $this->ac_gen . $this->ac_rate . $this->ac_comment . $this->ac_tr != "") {
			$r = Yii::app()->db
				->createCommand("SELECT 1 FROM chapters WHERE book_id = :book_id AND id != :id AND (ac_read || ac_gen || ac_rate || ac_comment || ac_tr != '')")
				->query(array("book_id" => $this->book_id, "id" => $this->id));

			if($r->rowCount > 0) {
				$this->addError("ac_read", "В переводе только одна глава может иметь особые права доступа.");
			}
		}

		parent::afterValidate();
	}

	public function afterSave() {
		parent::afterSave();

		if($this->isNewRecord) {
			$this->book->n_chapters++;
			$this->book->save(false, array("n_chapters"));
		}
	}

	protected function beforeDelete() {
		// groups.n_trs, rating
		Yii::app()->db->createCommand("
			BEGIN;

			WITH effort AS (
				SELECT user_id, COUNT(*) as n, SUM(rating) as r FROM translate WHERE chap_id = :chap_id GROUP BY user_id
			)
			UPDATE groups SET n_trs = n_trs - effort.n, rating = rating - effort.r
			FROM effort WHERE groups.book_id = :book_id AND groups.user_id = effort.user_id;

			WITH effort AS (
				SELECT user_id, COUNT(*) as n, SUM(rating) as r FROM translate WHERE chap_id = :chap_id GROUP BY user_id
			)
			UPDATE users SET n_trs = n_trs - effort.n, rate_t = rate_t - effort.r
			FROM effort WHERE users.id = effort.user_id;

			COMMIT;
		")->execute(array(":chap_id" => $this->id, ":book_id" => $this->book_id));

		return parent::beforeDelete();
	}

	public function afterDelete() {
		parent::afterDelete();

		$this->book->n_chapters--;
		$this->book->n_verses -= $this->n_verses;
		$this->book->n_vars -= $this->n_vars;
		$this->book->d_vars -= $this->d_vars;

		$this->book->save(false, array("n_chapters", "n_verses", "n_vars", "d_vars"));
	}

	/**
	 * Очищает главу, пересчитывает счётчики n_verses, n_vars, d_vars в book и свои счётчики
	 */
	public function clean() {
		if($this->n_verses != 0 || $this->n_vars != 0 || $this->d_vars != 0) {
			$this->book->n_verses -= $this->n_verses;
			$this->book->n_vars -= $this->n_vars;
			$this->book->d_vars -= $this->d_vars;
			$this->book->save(false, array("n_verses", "n_vars", "d_vars"));
		}

		// translate не очистится из-за того, что foreign-ключ на translate.orig_id жутко тормозит при удалении главы
		Yii::app()->db->createCommand("DELETE FROM orig WHERE chap_id = :chap_id")->execute(array(":chap_id" => $this->id));

		$this->n_verses = 0;
		$this->n_vars = 0;
		$this->d_vars = 0;
		$this->save(false, array("n_verses", "n_vars", "d_vars"));
	}

	public function getUrl($area = "") {
		return "/book/{$this->book_id}/{$this->id}" . ($area != "" ? "/{$area}" : "");
	}

	public function getAhref($area = "") {
		return "<a href='" . $this->getUrl($area) . "'>{$this->title}</a>";
	}

	public function getReady() {
		if($this->n_verses == 0) return "&mdash;";
		if($this->d_vars == 0) return "0%";
		if($this->n_verses == $this->d_vars) return "100%";
		return sprintf("%.01f%%", floor($this->d_vars / $this->n_verses * 1000) / 10);
	}

	/**
	 * @param string $what read|trread|gen|rate|comment|tr
	 * @return bool
	 */
	public function can($what) {
		$user = Yii::app()->user;

		// Хозяин может всё
		if($this->book->owner_id == $user->id) return true;

		// Если у главы статус STATUS_READY, то нельзя переводить и рейтинговать
		if($this->status == self::STATUS_READY) {
			if($what == "rate" || $what == "tr") return false;
		}

		// Можно ли генерировать результат с подставлением оригинала вместо непереведённых фрагментов
		if($what == "gen_untr") {
			if($this->book->typ != "A") return true;
			if($this->d_vars / $this->n_verses >= 0.95) return true;
			if($this->book->ac_read != "a" || ($this->ac_read != "" && $this->ac_read != "a")) return true;
			return false;
		}

		$ac = "ac_" . $what;
		if(!$this->$ac) return $this->book->can($what);

		if($this->$ac == "a") {
			// "a" разрешает анонимам только read и gen
			if(Yii::app()->user->isGuest and !($what == "read" || $what == "gen")) return false;
			else return true;
		}

		if($this->$ac == "g") return $this->book->membership->status == GroupMember::MEMBER or $this->book->membership->status == GroupMember::MODERATOR;
		if($this->$ac == "m") return $this->book->membership->status == GroupMember::MODERATOR;
		if($this->$ac == "o") return $this->book->owner_id == $user->id;

		return false;
	}

	public function getHasOverride() {
		return $this->ac_read != "" || $this->ac_gen != "" || $this->ac_rate != "" || $this->ac_comment != "" || $this->ac_tr != "";
	}

	public function idle_time_text() {
		if($this->last_tr == "") return "&mdash;";

		$t = time() - strtotime($this->last_tr);

		if($t < 60) return $this->idle_time . " сек.";
		if($t < 60 * 60) return round($this->idle_time / 60) . " мин.";
		if($t < 60 * 60 * 24) return round($this->idle_time / 3600) . " час.";
		if($t < 60 * 60 * 24 * 30) return round($this->idle_time / 86400) . " дней.";
		if($t < 60 * 60 * 24 * 30 * 12) return round($this->idle_time / 2592000) . " мес.";
		return "> 1 года.";
	}

	/**
	* Пересчитывает n_verses, n_vars, d_vars
	* @todo: чтобы можно было передать значения счётчиков в параметре и сохранить их сразу, а не пересчитывать
	*/
	public function save_counters() {
		if($this->isNewRecord) return false;

		$this->n_verses = Yii::app()->db->createCommand("SELECT COUNT(*) FROM orig WHERE chap_id = :id")->queryScalar(array("id" => $this->id));
		list($this->n_vars, $this->d_vars) = Yii::app()->db->createCommand("SELECT COUNT(*), COUNT(DISTINCT orig_id) FROM translate WHERE chap_id = :id")->queryRow(false, array("id" => $this->id));

		$this->save(false, array("n_verses", "n_vars", "d_vars"));

		return true;
	}

	public function getErrorsString() {
		$t = "";
		foreach($this->getErrors() as $field => $errors) {
			$t .= join("\n", $errors);
		}

		return $t;
	}

	/**
	 * Возвращает текст типа "Это могут делать только члены группы. Чтобы вступить в группу, нужно подать заявку"
	 *
	 * @param string $what - роль
	 * @param bool $tools  - добавлять ли кнопки для подачи заявки или приёма инвайта в группу
	 * @return string
	 */
	public function getWhoCanDoIt($what = "read", $tools = true) {
		$ac = "ac_" . $what;

		if($this->$ac == "") return $this->book->getWhoCanDoIt($what, $tools);

		$msg = "Это " . ($this->$ac == "o" ? "может" : "могут") . " делать " . Yii::app()->params["ac_roles_title"][$this->$ac] . ".";

		if($this->$ac == "g") {
			if($this->book->facecontrol == Book::FC_CONFIRM) {
				$msg .= "Чтобы вступить в группу, нужно подать заявку владельцу ({$this->book->owner->ahref})" . ($this->book->ac_membership == "m" ? " или модераторам" : "") . ".";
				if($tools) $msg .= Yii::app()->controller->renderPartial("//book/_join", array("book" => $this->book), true);
			} elseif($this->book->facecontrol == Book::FC_INVITE) {
				$msg = "Чтобы вступить в группу, нужно получить приглашение от владельца ({$this->book->owner->ahref})" . ($this->book->ac_membership == "m" ? " или модераторов" : "") . ".";
				if($tools && $this->book->user_invited(Yii::app()->user->id)) {
					$msg .= " Кстати, у вас это приглашение есть.<br /><br /><a href='" . $this->book->getUrl("invite_accept") . "' class='act'>Принять</a> | <a href='" . $this->book->getUrl("invite_decline") . "' class='act'>Отказать</a>";
				}
			}
		}

		return $msg;
	}

	public function getDeniedWhy() {
		if($this->can("read")) return "";

		if($this->ac_read == "o") $msg = "Владелец перевода (" . $this->book->owner->ahref . ") закрыл доступ в эту главу для всех.";
		elseif($this->ac_read == "m") $msg = "Эта глава доступна только модераторам этого перевода, которых назначает владелец (" . $this->book->owner->ahref . ").";
		elseif($this->ac_read == "g") {
			if($this->book->facecontrol == Book::FC_CONFIRM) {
				$msg = "Чтобы войти в эту главу перевода, нужно вступить в группу перевода.";
			} elseif($this->book->facecontrol == Book::FC_INVITE) {
				$msg = "Чтобы войти в эту главу перевода, нужно получить приглашение от владельца (" . $this->book->owner->ahref . ")" . ($this->book->ac_membership == "m" ? " или модераторов" : "") . ".";
			}
		}
		else $msg = "Вы не можете войти в эту главу перевода.";

		return $msg;
	}

	/**
	 * Добавляет в javascript-код страницы инициализацию объекта Chap
	 */
	public function registerJS($varName = "Chap") {
		$js = "var {$varName} = {\n";
		foreach(array("id", "book_id", "n_verses", "n_vars", "d_vars") as $k) {
			$js .= "\t{$k}: " . intval($this->$k) . ",\n";
		}
		foreach(array("title", "ac_read", "ac_gen", "ac_rate", "ac_comment", "ac_tr") as $k) {
			$js .= "\t{$k}: '" . addcslashes($this->$k, "\t\r\n'\"") . "',\n";
		}
		$js .= "};\n";

		Yii::app()->getClientScript()->registerScript("book_" . $varName, $js, CClientScript::POS_HEAD);
	}

	public function setModified($val = null) {
		Yii::app()->cache->set("cm" . $this->id, $val === null ? time() : $val);
	}

	public function getModified() {
		return Yii::app()->cache->get("cm" . $this->id);
	}
}
?>
