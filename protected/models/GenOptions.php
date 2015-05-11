<?php
class GenOptions extends CFormModel {
	public $format;
	public $algorithm = 0;
	public $skip_neg = false;
	public $untr = "o";
	public $enc = "UTF-8";
	public $crlf = 0;
	public $author_id = 0;

	/** @var Chapter */
	public $chap;

	public static $format_options = array(
		"S" => array(
			"s" => "SubRipper (*.srt)",
			"m" => "SAMI (*.smi)",
			"b" => "SubViewer (*.sub)",
		),
		"A" => array(
			"h" => "HTML",
			"t" => "Текстовый файл",
		),
	);
	public static $extensions = array(
		"s" => "srt", "m" => "smi", "b" => "sub",
		"h" => "html", "t" => "txt",
	);
	public static $algorithm_options = array(
		0 => "Версии с максимальным рейтингом",
		1 => "Самые свежие версии",
	);
	public static $untr_options = array(
		"o" => "Подставить фрагмент оригинала",
		"s" => "Пропустить",
	);
	public static $crlf_options = array(
		0 => "Windows (0D 0A)",
		1 => "Unix (0A)",
		2 => "Mac (0D)",
	);

	const COOKIE_NAME = "genopts";

	public function rules() {
		return array(
			array('format', 'required'),
			array("format", "in", "range" => array_keys(self::$format_options[$this->chap->book->typ])),
			array("algorithm", "in", "range" => array_keys(self::$algorithm_options)),
			array("skip_neg", "boolean"),
			array("untr", "in", "range" => array_keys(self::$untr_options)),
			array("enc", "in", "range" => array_keys(Yii::app()->params["encodings"])),
			array("crlf", "in", "range" => array_keys(self::$crlf_options)),
			array("author_id", "numerical", "integerOnly" => true),
		);
	}

	protected function afterValidate() {
		if(!isset(self::$format_options[$this->chap->book->typ][$this->format])) {
			$this->format = $this->chap->book->typ == "S" ? "s" : "h";
		}
	}

	public function getEol() {
		$eols = array(0 => "\r\n", 1 => "\n", 2 => "\r");
		return $eols[$this->crlf];
	}

	public function saveOptions() {
		$name = self::COOKIE_NAME . $this->chap->book->typ;
		$text = "{$this->format}.{$this->algorithm}.{$this->skip_neg}.{$this->untr}.{$this->enc}.{$this->crlf}";

		$cookie = new CHttpCookie($name, $text);
		$cookie->expire = time() + 60*60*24*365;
		Yii::app()->request->cookies[$name] = $cookie;
	}

	public function loadOptions() {
		$name = self::COOKIE_NAME . $this->chap->book->typ;
		if(!isset(Yii::app()->request->cookies[$name])) return false;

		$text = Yii::app()->request->cookies[$name]->value;
		list($this->format, $this->algorithm, $this->skip_neg, $this->untr, $this->enc, $this->crlf) = explode(".", $text);

		return true;
	}

	/**
	 * Возвращает ключ для ReadyCache, если перевод нельзя кешировать, то false
	 */
	public function getRcKey() {
//		if($this->author_id != 0) return false;
		return $this->chap->id . "~" . $this->format . "~" . $this->algorithm . "~" . $this->skip_neg . "~" . $this->untr . "~" . $this->enc . "~" . $this->crlf . "~" . $this->author_id;
	}
}