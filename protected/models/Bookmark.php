<?php
/**
 * @property User $user
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $book_id
 * @property integer $orig_id
 * @property string $cdate
 * @property integer $ord
 * @property integer $note;

 * @property integer $n_orig_bms
 */
class Bookmark extends CActiveRecord {
	/** @return Bookmark */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function tableName() {
		return "bookmarks";
	}
//	public function primaryKey() {
//		return array("user_id", "book_id", "orig_id");
//	}

	public $n_origs;

	public function rules() {
		return array(
			array("ord", "numerical", "integerOnly" => true),
			array("note", "clean"),
			array("watch", "boolean"),
		);
	}

	public function clean($attr, $params) {
		$this->$attr = trim(strip_tags($this->$attr));
	}

	public function relations() {
		return array(
			"user"  => array(self::BELONGS_TO, "User",     "user_id"),
			"book"  => array(self::BELONGS_TO, "Book",     "book_id"/* , "select" => array("id", "owner_id", "s_title", "t_title", "n_vars", "d_vars", "n_verses") */),
			"orig"  => array(self::BELONGS_TO, "Orig",     "orig_id"),

			"no" => array(self::STAT, "Bookmark", "book_id", "condition" => "t.user_id = no.user_id AND no.orig_id IS NOT NULL")
		);
	}

	public function afterDelete() {
		if(!$this->orig_id) {
			Yii::app()->db->createCommand("DELETE FROM bookmarks WHERE user_id = :user_id AND book_id = :book_id AND orig_id IS NOT NULL")
				->execute(array(":user_id" => $this->user_id, ":book_id" => $this->book_id));
		}

		parent::afterDelete();
	}

	public function bookList($user_id) {
		$this->dbCriteria->mergeWith(array(
			"with" => "book.membership",
			"select" => "t.user_id, t.book_id, t.orig_id, t.cdate, t.note, t.watch, t.ord, (SELECT COUNT(*) FROM bookmarks bm2 WHERE bm2.user_id = t.user_id AND bm2.book_id = t.book_id AND bm2.orig_id IS NOT NULL) as n_origs",
			"condition" => "t.user_id = :user_id AND t.orig_id IS NULL",
			"params" => array(":user_id" => $user_id),
		));

		return $this;
	}

	public function origList($user_id, $book_id) {
		$this->dbCriteria->mergeWith(array(
			"with" => array("orig.chap" => array("select" => array("title"))),
			"condition" => "t.user_id = :user_id AND t.book_id = :book_id AND t.orig_id IS NOT NULL",
			"params" => array(":user_id" => $user_id, ":book_id" => $book_id),
		));

		return $this;
	}

	public function getJSON() {
		$json = array(
			"ord" => $this->ord,
			"note" => $this->note,
			"cdate" => strtotime($this->cdate),
		);

		if($this->orig_id == 0) {
			$json["watch"] = (int) $this->watch;
			$json["book"] = array(
				"id" => $this->book->id,
				"s_title" => $this->book->s_title,
				"t_title" => $this->book->t_title,
				"ready" => $this->book->ready,
			);
			$json["group"] = array(
				"status" => $this->book->membership->status,
				"last_tr" => strtotime($this->book->membership->last_tr),
				"since" => strtotime($this->book->membership->since),
			);
			$json["nOrigs"] = $this->n_origs;
		} else {
			$json["orig"] = array(
				"id" => $this->orig->id,
				"chap_id" => $this->orig->chap_id,
				"body" => (mb_strlen($this->orig->body) < 40 ? $this->orig->body : (mb_substr($this->orig->body, 0, 40) . "...")) . " / " . $this->orig->chap->title,
			);
		};

		return $json;
	}

	public function getErrorsString() {
		$t = "";
		foreach($this->getErrors() as $field => $errors) {
			$t .= join("\n", $errors);
		}

		return $t;
	}

	/**
	 * Ставит закладку на перевод/фрагмент
	 * Если закладка была, то редактируется $note (если указано)
	 * Если не было, то создаётся с min(ord) - 1
	 **/
	public static function set($book_id, $orig_id = null, $note = null) {
		if(Yii::app()->user->isGuest) return false;

		$pk = array("user_id" => Yii::app()->user->id, "book_id" => $book_id);
		if($orig_id) $pk["orig_id"] = $orig_id;
		$bm = Bookmark::model()->findByAttributes($pk);

		if(!$bm) {
			$bm = new Bookmark();
			$bm->user_id = Yii::app()->user->id;
			$bm->book_id = $book_id;
			if($orig_id) $bm->orig_id = $orig_id;
			$bm->ord = Yii::app()->db->createCommand("SELECT MAX(ord) FROM bookmarks WHERE user_id = :user_id AND orig_id IS NULL")->queryScalar(array(":user_id" => Yii::app()->user->id)) + 1;
		}

		if($note !== null) $bm->note = $note;

		return $bm->save();
	}
}
?>
