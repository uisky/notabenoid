<?php
class ModerBookCat extends CActiveRecord {
	/** @return Book */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function tableName() {
		return "moder_book_cat";
	}

	public function relations() {
		return array(
			"book" => array(self::BELONGS_TO, "Book", "book_id"),
		);
	}
}
?>