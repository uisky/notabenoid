<?php
/**
 * @property integer $user_id
 * @property integer $tr_id
 * @property integer $mark
 */
class Mark extends CActiveRecord {
	/** @returns Mark */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function tableName() {
		return "marks";
	}
	public function primaryKey() {
		return array("tr_id", "user_id");
	}

	public function relations() {
		return array(
			"user"      => array(self::BELONGS_TO, "User", "user_id"),
			"translate" => array(self::BELONGS_TO, "Translate", "tr_id")
		);
	}

	public function rules() {
		return array(
			array("mark", "in", "range" => array(-1, 0, 1)),
		);
	}
}