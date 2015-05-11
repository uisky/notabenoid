<?php
class InviteForm extends CFormModel {
	public $email, $who;

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules() {
		return array(
			// login and pass are required
			array("email", "required", "message" => "Пожалуйста, введите адрес вашего друга."),
			array("email", "email", "message" => "Это не похоже на адрес электронной почты, проверьте ещё раз."),
			array("who", "required", "message" => "Пожалуйста, подпишитесь."),
			array("who", "filter", "filter" => "htmlspecialchars"),
		);
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels() {
		return array(
			"email" => "Введите e-mail Вашего друга, и ему отправится красивое приглашение с Вашими данными:",
			"who" => "Как Вас представить?"
		);
	}

}
