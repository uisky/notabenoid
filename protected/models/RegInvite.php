<?php

/**
 * @property int $id
 * @property int $from_id
 * @property int $to_id
 * @property string $to_email
 * @property string $cdate
 * @property string $code
 * @property string $message

 * @property User $sender
 * @property User $buddy
 */
class RegInvite extends CActiveRecord {
	const CODE_LENGTH = 80;

	public $type, $clue, $giveInvites = 0;
	public $foundBuddy;

	/**
	 * @param string $className
	 * @return RegInvite
	 */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function tableName() {
		return "reg_invites";
	}

	/**
	 * Генерирует новый инвайт от юзера $user и пишет его в базу, уменьшает и сохраняет $user->n_invites.
	 * Инициализирует поля from_id и code.
	 * @param User $user
	 * @return RegInvite
	 */
	public static function gen($user) {
		$invite = new self();
		$invite->from_id = $user->id;

		$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';
		$n = strlen($alphabet);
		do {
			$invite->code = '';
			for($i = 0; $i < self::CODE_LENGTH; $i++) $invite->code .= $alphabet[rand(0, $n - 1)];
			$exists = Yii::app()->db->createCommand("SELECT 1 FROM reg_invites WHERE from_id = :user_id and code = :code")
				->queryScalar(["user_id" => $user->id, "code" => $invite->code]);
		} while($exists);

		return $invite;
	}

	public function rules() {
		return [
			["message", "filter", "filter" => "trim"],
			["message", "filter", "filter" => "strip_tags"],

			["type", "in", "range" => ["user", "new"]],
			["clue", "validateClue"],
			["giveInvites", "validateGiveInvites"],
		];
	}

	public function relations() {
		return [
			"sender" => [self::BELONGS_TO, "User", "from_id"],
			"buddy" => [self::BELONGS_TO, "User", "to_id"],
		];
	}

	public function validateClue($param, $options) {
		if($this->type == "user") {
			$this->foundBuddy = User::model()->byLogin($this->$param)->find();
			if(!$this->foundBuddy) {
				$this->addError("clue", "Пользователь с таким логином не найден.");
				return;
			} elseif($this->foundBuddy->can(User::CAN_LOGIN)) {
				$this->addError("clue", "Пользователь {$this->buddy->ahref} уже является членом клуба.");
				return;
			} else {
				$this->to_id = $this->foundBuddy->id;
				$this->to_email = $this->foundBuddy->email;
			}
		} elseif($this->type == "new") {
			$this->foundBuddy = User::model()->findByAttributes(["email" => $this->$param]);
			$this->to_email = $this->$param;
			if($this->foundBuddy) {
				if($this->foundBuddy->can(User::CAN_LOGIN)) {
					$this->addError("clue", "Этот пользователь уже зарегистрирован на Нотабеноиде и является членом клуба.");
					return;
				} else {
					$this->to_id = $this->foundBuddy->id;
				}
			}
		} else {
			$this->addError("type", "Ошибка формы");
		}

		// Проверяем, не приглашали ли мы его уже ранее
		$i = Yii::app()->db->createCommand("SELECT 1 FROM reg_invites WHERE from_id = :from_id AND to_id = :to_id")
			->queryScalar(["from_id" => $this->from_id, "to_id" => $this->foundBuddy->id]);
		if($i) {
			$this->addError("clue", "Вы уже приглашали этого пользователя.");
			return;
		}
	}

	public function validateGiveInvites($attr, $params) {
		if(!Yii::app()->user->can("admin")) return;

		if($this->foundBuddy) {
			$this->foundBuddy->n_invites += (int) $this->$attr;
			$this->foundBuddy->save(false, ["n_invites"]);
		}
	}

	public function getUrlAccept() {
		return "http://" . Yii::app()->params["domain"] . "/register/?u={$this->from_id}&invite=" . urlencode($this->code);
	}

	public function sendMail() {
		$msg = new YiiMailMessage("{$this->sender->login} приглашает вас в Закрытый Клуб Переводчиков");
		$msg->view = $this->to_id ? "reg_invite_user" : "reg_invite_new";
		$msg->from = Yii::app()->params['adminEmail'];
		$msg->addTo($this->to_email);
		$msg->setBody(array("invite" => $this), "text/html");
		return Yii::app()->mail->send($msg);
	}
}