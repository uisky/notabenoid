<?php
	class Langs extends CApplicationComponent {
		public $Langs = array();

		public $Types = array(
			10 => "Основные",
			20 => "Народы СССР",
			30 => "Европа",
			40 => "Азия",
			50 => "Америка",
			60 => "Африка",
			70 => "Австралия и Океания",
			200 => "Прочие",
		);

		const FORM_INF = 0; // Инфинитив, "русский"
		const FORM_GEN = 1;	// Родительный падеж, "русского"

		public function init() {
			$r = Yii::app()->db->createCommand("SELECT id, typ, title, title_r FROM languages ORDER BY typ, title")->query();
			foreach($r as $row) {
				$this->Langs[$row['id']] = array(
					self::FORM_INF => $row['title'],
					self::FORM_GEN => $row['title_r'],
					't' => $row['typ']
				);
			}
		}

		public function options($form = self::FORM_INF, $default = null) {
			$prev_typ = -1;
			$html = "";
			foreach($this->Langs as $k => $V) {
				if($prev_typ != $V['t']) {
					if($prev_typ != -1) $html .= "</optgroup>\n";

					$html .= "<optgroup label='" . $this->Types[$V['t']] . "'>\n";
					$prev_typ = $V['t'];
				}
				$html .= "<option value='{$k}'" . ($k == $default ? " selected='selected'" : "") . ">{$V[$form]}</option>\n";
			}
			return $html;
		}

		public function select($form = self::FORM_INF) {
			$A = array();

			foreach($this->Types as $type => $type_title) {
				$A[$type_title] = array();
				foreach($this->Langs as $id => $L) {
					if($L["t"] == $type) $A[$type_title][$id] = $L[$form];
				}
			}

			return $A;
		}

		public function inf($id) {
			return $this->Langs[$id][self::FORM_INF];
		}

		public function gen($id) {
			return $this->Langs[$id][self::FORM_GEN];
		}


		const FORMAT_LONG = 0;	// с английского на русский
		const FORMAT_ABBR = 1; // анг -> рус
		/**
		 * Возвращает строку "с [языка] на [язык]"
		 *
		 * @param int $from_lang_id - ID языка оригинала
		 * @param int $to_lang_id - ID языка перевода
		 * @param int $format - формат
		 * @return String
		 */
		public function from_to($from_lang_id, $to_lang_id, $format = self::FORMAT_LONG) {

			if($format == self::FORMAT_ABBR) {
				$ret = mb_substr($this->Langs[$from_lang_id][self::FORM_GEN], 0, 3);
				if($to_lang_id != 0) {
					$ret .= " &rarr; " . mb_substr($this->Langs[$to_lang_id][self::FORM_INF], 0, 3);
				}
			} else {
				$ret = "с ";
				$ret .= $this->Langs[$from_lang_id][self::FORM_GEN] . " на ";
				if($to_lang_id == 0) $ret .= "все языки мира";
				else $ret .= $this->Langs[$to_lang_id][self::FORM_INF];
			}

			return $ret;
		}

	}
?>
