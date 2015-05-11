<?php
/**
 * @property int $id
 * @property int $book_id
 * @property int $user_id
 * @property string $cdate
 * @property string $term
 * @property string $descr
 *
 * @property Book $book
 * @property User $user
 */
class Dict extends CActiveRecord {
	/** @returns Dict */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function tableName() {
		return "dict";
	}

	public function relations() {
		return array(
			"book"      => array(self::BELONGS_TO, "Book", "book_id"),
			"user"      => array(self::BELONGS_TO, "User", "user_id"),
		);
	}

	public function rules() {
		return array(
			array("term, descr", "required"),
			array("term, descr", "clean"),
		);
	}

	public function clean($attr, $params) {
		return trim(strip_tags($this->$attr));
	}

	public function attributeLabels() {
		return array(
			"term" => "Слово",
			"descr" => "Перевод",
		);
	}

	public function book($book_id) {
		$book_id = (int) $book_id;

		$this->getDbCriteria()->mergeWith(array(
			"condition" => "t.book_id = '{$book_id}'",
		));

		return $this;
	}

	public function getErrorsString() {
		$t = "";
		foreach($this->getErrors() as $field => $errors) {
			$t .= join("\n", $errors);
		}

		return $t;
	}
}