<?php
class GroupMember extends CActiveRecord {
	const BANNED = -1;
	const CONTRIBUTOR = 0;
	const MEMBER = 1;
	const MODERATOR = 2;

	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function tableName() {
		return "groups";
	}

	public $book_id, $user_id, $status, $n_trs, $rating, $since;

	public function primaryKey() {
		return array("book_id", "user_id");
	}

	public function relations() {
		return array(
			"user" => array(self::BELONGS_TO, "User", "user_id"),
			"book" => array(self::BELONGS_TO, "Book", "book_id"),
		);
	}

	public function user($user_id) {
		$this->getDbCriteria()->mergeWith(array(
			"condition" => "t.user_id = " . intval($user_id)
		));

		return $this;
	}

	public function book($book_id) {
		$this->getDbCriteria()->mergeWith(array(
			"condition" => "t.book_id = " . intval($book_id)
		));

		return $this;
	}
}
?>
