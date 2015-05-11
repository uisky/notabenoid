<?php
class BlogPost extends CActiveRecord {
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function tableName() {
		return 'blog_posts';
	}

	public $id, $user_id, $book_id, $cdate, $n_comments, $lastcomment, $topics, $title, $body;
	public $n_new_comments = 0;

	public function rules() {
		$topics = Yii::app()->params["blog_topics"][$this->book_id ? "book" : "common"];
		if(Yii::app()->user->id != 1) unset($topics[64]);

		return array(
			array("title", "required", "message" => "Пожалуйста, введите заголовок."),
			array("body", "required", "message" => "Пожалуйста, введите текст поста."),
			array("title", "filter", "filter" => "strip_tags"),
			array("body", "safehtml"),
			array("topics", "required", "message" => "Пожалуйста, выберите рубрику."),
			array("topics", "in", "range" => array_keys($topics)),
		);
	}

	public function safehtml($attr, $params) {
		$p = new CHtmlPurifier();
		$p->options = Yii::app()->params["HTMLPurifierOptions"];
		$this->$attr = trim($p->purify($this->$attr));
	}

	public function attributeLabels() {
		return array(
			"title" => "Заголовок",
			"body" => "Текст поста",
			"topics" => "Рубрика"
		);
	}

	public function relations() {
		$rel = [
			"author" => array(self::BELONGS_TO, "User", "user_id", "select" => array("id", "login", "sex", "upic", "email", "ini", "can")),
			"book"   => array(self::BELONGS_TO, "Book", "book_id", /* "select" => array("id", "s_title", "t_title", ac_*) */),
		];
		if(!Yii::app()->user->isGuest) {
			$rel["seen"] = [self::HAS_ONE, "Seen", "post_id", "on" => "seen.user_id = " . intval(Yii::app()->user->id), "select" => ["seen", "n_comments", "track"]];
		} else {
			// дешёвый трюк, расчитанный на то, что планировщик postgresql заметит, что seen.user_id NOT NULL и не будет ничего джойнить вообще
			// Вообще для анонимов эту реляцию в контроллере ещё не загружать
			$rel["seen"] = [self::HAS_ONE, "Seen", "post_id", "on" => "seen.user_id IS NULL", "select" => ["seen", "n_comments", "track"]];
		}

		return $rel;
	}



	// SCOPES

	public function common($topics = null) {
		if($topics === null) $topics = [];
		if(!is_array($topics)) $topics = [(int) $topics];

		$this->dbCriteria->mergeWith(array(
			"condition" => "t.book_id IS NULL",
			"order" => "t.lastcomment desc",
			"with" => array("author", "seen")
		));

		if(count($topics) == 0) $this->dbCriteria->addInCondition("topics", array_keys(Yii::app()->params["blog_topics"]["common"]));
		else $this->dbCriteria->addInCondition("topics", $topics);

		return $this;
	}

	// Все посты из перевода $book_id (если $book_id = 0, то из общего блога)
	public function book($book_id) {
		$book_id = (int) $book_id;

		$this->getDbCriteria()->mergeWith(array(
			"condition" => "t.book_id " . ($book_id == 0 ? "IS NULL" : "= '{$book_id}'"),
			"order" => "t.lastcomment desc",
		));

		return $this;
	}

	// Все посты пользователя
	public function user($user_id) {
		$user_id = (int) $user_id;

		$this->getDbCriteria()->mergeWith(array(
			"condition" => "t.user_id = '{$user_id}'",
			"order" => "t.lastcomment desc",
		));

		return $this;
	}

	// мои обсуждения ($new_only: только с новыми комментариями)
	public function x_expired_talks($new_only = false) {
		$c = new CDbCriteria();
		$c->join = "RIGHT JOIN seen seen_my ON seen_my.post_id = t.id";		// второй раз джойним seen, первый раз - через with
		$c->addCondition("seen_my.user_id = '" . intval(Yii::app()->user->id) . "' AND seen_my.track");
		if($new_only) $c->addCondition("t.n_comments - COALESCE(seen_my.n_comments, 0) != 0");
		$c->order = "t.lastcomment desc";

		$this->getDbCriteria()->mergeWith($c);

		return $this;
	}

	public function talks($new_only = false) {
		$c = $this->dbCriteria;

		$c->with = [
			"author", "book.membership",
			"seen" => ["joinType" => "RIGHT JOIN", "on" => ""]
		];

		$c->addCondition("seen.user_id = '" . intval(Yii::app()->user->id) . "' AND seen.track");
		if($new_only) $c->addCondition("t.n_comments - COALESCE(seen.n_comments, 0) != 0");

		$c->order = "t.lastcomment desc";

		return $this;
	}


	// EVENTS

	public function afterFind() {
		if($this->hasRelated("seen")) $this->n_new_comments = $this->n_comments - $this->seen->n_comments;
	}

	public function beforeSave() {
		if($this->isNewRecord) {
			$this->lastcomment = new CDbExpression("now()");
		}
		return true;
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
		$this->lastcomment = new CDbExpression("now()");
		Yii::app()->db->createCommand("UPDATE blog_posts SET n_comments = n_comments + 1, lastcomment = now() WHERE id = :post_id")
			->execute(array("post_id" => $this->id));

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
		Yii::app()->db->createCommand("UPDATE blog_posts SET n_comments = n_comments - 1 WHERE id = :post_id")
			->execute(array(":post_id" => $this->id));

	}

	/**
	* Отправляет почтовые уведомления о новом комментарии в посте. Вызывается из self::afterCommentAdd().
	*
	* @param Comment $comment - добавленный комментарий
	* @param Comment $parent - родительский комментарий (пустой объект, если в корень)
	*/
	protected function comment_mail($comment, $parent, $debug = false) {
		$subj = "Комментарий в посте ";
		if($this->title == "")
			$subj .= "{$this->author->login} от " . Yii::app()->dateFormatter->formatDateTime($this->cdate, "medium", "short");
		else
			$subj = "\"{$this->title}\"";

		// Шлём уведомления на почту автору поста, если это не мой пост
		if( $this->author->id != Yii::app()->user->id and
			$this->author->ini_get(User::INI_MAIL_COMMENTS)
		) {
			$msg = new YiiMailMessage($subj);
			$msg->view = "comment_post";
			$msg->setFrom(array(Yii::app()->params["commentEmail"] => Yii::app()->user->login . " - комментарий"));
			$msg->addTo($this->author->email);
			$msg->setBody(array(
				"comment" => $comment,
				"post" => $this
			), "text/html");
			Yii::app()->mail->send($msg);

			if($debug) {
				$debug_text = "From: " . print_r($msg->from, true) . "\nTo: " . print_r($msg->to, true) . "\nSubj: {$msg->subject}\n{$msg->body}\n\n";
				file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/{$msg->view}.email.html", $debug_text);
			}
		}

		// Шлём уведомление на почту автору комментария, на который ответили, если я отвечал не себе и не автору поста (в последнем случае, он уже получил уведомление)
		if( $parent->id and
			$parent->author->id != Yii::app()->user->id and
			$parent->author->id != $this->author->id and
			$parent->author->ini_get(User::INI_MAIL_COMMENTS)
		) {
			$msg = new YiiMailMessage($subj);
			$msg->view = "comment_reply";
			$msg->setFrom(array(Yii::app()->params["commentEmail"] => Yii::app()->user->login . " - комментарий"));
			$msg->addTo($parent->author->email);
			$msg->setBody(array(
				"comment" => $comment,
				"parent" => $parent,
				"post" => $this
			), "text/html");
			Yii::app()->mail->send($msg);

			if($debug) {
				$debug_text = "From: " . print_r($msg->from, true) . "\nTo: " . print_r($msg->to, true) . "\nSubj: {$msg->subject}\n{$msg->body}\n\n";
				file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/{$msg->view}.email.html", $debug_text);
			}
		}

		return true;
	}



	// ACTIONS

	/**
	* Добавляет пост в "мои обсуждения" текущего пользователя. Увеличивает seen.n_comments на $nc_inc
	*
	* @param integer $nc_inc
	*/
	public function setTrack($nc_inc = 0) {
		if(!Yii::app()->user->isGuest) {
			Yii::app()->db->createCommand("SELECT track_post(:user_id, :post_id, :inc)")
				->execute(array(":user_id" => Yii::app()->user->id, ":post_id" => $this->id, ":inc" => $nc_inc));
		}
		return true;
	}

	/**
	* Помечает в seen, что мы только что видели пост
	*/
	public function setSeen() {
		if(Yii::app()->user->isGuest) return;

		Yii::app()->db->createCommand("SELECT seen_post(:user_id, :post_id, :n_comments)")
			->execute(array(":user_id" => Yii::app()->user->id, ":post_id" => $this->id, "n_comments" => $this->n_comments));
	}

	public function can($what) {
		$user = Yii::app()->user;

		if($what == "edit") {
			if($this->book_id != 0) {
				if($this->isAnnounce) return Yii::app()->user->can("blog_moderate");
				else return $this->user_id == $user->id || $this->book->membership->status == GroupMember::MODERATOR;
			} else {
				return $this->user_id == $user->id || $user->can("blog_moderate");
			}
		} elseif($what == "read") {
			if(!isset(Yii::app()->params["blog_topics"]["common"][$this->topics])) return false;
			return true;
		} else {
			return false;
		}
	}

	public function getUrl($area = "") {
		if($this->book_id == 0) return "/blog/{$this->id}" . ($area != "" ? "/{$area}" : "");
		else {
			return $this->book->getUrl(($this->isAnnounce ? "announces" : "blog") . "/{$this->id}" . ($area != "" ? "/{$area}" : ""));
		}
	}

	public function getIsAnnounce() {
		return $this->topics >=80 && $this->topics <= 89;
	}

	public function getTopicHtml() {
		$class = array(81 => "label-info", "82" => "label-success", 89 => "label-inverse");

		return "<span class='label {$class[$this->topics]}'>" . Yii::app()->params["blog_topics"]["announce"][$this->topics] . "</span>";
	}

	public function url($area = "") {
		throw new CException('DEPRECATED FUNCTION USED: BlogPost::url()');
		return $this->url . ($area != "" ? "/{$area}" : "");
	}
}