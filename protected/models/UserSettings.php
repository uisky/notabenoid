<?php
class UserSettings extends User {
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public $old_pass, $new_pass, $new_pass2;
	public $sex, $email;
	public $set_ini;

	public function rules() {
		return array_merge(parent::rules(), array(
			array("email", "required"),
			array("email", "length", "max" => 255, "tooLong" => "Слишком длинный адрес электронной почты"),
			array("email", "email", "checkPort" => false, "message" => "Неверный адрес электронной почты."),
			array("email", "unique",
				"className" => "User",
				"caseSensitive" => false,
				"criteria" => new CDbCriteria(array(
					"condition" => "id != " . Yii::app()->user->id
				)),
				"message" => "Пользователь с таким адресом электронной почты уже зарегистрирован."
			),

			array("sex", "in", "range" => array("x", "m", "f"), "message" => "Вы должны быть либо мужчиной, либо, ещё лучше, женщиной."),

			array("new_pass, new_pass2", "filter", "filter" => "trim"),
			array("new_pass, new_pass2", "length", "min" => 5, "max" => 32, "tooShort" => "Слишком короткий новый пароль.", "tooLong" => "Слишком длинный новый пароль."),
			array("new_pass2", "compare", "compareAttribute" => "new_pass", "message" => "Новые пароли не совпадают, вы где-то опечатались."),
			array("old_pass", "change_pass"),

			// array("ini", "type", "type" => "array"),
			array("set_ini", "set_ini"),
		));
	}

	public function set_ini($param, $options) {
		foreach($this->$param as $k => $v) $this->ini[$k] = $v;
	}

	public function change_pass($param, $options) {
		echo "<h3>change_pass</h3>";
		if(empty($this->$param)) return;
		if($this->hasErrors()) return;
		if(empty($this->new_pass)) {
			$this->addError("new_pass", "Введите новый пароль!");
			return;
		}
		if(!$this->validate(array("new_pass", "new_pass2"))) return;

		echo "<h3>check old pass</h3>";
		$ui = new UserIdentity(Yii::app()->user->login, $this->old_pass);
		if(!$ui->authenticate()) {
			$this->addError("old_pass", "Неверный пароль. Если вы не можете его вспомнить, вам <a href='/register/remind'>сюда</a>.");
		} else {
			echo "<p>check ok</p>";
			$this->pass = self::hashPass($this->new_pass);
		}
		echo "<h3>/change_pass</h3>";
	}

	public function attributeLabels() {
		return array(
			"old_pass" => "Старый пароль",
			"new_pass" => "Новый пароль",
			"new_pass2" => "Ещё раз",
			"sex" => "Итак, я &mdash;",
			"email" => "Присылать на почту",
			"set_ini[" . User::INI_ADDTHIS_OFF . "]" => "не показывать кнопку для добавления в социальные сети и закладки",
		);
	}

	protected function afterFind() {
		parent::afterFind();

		$this->set_ini = $this->ini;
	}
}