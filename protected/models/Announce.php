<?php
class Announce extends BlogPost {
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function rules() {
		return array(
			array("body", "required", "message" => "Пожалуйста, введите текст анонса."),
			array("body", "length", "max" => 4096, "tooLong" => "Текст анонса не может быть длиннее 4 килобайт."),
			array("body", "safehtml"),
			array("topics", "required", "message" => "Пожалуйста, выберите рубрику."),
			array("topics", "in", "range" => array_keys(Yii::app()->params["blog_topics"]["announce"])),
		);
	}

	public function safehtml($attr, $params) {
		$p = new CHtmlPurifier();
		$p->options = Yii::app()->params["HTMLPurifierOptions"];
		$p->options["HTML.Allowed"] = "a[href],b,strong,i,em,u,small,sub,sup";
		$this->$attr = trim($p->purify($this->$attr));
	}

	public function attributeLabels() {
		return array(
			"body" => "Текст анонса",
			"topics" => "Рубрика"
		);
	}

	public function getWasToday() {
		return Yii::app()->db->createCommand("SELECT 1 FROM blog_posts WHERE book_id = :book_id AND (topics BETWEEN 80 AND 89) AND cdate::date = current_date LIMIT 1")
			->queryScalar(array(":book_id" => $this->book_id));
	}

	public function afterValidate() {
		if($this->isNewRecord) {
			if($this->wasToday) {
				$this->addError("body", "Нельзя анонсировать переводы чаще, чем один раз в сутки.");
			}
		}

		parent::afterValidate();
	}
}
?>