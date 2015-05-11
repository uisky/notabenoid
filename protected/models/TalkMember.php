<?php
class TalkMember extends CActiveRecord {
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function tableName() {
		return "talk_members";
	}

	public $talk_id, $user_id, $seen, $n_comments;

	public function relations() {
		return array(
			"talk" => array(self::BELONGS_TO, "Talk", "talk_id"),
			"user" => array(self::BELONGS_TO, "User", "user_id"),
		);
	}
}
