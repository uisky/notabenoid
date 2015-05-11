<?php
/**
 * @property integer $id
 * @property integer $chap_id
 * @property integer $ord
 * @property string $t1
 * @property string $t2
 * @property string $body
 * @property integer n_comments
 *
 * @property Chapter $chap
 * @property Book $book
 * @property Translation[] $trs
 */
class Orig extends CActiveRecord {
	/** @returns Orig */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function tableName() {
		return "orig";
	}

	public $id, $chap_id, $ord, $t1, $t2, $body;

	public function rules() {
		return array(
			array("t1, t2", "validateTiming", "on" => "edit_S"),
			array("ord", "numerical", "integerOnly" => true, "on" => "edit_A, edit_S"),
			array("body", "validateBody"),
		);
	}

	public function validateTiming($attr, $params) {
		if($this->$attr === null) return;

		$this->$attr = trim($this->$attr);
		if(!preg_match('/^\d+:\d+:\d+([,.]\d+)?$/', $this->$attr)) {
			$this->addError($attr, "Пожалуйста, введите время " . ($attr == "t1" ? "начала" : "конца") . " субтитра в формате ЧЧ:ММ:СС.ммм (где ммм - миллисекунды)");
		}
		$this->$attr = strtr($this->$attr, ",", ".");
	}

	public function validateBody($attr, $params) {
		if($this->chap->book->typ == "S") $this->body = trim($this->body);
		if($this->body == "") $this->body = " ";
//		return $this->$attr;
//		$this->$attr = trim(strip_tags($this->$attr));
	}

	public function relations() {
		$rel = array(
			"chap"         => array(self::BELONGS_TO, "Chapter",     "chap_id"),
			"book"         => array(self::BELONGS_TO, "Book",        "book_id"),
			"trs" 		   => array(self::HAS_MANY,   "Translation", "orig_id")
		);
		if(!Yii::app()->user->isGuest) {
			$rel["seen"] = array(
				self::HAS_ONE, "SeenOrig", "orig_id",
				"select" => array("seen", "n_comments", "track"),
				"on" => "seen.user_id = " . intval(Yii::app()->user->id),
			);
			$rel["bookmark"] = array(
				self::HAS_ONE, "Bookmark", "orig_id", "on" => "bookmark.user_id = " . Yii::app()->user->id
			);
		} else {
			// дешёвый трюк, расчитанный на то, что планировщик postgresql заметит, что seen.user_id NOT NULL и не будет ничего джойнить вообще
			// нужно как-то изящнее тут поступить.
			$rel["seen"] = array(
				self::HAS_ONE, "SeenOrig", "post_id",
				"select" => array("seen", "n_comments", "track"),
				"on" => "seen.user_id IS NULL",
			);
			$rel["bookmark"] = array(
				self::HAS_ONE, "Bookmark", "orig_id", "on" => "bookmark.user_id IS NULL"
			);
		}
		return $rel;
	}

	public function chapter($id) {
		$this->getDbCriteria()->mergeWith(array(
			"condition" => "t.chap_id = " . intval($id),
		));

		return $this;
	}

	// мои обсуждения ($new_only: только с новыми комментариями)
	public function x_expired_talks($new_only = false) {
		$c = new CDbCriteria();
		$c->join = "RIGHT JOIN seen seen_my ON seen_my.orig_id = t.id";		// второй раз джойним seen, первый раз - через with
		$c->addCondition("seen_my.user_id = '" . intval(Yii::app()->user->id) . "' AND seen_my.track");
		if($new_only) $c->addCondition("t.n_comments - COALESCE(seen_my.n_comments, 0) != 0");
		$c->order = "seen_my.seen desc";

		$this->getDbCriteria()->mergeWith($c);

		return $this;
	}

	public function talks($new_only = false) {
		$c = $this->dbCriteria;

		$c->with = [
			"chap.book",
			"seen" => ["joinType" => "RIGHT JOIN", "on" => ""]
		];

		$c->addCondition("seen.user_id = '" . intval(Yii::app()->user->id) . "' AND seen.track");
		if($new_only) $c->addCondition("t.n_comments - COALESCE(seen.n_comments, 0) != 0");

		$c->order = "seen.seen desc";

		return $this;
	}


	/**
	 * Инициализирует новый фрагмент в главе: для субтитров считает тайминг, для текста - ord
	 */
	public function initNew($after_id = 0) {
		$after = false;
		if($after_id) {
			$after = Orig::model()->findByPk((int) $_GET["after"], "chap_id = :chap_id", array(":chap_id" => $this->chap_id));
		}

		if($this->chap->book->typ == "S") {
			if($after) {
				$t1 = $after->mstime("t2") + 1;

				$next = Orig::model()->find(array(
					"condition" => "chap_id = :chap_id AND t1 >= :t1 AND id != :id",
					"params" => array(":chap_id" => $this->chap_id, ":t1" => $after->t1, ":id" => $after->id),
					"order" => "t1"
				));

				if($next) {
					$t2 = $next->mstime("t1") - 1;
				} else {
					$t2 = $after->mstime("t2") + 2001;
				}

				if($t2 < $t1) $t2 = $t1;

				$this->t1 = Orig::ms2std($t1);
				$this->t2 = Orig::ms2std($t2);
			} else {
				list($this->t1, $this->t2) = Yii::app()->db->createCommand("SELECT COALESCE(MAX(t2) + interval '0:0:1', '0:0:0') as t1, COALESCE(MAX(t2) + interval '0:0:2', '0:0:1') as t2 FROM orig WHERE chap_id = :chap_id")->queryRow(false, array(":chap_id" => $this->chap_id));
			}
		} else {
		}

		if($after) {
			$this->ord = $after->ord + 1;
		} else {
			$this->ord = Yii::app()->db->createCommand("SELECT COALESCE(MAX(ord), 0) + 1 FROM orig WHERE chap_id = :chap_id")->queryScalar(array(":chap_id" => $this->chap_id));
		}
	}

	protected function afterDelete() {
		// Так как у нас нет foreign-ключа на translate.orig_id из-за соображений производительности, удаляем переводы вручную
		Yii::app()->db->createCommand("DELETE FROM translate WHERE orig_id = :id")->execute(array(":id" => $this->id));
	}

	protected function afterSave() {
		if($this->isNewRecord) {
			Yii::app()->db->createCommand("
				UPDATE chapters SET n_verses = n_verses + 1, last_tr = now() WHERE id = :chap_id;
				UPDATE books    SET n_verses = n_verses + 1, last_tr = now() WHERE id = :book_id;
			")->execute(array(":chap_id" => $this->chap_id, ":book_id" => $this->chap->book_id));
		}
	}

	/**
	 * Вызывается в контроллере после добавления нового комментария в пост.
	 * Увеличивает счётчик комментариев в посте и отправляет почту.
	 *
	 * @param Comment $comment - добавленный комментарий
	 * @param Comment $parent - родительский комментарий (пустой объект, если в корень)
	 */
	public function afterCommentAdd($comment, $parent) {
		// Увеличиваем счётчик комментариев поста
		$this->n_comments++;
		Yii::app()->db->createCommand("UPDATE orig SET n_comments = n_comments + 1 WHERE id = :id")->execute(array("id" => $this->id));

		// Отправляем почту
		$this->comment_mail($comment, $parent);
	}

	/**
	 * Вызывается в контроллере после удаления комментария в посте.
	 * Уменьшает счётчик комментариев поста.
	 *
	 * @param Comment $comment - удалённый комментарий
	 */
	public function afterCommentRm($comment) {
		Yii::app()->db->createCommand("UPDATE orig SET n_comments = n_comments - 1 WHERE id = :id")->execute(array(":id" => $this->id));
	}

	/**
	 * Отправляет почтовые уведомления о новом комментарии в посте. Вызывается из self::afterCommentAdd().
	 *
	 * @param Comment $comment - добавленный комментарий
	 * @param Comment $parent - родительский комментарий (пустой объект, если в корень)
	 */
	protected function comment_mail($comment, $parent, $debug = false) {
		// Шлём уведомления на почту владельцу перевода
		//
		// Кстати, chap.book.owner у нас в OrigController::actionComment() не загружается, поэтому тут будет ленивая загрузка
		// Пока хуй с ним, но @todo
		if( $this->chap->book->owner->id != Yii::app()->user->id and
			$this->chap->book->owner->ini_get(User::INI_MAIL_COMMENTS)
		) {
			$msg = new YiiMailMessage("Новый комментарий в переводе \"{$this->chap->book->fullTitle}\"");
			$msg->view = "orig_comment";
			$msg->setFrom(array(Yii::app()->params["commentEmail"] => Yii::app()->user->login . " - комментарий к переводу"));
			$msg->addTo($this->chap->book->owner->email);
			$msg->setBody(array(
				"comment" => $comment,
				"orig" => $this
			), "text/html");
			Yii::app()->mail->send($msg);
		}

		// Шлём уведомление на почту автору комментария, на который ответили, если:
		// я отвечал не себе
		// и не владельцу перевода (в последнем случае, он уже получил уведомление)
		// Загружен ли уже $parent->author?
		if( $parent->id &&
			$parent->author->id &&
			$parent->author->id != Yii::app()->user->id &&
			$parent->author->id != $this->chap->book->owner->id &&
			$parent->author->ini_get(User::INI_MAIL_COMMENTS)
		) {
			$msg = new YiiMailMessage("Ответ на ваш комментарий в переводе \"{$this->chap->book->fullTitle}\"");
			$msg->view = "orig_reply";
			$msg->setFrom(array(Yii::app()->params["commentEmail"] => Yii::app()->user->login . " - комментарий"));
			$msg->addTo($parent->author->email);
			$msg->setBody(array(
				"comment" => $comment,
				"parent" => $parent,
				"orig" => $this
			), "text/html");
			Yii::app()->mail->send($msg);
		}

		return true;
	}

	/**
	 * Добавляет пост в "мои обсуждения" текущего пользователя. Увеличивает seen.n_comments на $nc_inc
	 *
	 * @param integer $nc_inc
	 */
	public function setTrack($nc_inc = 0) {
		if(!Yii::app()->user->isGuest) {
			Yii::app()->db->createCommand("SELECT track_orig(:user_id, :orig_id, :inc)")
				->execute(array(":user_id" => Yii::app()->user->id, ":orig_id" => $this->id, ":inc" => $nc_inc));
		}
		return true;
	}

	/**
	 * Помечает в seen, что мы только что видели пост
	 */
	public function setSeen() {
		if(Yii::app()->user->isGuest) return;

		Yii::app()->db->createCommand("SELECT seen_orig(:user_id, :orig_id, :n_comments)")
			->execute(array(":user_id" => Yii::app()->user->id, ":orig_id" => $this->id, "n_comments" => $this->n_comments));
	}


	public function getUrl($area = "") {
		return "/book/{$this->chap->book_id}/{$this->chap->id}/" . intval($this->id) . ($area != "" ? "/{$area}" : "");
	}

	public function render($filter = null) {
		if(Yii::app()->user->ini["t.iface"] == 0) return $this->renderOld($filter);

		$body = nl2br(htmlspecialchars($this->body));
		if($filter && $filter->show == 5) $body = preg_replace("/({$filter->to_esc})/i", "<span class='shl'>\\1</span>", $body);

		$html = "<p class='text'>" . $body . "</p>";

		$html .= "<p class='info'>";
		if($this->chap->book->typ == "S") {
			$html .= "<a class='ord'>#{$this->ord}</a> &middot; ";
			$html .= "<span class='t1'>" . $this->nicetime("t1") . "</span> &rarr; <span class='t2'>" . $this->nicetime("t2") . "</span>";
		} else {
			$html .= "<a href='{$this->url}#{$this->ord}' class='ord'>#{$this->ord}</a>";
		}
		if($filter && $filter->show != 0) $html .= " <a href='{$this->url}' class='ctx'>в контексте</a>";
		$html .= "</p>";

		if($this->chap->book->can("chap_edit")) {
			$html .= "<div class='tools'>";
			$html .= "<a href='#' class='xp'><i class='i icon-xp'></i></a>";
			$html .= "<a href='#' class='edit'><i class='i icon-edit'></i></a>";
			$html .= "<a href='#' class='add'><i class='i icon-plus'></i></a>";
			$html .= "<a href='#' class='rm'><i class='i icon-remove'></i></a>";
			$html .= "</div>";
		}

		return $html;
	}

	public function renderOld($filter) {
		$body = nl2br(htmlspecialchars($this->body));
		if($filter && $filter->show == 5) $body = preg_replace("/({$filter->to_esc})/i", "<span class='shl'>\\1</span>", $body);
		return "<span class='b'>" . $body . "</span>";
	}

	public function renderTranslations($filter = null) {
		$user = Yii::app()->user;

		if($user->ini["t.iface"] == 0) return $this->renderTranslationsOld($filter);

		// Сортируем переводы по дате
		$trs = $this->trs;
		usort($trs, array("Translation", "trcmp"));

		// Находим best
		if($user->ini["t.hlr"] != 0 && count($trs) > 1) {
			$max_id = null; $max_rating = null;
			foreach($trs as $tr) {
				if($max_id === null || $tr->rating >= $max_rating) {
					$max_id = $tr->id;
					$max_rating = $tr->rating;
				}
			}
		}

		$ret = "";

		// Опции Translate::render() для автора версии перевода
		$tr_opts_owner = array(
			"edit" => true,
			"rm" => true,
			"rate" => false,
		);
		// Опции Translate::render() для всех остальных версий
		$tr_opts = array(
			"edit" => $this->chap->book->membership->status == GroupMember::MODERATOR,
			"rm" => $this->chap->book->membership->status == GroupMember::MODERATOR,
			"rate" => $this->chap->can("rate"),
			"rate-" => $this->chap->book->membership->status == GroupMember::MODERATOR,
		);

		foreach($trs as $tr) {
			if(!$this->chap->can("trread") && $tr->user_id != $user->id) continue;

			$opts = $tr->user_id == $user->id ? $tr_opts_owner : $tr_opts;

			$classes = ["u{$tr->user_id}"];
			if($tr->id == $max_id && $user->ini["t.hlr"] != 0) $classes[] = "best";
			$html = "<div id='t{$tr->id}' class='" . join(" ", $classes) . "'>";

			$body = nl2br(htmlspecialchars($tr->body));
			if($filter && $filter->show == 6) $body = preg_replace("/({$filter->tt_esc})/i", "<span class='shl'>\\1</span>", $body);
			$html .= "<p class='text'>{$body}</p>";

			$html .= "<p class='info'>";
			if($tr->user_id == 0) $html .= "(анонимно) ";
			else $html .= "{$tr->user->ahref} <i class='i icon-flag'></i> ";
			$html .= Yii::app()->dateFormatter->format("d.MM.yy в H:mm", $tr->cdate);
			$html .= "</p>";


			$tagPos = $opts["rate"] ? "a" : "span";
			$tagNeg = ($opts["rate"] && $opts["rate-"]) ? "a" : "span";
			if($tr->rating < 0) $aClass = "neg"; elseif($tr->rating > 0) $aClass = "pos"; else $aClass = "";
			$html .= "<div class='rating" . (!$opts["rate"] ? " x" : "") . "'>";
			if($opts["rate"]) $html .= "<{$tagPos} href='#' class='pane vote pos'><b>+</b></{$tagPos}>";
			$html .= "<a href='#' class='base current $aClass'>{$tr->rating}</a>";
			if($opts["rate"] && $opts["rate-"]) $html .= "<{$tagNeg} href='#' class='pane vote neg'><b>–</b></{$tagNeg}>";
			$html .= "</div>";

			if($opts["edit"] || $opts["rm"]) {
				$html .= "<div class='tools'>";
				// $html .= "<a href='#' class='xp'><i class='i icon-xp'></i></a>";
				if($opts["edit"]) $html .= "<a href='#' class='edit'><i class='i icon-edit'></i></a>";
				if($opts["rm"]) $html .= "<a href='#' class='rm'><i class='i icon-remove'></i></a>";
				$html .= "</div>";
			}


			$html .= "</div>\n";

			$ret .= $html;
		}

		return $ret;
	}

	private function renderTranslationsOld() {
		$trs = $this->trs;
		usort($trs, array("Translation", "trcmp"));

		$user = Yii::app()->user;

		$max_id = null; $max_rating = null; $max_cdate = null;
		foreach($trs as $tr) {
			if($max_id === null || $tr->rating >= $max_rating) {
				$max_id = $tr->id;
				$max_rating = $tr->rating;
			}
		}
		$tr_opts = array(
			"edit" => $this->chap->book->membership->status == GroupMember::MODERATOR,
			"rm" => $this->chap->book->membership->status == GroupMember::MODERATOR,
			"rate" => $this->chap->can("rate"),
			"rate-" => $this->chap->book->membership->status == GroupMember::MODERATOR,
		);
		$tr_opts_owner = array(
			"edit" => true,
			"rm" => true,
			"rate" => false,
		);
		$html = "";
		foreach($trs as $tr) {
			if(!$this->chap->can("trread") && $tr->user_id != $user->id) continue;
			$tr->chap = $this->chap;
			if($user->ini["t.hlr"] == 1) $tr_opts["best"] = $tr_opts_owner["best"] = $tr->id == $max_id;
			$html .= $tr->render(($tr->user_id == $user->id) ? $tr_opts_owner : $tr_opts);
		}

		return $html;
	}


	/**
	 * @param string $param "t1" или "t2"
	 * @return string время в коротком формате
	 */
	public function nicetime($param) {
		$t = $this->$param;
		if(strlen($t) == 8) $t .= ".000";
		else $t = str_pad($t, 12, "0");
		if(strncmp($t, "00:", 3) == 0) $t = substr($t, 3);
		return $t;
	}

	/**
	 * @param string $param "t1" или "t2"
	 * @return string время в стандартном формате ЧЧ:ММ:СС.ддд
	 */
	public function stdtime($param) {
		$t = $this->$param;
		if(strlen($t) == 8) $t .= ".000";
		else $t = str_pad($t, 12, "0");
		return $t;
	}

	public function mstime($param) {
		$t = $this->stdtime($param);
		list($h, $m, $s, $d) = sscanf($t, "%d:%d:%d.%d");
		return (($h * 60 + $m) * 60 + $s) * 1000 + $d;
	}

	public static function ms2std($ms) {
		$d = $ms % 1000; $ms = (int) ($ms / 1000);
		$s = $ms % 60; $ms = (int) ($ms / 60);
		$m = $ms % 60; $h = (int) ($ms / 60);
		return sprintf("%02d:%02d:%02d.%03d", $h, $m, $s, $d);
	}

	public static function std2ms($std) {
		list($h, $m, $s, $d) = sscanf($std, "%d:%d:%d.%d");
		return (($h * 60 + $m) * 60 + $s) * 1000 + $d;
	}

	public function getErrorsString() {
		$t = "";
		foreach($this->getErrors() as $field => $errors) {
			if($t != "") $t .= "<br />";
			$t .= join("\n", $errors);
		}

		return $t;
	}

}

/* Эх, Тисла, Тисла :( */