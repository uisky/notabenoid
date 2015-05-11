<?php
/**
 * @property int $book_id
 * @property string $cdate
 * @property string $title
 * @property string $url
 * @property string $email
 * @property string $message
 *
 * @property Book $book
 */
class BookBanReason extends CActiveRecord {
	/** @returns Dict */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function tableName() { return "book_ban_reasons"; }

	public function relations() {
		return [
			"book" => [self::BELONGS_TO, "Book", "book_id"],
		];
	}

	public function rules() {
		return array(
			array("title, url, email, message", "safehtml"),
		);
	}

	public function safehtml($attr, $params) {
		$p = new CHtmlPurifier();
		$p->options = Yii::app()->params["HTMLPurifierOptions"];
		$this->$attr = trim($p->purify($this->$attr));
	}
}