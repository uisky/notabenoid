<?php
class ImportOptionsSubs extends CFormModel {
	public $src, $format, $encoding;

	public function rules() {
		return array(
			// login and pass are required
			array("src", "file", "message" => "Пожалуйста, выберите файл.", "maxSize" => 1024 * 1024, "minSize" => 1,
				"tooLarge" => "Файл слишком большой", "tooSmall" => "Файл подозрительно малелький",
			),
			array("format", "required"),
			array("encoding", "required"),
		);
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels() {
		return array(
			"src" => "Файл с субтитрами",
			"format" => "Формат",
			"encoding" => "Кодировка",
		);
	}

}
