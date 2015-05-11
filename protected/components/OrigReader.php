<?php
class OrigReader {
	public static function factory($options, $chap) {
		if($chap->book->typ == "S") {
			if($options->format == "srt") {
				return new OrigReaderSRT($options, $chap);
			} else {
				throw new Exception("Format '{$options->format}' is not supported");
			}
		} elseif($chap->book->typ == "A") {
			if($options->format == "txt") {
				return new OrigReaderTXT($options, $chap);
			} else {
				throw new Exception("Format '{$options->format}' is not supported");
			}
		} else {
			throw new Exception("This book type '{$chap->book->typ}' is not supported");
		}
	}
}

interface IOrigReader {
	public function __construct($options, $chap);
	public function getNextVerse();
}

class OrigReaderException extends Exception {}

class OrigReaderSRT implements IOrigReader {
	/** @var Chapter $chap */
	public $chap;
	/** @var ImportOptionsSubs */
	public $options;
	/** @var resource */
	public $fh;
	public $line = 0;
	public $errorMessage = "";

	public function __construct($options, $chap) {
		$this->chap = $chap;
		$this->options = $options;
	}

	public function init() {
		$this->purifier = new CHtmlPurifier();
		$this->purifier->options = array(
			"HTML.Allowed" => "b,strong,i,em,u",
		);

		if($this->options->src instanceof CUploadedFile) {
			$this->fh = fopen($this->options->src->tempName, "r");

			// Если UTF8, то пропускаем bom, если он есть
			if($this->options->encoding == "UTF-8") {
				$bom = fread($this->fh, 3);
				if($bom != pack("CCC", 0xef, 0xbb, 0xbf)) fseek($this->fh, 0, SEEK_SET);
			}
		}

		return true;
	}

	public function is_eod() {
		return feof($this->fh);
	}

	private $purifier;
	private function getline() {
		if($this->is_eod()) return false;
		$t = fgets($this->fh);
		$this->line++;

		if($this->options->encoding != "UTF-8") {
			$t = iconv($this->options->encoding, "UTF-8//IGNORE", $t);
		} elseif(!mb_check_encoding($t, "utf-8")) {
			$this->raiseError("Неправильная кодировка текста, выберите правильную.");
		}

		$t = trim($t);

		return $t;
	}

	public function hasError() {
		return $this->errorMessage != "";
	}

	public function getError() {
		return $this->errorMessage;
	}

	public function raiseError($msg, $line = null) {
		if($line === null) $line = $this->line;

		if($line != 0) $this->errorMessage = "Ошибка в исходных данных в строке {$this->line}: ";
		$this->errorMessage .= "{$msg}";

		throw new OrigReaderException($this->errorMessage);
	}

	public function getNextVerse() {
		$orig = new Orig();
		$orig->chap = $this->chap;
		$orig->chap_id = $this->chap->id;

		// Номер (или конец файла)
		$t = $this->getline();
		if($this->is_eod()) return false;
		if(!is_numeric($t)) {
			$this->raiseError("Ожидается число, получено '{$t}'");
		}

		// Тайминг
		$t = $this->getLine();
		if(!preg_match('/^(\d{1,2}:\d{1,2}:\d{1,2}[,.]\d{1,3})\s*-->\s*(\d{1,2}:\d{1,2}:\d{1,2}[,.]\d{1,3})$/', $t, $res)) {
			$this->raiseError("Ожидается тайм-код, получено '{$t}'");
		}
		$orig->t1 = strtr($res[1], ",", ".");
		$orig->t2 = strtr($res[2], ",", ".");

		// Текст до первой пустой строки
		do {
			$t = $this->getLine();
			$orig->body .= $t . "\n";
		} while($t !== false && $t != "");
		$orig->body = trim($this->purifier->purify($orig->body));

		return $orig;
	}
}

