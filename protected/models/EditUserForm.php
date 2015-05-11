<?php
class EditUserForm extends CFormModel {
	public $name, $icq, $lj, $url, $skype, $bdate_y, $bdate_m, $bdate_d, $country_id, $city, $bio, $upic;
	
	private $bdate = "";
	private $user;
	
	public static $Properties = array(
		1 => array('Имя', 					'name', 60, 60),
		2 => array('ICQ', 					'icq', 16, 16),
		3 => array('ЖЖ', 					'lj', 16, 16),
		4 => array('Домашняя страница', 	'url', 255, 60),
		5 => array('skype', 				'skype', 32, 16),
		6 => array('Дата рождения', 		'bdate', 10, 16),
		7 => array('Страна', 				'country_id', 'не скажу'),
		8 => array('Город', 				'city', 60, 60),
		9 => array('Несколько слов о себе', 'bio', 3, 60, true),
	);

	public function rules() {
		return array(
			array("name, icq, lj, url, skype, bdate_y, bdate_m, bdate_d, country_id, city, bio", "safe"),
			array("name, icq, lj, url, skype, city", "clean"),
			array("bio", "safehtml"),
			array("bdate_y, bdate_m, bdate_d, country_id", "numerical", "integerOnly" => true),
			array("icq", "match", "pattern" => '/^[\d -]+$/', "message" => "номер icq может состоять только из цифр и дефисов"),
			array("lj", "match", "pattern" => '/^[a-z\d_-]+$/i', "message" => "введите ваш ник в ЖЖ"),
			array("url", "url", "defaultScheme" => "http", "message" => "это не похоже на адрес сайта"),
			array("upic", "file", "types" => "jpg, gif, png, jpeg", "wrongType" => "Неверный формат файла. Пожалуйста, загружайте JPG, PNG или GIF"),
		);
	}
	
	public function attributeLabels() {
		return array(
			"name" => "Имя",
			"icq" => "ICQ",
			"lj" => "ЖЖ",
			"url" => "Домашняя страница",
			"skype" => "Skype",
			"bdate" => "День рожденья",
			"country_id" => "Страна",
			"city" => "Город",
			"bio" => "Несколько слов о себе",
			"upic" => "Фотография"
		);
	}
	
	public function clean($attr, $params) {
		$this->$attr = trim(htmlspecialchars(strip_tags($this->$attr, ENT_QUOTES | ENT_HTML5)));
	}
	
	public function safehtml($attr, $params) {
		$p = new CHtmlPurifier();
		$p->options = Yii::app()->params["HTMLPurifierOptions"];
		$this->$attr = trim($p->purify($this->$attr));
	}
	
	protected function setUser($user) {
		$this->user = $user;
		
		$r = Yii::app()->db->createCommand("SELECT prop_id, value FROM userinfo WHERE uid = :uid")->query(array(":uid" => $this->user->id));
		foreach($r as $row) {
			$attr = self::$Properties[$row["prop_id"]][1];
			if($attr == "bdate") {
				list($this->bdate_y, $this->bdate_m, $this->bdate_d) = sscanf($row["value"], "%d-%d-%d");
			}
			$this->$attr = $row["value"];
		}
	}

	public function save() {
		if(!$this->validate()) return false;
		
		// bdate
		$this->bdate = sprintf("%04d-%02d-%02d", $this->bdate_y, $this->bdate_m, $this->bdate_d);
		
		// userinfo
		Yii::app()->db->createCommand("DELETE FROM userinfo WHERE uid = :uid")->execute(array(":uid" => $this->user->id));
		foreach(self::$Properties as $prop_id => $P) {
			$attr = $P[1];
			Yii::app()->db->createCommand()->insert("userinfo", array("uid" => $this->user->id, "prop_id" => $prop_id, "value" => $this->$attr));
		}
		
		// аватар
		
		return true;
	}
}