<?php
class ModerUtils extends CApplicationComponent {
	public $moderators = array("notabenoid");

	public function init() {

	}

	public function getAmI() {
		return !Yii::app()->user->isGuest && in_array(Yii::app()->user->login, $this->moderators);
	}
}
?>
