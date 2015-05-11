<?php

/**
 * Временные токены для генерирования нового пароля. Отсылаются на электронную почту по запросу
 * на восстановление пароля.
 *
 * Токен считается валидным, пока:
 *   1) now() - cdate > interval '1 day'
 *	 2) attempts <= 0
 *
 * @property int $user_id
 * @property string $cdate
 * @property string $code
 * @property int $attempts
 */
class RemindToken extends CActiveRecord {
	const CODE_LENGTH = 20;

	/**
	 * @param string $className
	 * @return RemindToken
	 */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function tableName() {
		return "remind_tokens";
	}

	/**
	 * Возвращает существующий валидный токен для юзера или генерирует новый
	 * @param User $user
	 * @return RemindToken
	 */
	public static function gen($user) {
		Yii::app()->db->createCommand("DELETE FROM remind_tokens WHERE user_id = :user_id")->execute(["user_id" => $user->id]);

		$token = new self();
		$token->user_id = $user->id;

		$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';
		$n = strlen($alphabet);
		$token->code = '';
		for($i = 0; $i < self::CODE_LENGTH; $i++) $token->code .= $alphabet[rand(0, $n - 1)];

		$token->save();

		return $token;
	}

	public function check($code) {
		if($this->code == $code) return true;

		$this->attempts--;

		if($this->attempts <= 0) $this->delete();
		else $this->save();

		return false;
	}

	public function getUrl() {
		return "http://" . Yii::app()->params["domain"] . "/register/reset?u={$this->user_id}&c=" . urlencode($this->code);
	}
}