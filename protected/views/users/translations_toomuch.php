<?php
	/**
	 * @var integer $cache_time
	 * @var CActiveDataProvider $translations
	 * @var User $user
	 */

	Yii::app()->clientScript
		->registerScriptFile("/js/profile.js")->registerCssFile("/css/profile.css?3");

	$this->pageTitle = $user->login . ": переводы";

	$this->renderPartial("profile_head", array("user" => $user, "h1" => "переводы"));
?>
<div class="alert-block">
	К сожалению, <?=$user->login . " написал" . $user->sexy(); ?> слишком много версий перевода и мы не можем их тут пока показать.
</div>