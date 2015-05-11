<?php
class TextSource extends CFormModel {
	public $src_type = 1, $text, $file, $encoding, $url, $chopper;

	public $choppers = array(1 => "по переносу строки", 2 => "по двум переносам строки", 0 => "не разбивать вообще, я сделаю это вручную");
	public $choppers_delim = array(1 => "\n", 2 => "\n\n");

	public function rules() {
		return array(
			array("src_type", "in", "range" => array(1, 2)),
			array("text", "safe"),
			array("file", "safe"),
			array("encoding", "in", "range" => array_keys(Yii::app()->params["encodings"])),
			array("url", "safe"),
			array("chopper", "in", "range" => array_keys($this->choppers)),
		);
	}

	public function attributeLabels() {
		return array(
			"file" => "Файл",
			"chopper" => "Разбить на фрагменты",
			"encoding" => "Кодировка",
		);
	}

	protected function afterValidate() {
		switch($this->src_type) {
			case 1:
				$this->text = str_replace("\r", "", $this->text);
				break;
			case 2:
				// Читаем файл
				$file = CUploadedFile::getInstanceByName("TextSource[file]");
				$this->text = file_get_contents($file->tempName, false, null, -1, 500 * 1024);
				if($this->text === false) {
					$this->addError("file", "Файл не загрузился. Возможно, он слишком велик.");
					return false;
				}

				// Кодировка
				if($this->encoding != "UTF-8") {
					$this->text = iconv($this->encoding, "UTF-8//IGNORE", $this->text);
				} elseif(!mb_check_encoding($this->text, "utf-8")) {
					$this->addError("encoding", "Неправильная кодировка текста, выберите правильную.");
				}
				break;
			case 3:
				exit;
				if(!preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $this->url)) {
					$this->addError("url", "Некорректный URL. Должно быть что-то вроде http://someawesomesite.ru/foo/bar/baz.html");
					return false;
				}

				echo "<pre>";
				echo "Download text from '{$this->url}'\n";

				$html = Yii::app()->curl->run($this->url);
				file_put_contents("import.html", $html);

				print_r(Yii::app()->curl->info);
				echo "<hr />";

				// Пытаемся узнать кодировку, если её не указали явно
				if(preg_match('#<meta.*http-equiv=[\'"]?content-type[\'"]?.*>#is', $html, $res)) {
					echo "Got meta http-equiv: " . htmlspecialchars($res[0]) . "\n";
				} elseif(preg_match('#<meta.*charset=\'content-type\'.*>#is', $html, $res)) {
					echo "Got meta charset: " . htmlspecialchars($res[0]) . "\n";
				} elseif(Yii::app()->curl->info["content_type"] != "" && preg_match('#charset=([^; ]+)#i', Yii::app()->curl->info["content_type"], $res)) {
					echo "Found content-type header with charset: {$res[0]}\n";
					$charset = $res[1];
				} else {
					echo "Can't find encoding information (assume ISO)\n";
					$charset = "ISO−8859−1";
				}
				$charset = strtoupper($charset);
				$this->encoding = $charset;

				if($charset != "UTF-8" && $charset != "UTF8") {
					$html = iconv($charset, "UTF-8", $html);
				} elseif(!mb_check_encoding($html, "utf-8")) {
					$this->addError("encoding", "Неправильная кодировка текста, выберите правильную.");
				}

				$html = preg_replace('#<head>.+</head>#isU', '', $html);
				$html = preg_replace('#<script[^>]*>.+</script[^>]*>#isU', '', $html);
//				$html = preg_replace('#<style[^>]*>.+</style[^>]*>#isU', '', $html);

				echo "<b>HTML</b> = '" . htmlspecialchars($html) . "'";

				$p = new CHtmlPurifier();
				// идея в том, чтобы оставить только блочные теги, а потом по ним разбить на фрагменты
				$html = preg_replace('/\s+/s', " ", $html);        // привели всё к одной строке
				$html = preg_replace('#</[^>]*>#s', " ", $html);   // убрали все закрывающие теги
				$html = preg_replace('/<(p|div|li|dd|dt|h\d|address|blockquote)[^>]*>/s', "\n\n", $html); // все открывающие блочные теги - в два переноса строки
				$html = preg_replace('/<br[^>]*>/s', "\n", $html); // br - в одинарный перенос строки
				$html = strip_tags($html);
				$html = preg_replace('/[ \t]+/', " ", $html);

				echo "</pre>";

				$this->text = $html;
				$this->chopper = 2;

				break;
			default:
				$this->addError("src_typ", "Системная ошибка. Обратитесь в техподдержку.");
				return false;
				break;
		}

		$this->text = trim($this->text);

		if($this->text == "") {
			$this->addError("src_typ", "Текст не обнаружен. Иногда это случается, если выбрать неправильную кодировку.");
			return false;
		}

		if(mb_strlen($this->text) > 500 * 1024) {
			$this->addError("src_typ", "Слишком большой текст. Пожалуйста, разбейте его на несколько глав, не более 500 КБ каждая.");
			return false;
		}

		return parent::afterValidate();
	}

	/**
	 * @param Chapter $chap
	 */
	public function prepareText($chap) {
		if($this->chopper != 0) {
			$text = explode($this->choppers_delim[$this->chopper], $this->text);
			$n_long = 0;
			foreach($text as $i => $p) {
				if(trim($p) == "") unset($text[$i]);
				if(mb_strlen($p) > 1024) {
					$n_long++;
				};
			}

			$n_verses = count($text);
		} else {
			$text = array($this->text);
			$n_verses = 1;
			if(mb_strlen($this->text) > 1024) $n_long = 1;
			else $n_long = 0;
		}

		$warnings = array();
		if($n_long > 0)
			$warnings[] = "В тексте есть <strong>" . Yii::t("app", "{n} слишком длинный абзац.|{n} слишком длинных абзаца|{n} слишком длинных абзецев", $n_long) . "</strong>, они выделены красной полосой слева. Их будет неудобно переводить, разбейте их на менее крупные.";
		if(count($text) > 5000)
			$warnings[] = "Текст разбился на <strong>" . Yii::t("app", "{n} фрагмент|{n} фрагмента|{n} фрагментов", $n_verses) . "</strong>, возможно, вам стоит разделить текст на несколько глав.";
		if(count($warnings)) {
			Yii::app()->user->setFlash("warning", join("<br />", $warnings));
		}

		return $text;
	}

	public function getErrorsString() {
		$t = "";
		foreach($this->getErrors() as $field => $errors) {
			$t .= join("\n", $errors);
		}

		return $t;
	}

}