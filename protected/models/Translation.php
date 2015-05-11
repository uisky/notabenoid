<?php
/**
 * @property User $user
 * @property Book $book
 * @property Chapter $chap
 * @property Orig $orig
 * @property Mark $mark
 * @property Marks[] $marks
 *
 * @property integer $id
 * @property integer $book_id
 * @property integer $chap_id
 * @property integer $orig_id
 * @property integer $user_id
 * @property string $cdate
 * @property integer $rating
 * @property integer $n_votes;
 * @property string $body
 */
class Translation extends CActiveRecord {
	/** @returns Translation */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function tableName() {
		return "translate";
	}

	public function rules() {
		return array(
			array("body", "required", "message" => "Пожалуйста, введите вашу версию перевода этого фрагмента."),
			array("body", "validateBody"),
		);
	}

	public function validateBody($param, $options) {
		if($this->chap->book->typ == "S") $this->body = trim($this->body);
	}

	public function relations() {
		return array(
			"user"  => array(self::BELONGS_TO, "User",     "user_id"),
			"book"  => array(self::BELONGS_TO, "Book",     "book_id"),	// убрать бы нахуй, посмотреть, где используется (в модели доступ везде через $this->chap->book)
			"chap"  => array(self::BELONGS_TO, "Chapter",  "chap_id"),
			"orig"  => array(self::BELONGS_TO, "Orig",     "orig_id"),
			"mark"  => array(self::HAS_ONE,    "Mark",     "tr_id", "on" => "mark.user_id = " . intval(Yii::app()->user->id)),
			"marks" => array(self::HAS_MANY,   "Mark",     "tr_id", "order" => "marks.cdate desc"),
		);
	}

	public function chapter($id) {
		$this->getDbCriteria()->mergeWith(array(
			"condition" => "chap_id = " . intval($id),
		));
		return $this;
	}

	public function orig($id) {
		$this->getDbCriteria()->mergeWith(array(
			"condition" => "orig_id = " . intval($id),
		));
		return $this;
	}

	public function userbook($user_id, $book_id) {
		$this->getDbCriteria()->mergeWith(array(
			"condition" => "t.user_id = " . intval($user_id) . " AND t.book_id = " . intval($book_id),
		));
		return $this;
	}

	public function afterSave() {
		parent::afterSave();

		$sql = array();
		$params = array();
		$chap_update = array();
		$book_update = array();
		if($this->isNewRecord) {
			$is_first = count(Yii::app()->db->createCommand("SELECT 1 FROM translate WHERE orig_id = :orig_id LIMIT 2")->queryAll(true, array(":orig_id" => $this->orig_id))) == 1;

			$sql[] = "UPDATE users SET n_trs = n_trs + 1 WHERE id = :user_id;";
			$params[":user_id"] = $this->user_id;
			if($this->hasRelated("user")) $this->user->n_trs++;

			$sql[] = "UPDATE orig SET n_trs = n_trs + 1 WHERE id = :orig_id;";
			$params[":orig_id"] = $this->orig_id;
			if($this->hasRelated("orig")) $this->orig->n_trs++;

			$chap_update[] = "n_vars = n_vars + 1";
			$book_update[] = "n_vars = n_vars + 1";
			if($this->hasRelated("chap")) $this->chap->n_vars++;
			if($this->hasRelated("book")) $this->book->n_vars++;

			if($is_first) {
				$chap_update[] = "d_vars = d_vars + 1";
				$book_update[] = "d_vars = d_vars + 1";
				if($this->hasRelated("chap")) $this->chap->d_vars++;
				if($this->hasRelated("book")) $this->book->d_vars++;
			}
		}

		$chap_update[] = "last_tr = now()";
		$book_update[] = "last_tr = now()";

		if(count($chap_update) > 0) {
			$sql[] = "UPDATE chapters SET " . join(", ", $chap_update) . " WHERE id = :chap_id;";
			$params[":chap_id"] = $this->chap_id;
		}

		if(count($book_update) > 0) {
			$sql[] = "UPDATE books SET " . join(", ", $book_update) . " WHERE id = :book_id;";
			$params[":book_id"] = $this->book_id;
		}

		$sql = "BEGIN;\n" . join("\n", $sql) . "\nCOMMIT;";
		Yii::app()->db->createCommand($sql)->execute($params);
	}

	public function afterDelete() {
		parent::afterDelete();

		$sql = array();
		$params = array();

		if($this->user_id != 0) {
			// Понижаем рейтинг автора
			$sql[] = "UPDATE users SET n_trs = n_trs - 1, rate_t = rate_t - :rating WHERE id = :user_id;";
			$params[":rating"] = $this->rating;
			$params[":user_id"] = $this->user_id;

			// Понижаем рейтинг автора в группе
			$sql[] = "UPDATE groups SET n_trs = n_trs - 1, rating = rating - :rating WHERE book_id = :book_id AND user_id = :user_id;";
			$params[":book_id"] = $this->chap->book_id;
		}

		// Статистика chap и book
		$was_last = count(Yii::app()->db->createCommand("SELECT 1 FROM translate WHERE orig_id = :orig_id LIMIT 1")->queryAll(true, array(":orig_id" => $this->orig_id))) == 0;
		$t = "n_vars = n_vars - 1" . ($was_last ? ", d_vars = d_vars - 1" : "");

		$sql[] = "UPDATE chapters SET {$t}, last_tr = now() WHERE id = :chap_id;";
		$params[":chap_id"] = $this->chap_id;

		$sql[] = "UPDATE books SET {$t}, last_tr = now() WHERE id = :book_id;";
		$params[":book_id"] = $this->chap->book_id;

		$this->chap->n_vars--; $this->chap->book->n_vars--;
		if($was_last) { $this->chap->d_vars--; $this->chap->book->d_vars--; }

		// Статистика orig.n_trs
		$sql[] = "UPDATE orig SET n_trs = n_trs - 1 WHERE id = :orig_id;";
		$params[":orig_id"] = $this->orig_id;

		Yii::app()->db->createCommand("BEGIN;\n" . join("\n", $sql) . "\nCOMMIT;")->execute($params);
	}

	public function getErrorsString() {
		$t = "";
		foreach($this->getErrors() as $field => $errors) {
			$t .= join("\n", $errors);
		}

		return $t;
	}

	public function removeMarks() {
		Yii::app()->db->createCommand("DELETE FROM marks WHERE tr_id = :id")->execute(array(":id" => $this->id));
	}

	public function render($opts, $filter = null) {
		$html = "<div id='t{$this->id}'" . ($opts["best"] ? "class='best'" : "") . ">";

		if($opts["rate"]) $html .= "<div class='rater'>";
		if($opts["rate"] && $opts["rate-"]) $html .= "<a href='#' class='m'>&minus;</a> ";
		$html .= "<span class='rate'>{$this->rating}</span>";
		if($opts["rate"]) $html .= " <a href='#' class='p'>+</a></div>";

		$body = nl2br(htmlspecialchars($this->body));
		if($filter && $filter->show == 6) $body = preg_replace("/({$filter->tt_esc})/i", "<span class='shl'>\\1</span>", $body);
		$html .= "<span class='b'>" . $body . "</span>";

		if($this->user_id == 0) $html .= " (анонимно)";
		else $html .= " ({$this->user->ahref})";

		if($opts["edit"]) $html .= " <a href='#' class='e'><i class='icon-edit'></i></a>";
		if($opts["rm"]) $html .= " <a href='#' class='x'><i class='icon-remove'></i></a>";
		$html .= "</div>\n";

		return $html;
	}

	public static function trcmp($a, $b) {
		return strtotime($a->cdate) - strtotime($b->cdate);
	}

}
?>
