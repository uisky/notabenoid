<?php
class SearchFilter extends CFormModel {
	public $t = "", $cat = 0, $s_lang = 0, $t_lang = 0, $ready = false, $gen = false, $tr = false, $sort = 4;
	public $topic = 0;
	public $category = null;

	public static $sortOptions = array(
		0 => "По степени готовности",
		1 => "По названию на языке оригинала",
		2 => "По названию на языке перевода",
		3 => "По дате создания",
		4 => "По дате последней активности",
	);

	public static $sortSQL = array(
		0 => "ready(t.n_verses, t.d_vars) desc",
		1 => "t.s_title",
		2 => "t.t_title",
		3 => "t.cdate desc",
		4 => "t.last_tr desc NULLS LAST",
	);

	public function beforeValidate() {
		foreach(array("cat", "s_lang", "t_lang", "ready", "gen", "tr", "sort") as $k) $this->$k = (int) $this->$k;
		if($this->cat) {
			$this->category = Category::model()->findByPk($this->cat);
		}
		return true;
	}

	public function getFilterTitle($attr) {
		$html = array(
			"t" => "<a>Название или описание содержит текст</a>",
			"cat" => '<a href="#" onclick="return S.catChoose()">Из раздела каталога</a>',
			"s_lang" => "<a>Язык оригинала</a>",
			"t_lang" => "<a>Язык перевода</a>",
			"ready" => "<a>100% готовые</a>",
			"gen" => "<a>Доступные для скачавания</a>",
			"tr" => "<a>Доступные для перевода</a>",
		);
		return $html[$attr];
	}

	public function getFilterHtml($attr) {
		if($attr == "cat") {
			if(!$this->category) return "";
			return '<input type="hidden" name="cat" value="' . $this->category->id . '" />Из раздела каталога &laquo;<span class="name">' . $this->category->title . '</span>&raquo;';
		}
		$html = array(
			't' => '<input type="text" name="t" class="span8" />',
			'cat' => '<input type="hidden" name="cat" />Из раздела каталога &laquo;<span class="name"></span>&raquo;',
			's_lang' => 'Язык оригинала: <select name="t_lang">' . Yii::app()->langs->options(Langs::FORM_INF) . '</select>',
			't_lang' => 'Язык перевода: <select name="t_lang">' . Yii::app()->langs->options(Langs::FORM_INF) . '</select>',
			'ready' => '<label><input type="checkbox" name="ready" value="1" checked /> готовые на 100%</label>',
			'gen' => '<label><input type="checkbox" name="gen" value="1" checked /> доступные для скачивания</label>',
			'tr' => '<label><input type="checkbox" name="tr" value="1" checked /> доступные для перевода</label>',
		);
		return $html[$attr];
	}

	public function getHasOptions() {
		return  $this->cat || $this->s_lang || $this->t_lang || $this->ready || $this->gen || $this->tr;
	}

	public function getDoSearch() {
		return !empty($this->t) || !empty($this->cat) || !empty($this->s_lang) || !empty($this->t_lang);
	}

	public function rules() {
		return array(
			array("t", "filter", "filter" => "strip_tags", "on" => "search"),
			array("t", "filter", "filter" => "trim", "on" => "search"),
			array("cat", "numerical", "integerOnly" => true),
			array("s_lang, t_lang", "numerical", "integerOnly" => "true"),
			array("gen, tr", "boolean"),
			array("ready", "boolean", "on" => "search"),
			array("ready", "numerical", "integerOnly" => true, "on" => "announces"),
			array("sort", "in", "range" => array_keys(self::$sortOptions), "on" => "search"),
			array("topic", "numerical", "integerOnly" => true, "on" => "announces"),
		);
	}

	public function attributeLabels() {
		return array(
			"t" => "Название",
			"s_lang" => "Язык оригинала",
			"t_lang" => "Язык перевода",
			"cat" => "Из раздела каталога",
		);
	}
}
?>
