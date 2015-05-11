<?php
class Comment extends CActiveRecord {
	public static function model($className=__CLASS__) { return parent::model($className); }
	public function tableName() { return 'comments'; }

	public $id, $pid, $mp = array(), $cdate, $ip, $user_id, $body, $is_new = false, $rating = 0, $n_votes = 0;
	public $post_id, $orig_id;
	public $parent = null;

	public function getSeenTableName() { return "seen"; }

	// DATA STRUCTURE DEFINITION

	public function rules() {
		return array(
			array("body", "safehtml"),
			array("body", "required"),
			array("pid", 'numerical', 'integerOnly'=>true),
		);
	}

	public function safehtml($attr, $params) {
		$p = new CHtmlPurifier();
		$p->options = Yii::app()->params["HTMLPurifierOptions"];
		$this->$attr = trim($p->purify($this->$attr));
	}

	public function attributeLabels() {
		return array(
			"body" => "Комментарий",
		);
	}

	public function relations() {
		return array(
			"author" => array(self::BELONGS_TO, "User", "user_id", "select" => array("id", "login", "sex", "upic", "email", "ini", "can")),
			"post" => array(self::BELONGS_TO, "BlogPost", "post_id", "select" => array("id", "user_id", "book_id", "cdate", "topics", "title")),
			"orig" => array(self::BELONGS_TO, "Orig", "orig_id"),
		);
	}

	// SCOPES

	public function defaultScope() {
		return array(
			"order" => "t.mp, t.cdate",
		);
	}

	public function newer($date) {
		if($date != "") {
			$this->getDbCriteria()->mergeWith(array(
				"select" => array(
					"t.*",
					new CDbExpression("t.cdate >= '{$date}' as is_new"),
				),
			));
		} else {
			$this->getDbCriteria()->mergeWith(array(
				"select" => array(
					"t.*",
					new CDbExpression("1 as is_new"),
				)
			));
		}

		return $this;
	}

	public function post($post_id) {
		$this->getDbCriteria()->mergeWith(array(
			"condition" => "t.post_id = '{$post_id}'",
		));

		return $this;
	}

	public function orig($orig_id) {
		$this->getDbCriteria()->mergeWith(array(
			"condition" => "t.orig_id = '{$orig_id}'",
		));

		return $this;
	}

	public function user($user_id) {
		$this->getDbCriteria()->mergeWith(array(
			"condition" => "t.user_id = " . intval($user_id),
		));
		return $this;
	}

	// EVENTS

	public function afterFind() {
		$this->mp = explode(",", substr($this->mp, 1, -1));
	}

	public function beforeSave() {
		$this->mp = "{" . join(",", $this->mp) . "}";
		return true;
	}

	/**
	* Заносит пост в мои вещи
	* Увеличивает счётчик комментариев юзера
	*/
	public function afterSave() {
		if($this->user_id) {
			// Заносим пост в мои вещи
			if($this->orig_id) {
				Yii::app()->db->createCommand("SELECT track_orig(:user_id, :orig_id, 1)")->execute(array(":orig_id" => $this->orig_id, ":user_id" => $this->user_id));
			} else {
				Yii::app()->db->createCommand("SELECT track_post(:user_id, :post_id, 1)")->execute(array(":post_id" => $this->post_id, ":user_id" => $this->user_id));
			}

			// Увеличиваем счётчик комментариев юзера
			Yii::app()->db->createCommand("UPDATE users SET n_comments = n_comments + 1 WHERE id = :user_id")->execute(array(":user_id" => $this->user_id));
		}
	}

	/**
	* Правит seen
	* Уменьшает счётчик комментариев юзера
	*/
	public function afterDelete() {
		// Правим seen
		$field = $this->orig_id ? "orig_id" : "post_id";
		$sql = "UPDATE seen SET n_comments = n_comments - 1 WHERE {$field} = :page_id AND (seen >= :cdate OR user_id = :my_uid)";
		$params = array(
			":page_id" => $this->orig_id ? $this->orig_id : $this->post_id,
			":cdate" => $this->cdate,
			":my_uid" => Yii::app()->user->id
		);
		Yii::app()->db->createCommand($sql)->execute($params);

		if($this->user_id) {
			Yii::app()->db->createCommand("UPDATE users SET n_comments = n_comments - 1 WHERE id = :user_id")
				->execute(array("user_id" => $this->user_id));
		}

		parent::afterDelete();
	}

	// ACTIONS

	/**
	* Добавляет ответ $comment на этот комментарий. Возвращает true или false в зависимости от успеха операции.
	* Будет вызван $comment->validate(), все ошибки - в $comment.
	*
	* @param Comment $comment
	*/
	public function reply($comment) {
		if(Yii::app()->user->isGuest) throw new CHttpException(500, "Анонимные комментарии запрещены");

		$comment->cdate = date("Y-m-d H:i:s");
		$comment->author = Yii::app()->user->getModel();
		$comment->user_id = Yii::app()->user->id;
		$comment->pid = $this->id;
		$comment->ip = $_SERVER["HTTP_X_REAL_IP"];
		if($this->orig_id) {
			$comment->orig = $this->orig;
			$comment->orig_id = $this->orig_id;
		} else {
			$comment->post = $this->post;
			$comment->post_id = $this->post_id;
		}

		// Считаем максимальный ind среди будущих сестёр
		$t = $this->orig_id ? "orig_id = {$this->orig_id}" : "post_id = {$this->post_id}";
		$max_mp = Yii::app()->db
			->createCommand("SELECT max(mp) FROM comments WHERE {$t} AND pid " . ($this->id ? "= '{$this->id}'" : "IS NULL"))
			->queryScalar();
		if($max_mp == "") {
			$comment->mp = $this->mp;
			$comment->mp[] = 1;
		} else {
			$comment->mp = explode(",", substr($max_mp, 1, -1));
			$comment->mp[count($comment->mp) - 1]++;
		}

		return $comment->save();
	}

	public function delete() {
		if($this->getIsNewRecord()) throw new CDbException(Yii::t('yii','The active record cannot be deleted because it is new.'));
		if(!$this->beforeDelete()) return false;

		$n = count($this->mp);
		$p = array(
			":id" => $this->id,
			":page_id" => $this->orig_id ? $this->orig_id : $this->post_id,
			":mp" => "{" . join(",", $this->mp) . "}",
		);
		$field = $this->orig_id ? "orig_id" : "post_id";

		// Есть ли у удаляемого комментария неудалённые потомки? (дети могут при этом быть удалены, поэтому не WHERE pid = :id)
		$has_kids = Yii::app()->db->createCommand(
			"SELECT 1 FROM comments WHERE {$field} = :page_id AND mp[0:{$n}] = :mp AND id != :id AND body != ''"
		)->query($p)->count();

		if($has_kids) {
			// Под нами есть неудалённые комментарии, помечаем наш, как удалённый
			Yii::app()->db->createCommand("UPDATE comments SET body = '', user_id = NULL WHERE id = :id")->execute(array("id" => $this->id));
		} else {
			// Ниже нас либо нет комментариев вообще, либо толко удалённые. Стираем всю ветку. Последний AND в WHERE - для перестраховки
			Yii::app()->db->createCommand("DELETE FROM comments WHERE {$field} = :page_id AND mp[0:{$n}] = :mp AND (id = :id OR body = '')")->execute($p);
		}

		$this->afterDelete();

		return 1;
	}

	public function rate($mark) {
		$mark = $mark < 0 ? -1 : 1;
		$user = Yii::app()->user;
		$debug = "";

		$sql = array();
		$params = [":user_id" => $user->id, ":comment_id" => $this->id];

		$old_mark = Yii::app()->db->createCommand("SELECT mark FROM comments_rating WHERE user_id = :user_id AND comment_id = :comment_id")
			->queryScalar($params);

//		$debug .= "old_mark = {$old_mark}\n";

		if($old_mark == $mark) return false;

		$params[":mark"] = $mark;

		if($old_mark == 0) {
			$sql[] = "INSERT INTO comments_rating (comment_id, user_id, mark) VALUES (:comment_id, :user_id, :mark);";
			$params[":dv"] = $mark;
			$params[":dn"] = 1;
		} else {
			$sql[] = "UPDATE comments_rating SET mark = :mark WHERE comment_id = :comment_id AND user_id = :user_id;";
			$params[":dv"] = $mark - $old_mark;
			$params[":dn"] = 0;
		}
		$sql[] = "UPDATE comments SET rating = rating + :dv, n_votes = n_votes + :dn WHERE id = :comment_id;";

//		$debug .= strtr(join("\n", $sql), $params) . "\n";
//		$debug .= "params = " . print_r($params, true);

		Yii::app()->db->createCommand("BEGIN;\n" . join("\n", $sql) . "\nCOMMIT;")->execute($params);

		$this->rating += $params[":dv"];
		$this->n_votes += $params[":dn"];

		return true;
	}

	// UTILS

	public function can($what) {
		$user = Yii::app()->user;
		if($what == "delete") {
			if($user->can("blog_moderate")) return true;
			if($this->orig_id != 0) {
				// Комментарий к фрагменту  --- @todo сделать, чтобы модератор не мог стереть комментарий владельца перевода (this->orig->chap->book->owner_id)
				return $this->user_id == $user->id || $this->orig->chap->book->membership->status == GroupMember::MODERATOR;
			} elseif($this->post_id != 0) {
				if($this->post->book_id == 0) {
					// Комментарий в общем блоге
					return $this->user_id == $user->id || $this->post->user_id == $user->id;
				} else {
					// Комментарий в блоге перевода
					return $this->user_id == $user->id || $this->post->user_id == $user->id || $this->post->book->membership->status == GroupMember::MODERATOR;
				}
			}
		} elseif($what == "rate") {
			if($this->user_id == $user->id) return false;
			if($user->isGuest) return false;
			return true;
		}
		return false;
	}

	public function getErrorsString() {
		$t = "";
		foreach($this->getErrors() as $field => $errors) {
			$t .= join("\n", $errors);
		}

		return $t;
	}

	public function isDeleted() {
		return $this->body == "" and $this->user_id == 0;
	}
}
?>
