<?php
/**
 * @var UsersController $this
 * @var User $user
 * @var RemindToken $remindToken
 * @var RegInvite[] $sentInvites
 */
Yii::app()->clientScript
	->registerScriptFile("/js/profile.js")->registerCssFile("/css/profile.css?3")
	->registerScript("profile", "Profile.uid = {$user->id};", CClientScript::POS_HEAD);

$this->pageTitle = $user->login . ": редактирование";

$this->renderPartial("profile_head", array("user" => $user, "h1" => "редактирование"));

echo CHtml::errorSummary($user, "<div class='alert alert-box alert-danger'>", "</div>");
?>
<form method="post" class="form-horizontal">
	<div class="control-group">
		<label class="control-label">E-mail:</label>
		<div class="controls">
			<?=CHtml::activeTextField($user, "email", ["class" => "span6"]); ?>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label">Пол:</label>
		<div class="controls">
			<?=CHtml::activeDropDownList($user, "sex", ["m" => "мужчина", "f" => "женщина", "x" => "существо", "-" => "удалён"]); ?>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label">Может:</label>
		<div class="controls">
			<?php
			$abilities = [
				User::CAN_LOGIN    => "Логиниться",
				User::CAN_RATE     => "Ставить оценки переводам",
				User::CAN_COMMENT  => "Писать комментарии в блоге",
				User::CAN_PMAIL    => "Писать письма",
				User::CAN_POST     => "Писать посты в блоге",
				User::CAN_MODERATE => "Модерировать блог",
				User::CAN_TRANSLATE    => "Переводить",
				User::CAN_CREATE_BOOKS => "Создавать переводы",
				User::CAN_ANNOUNCE     => "Создавать анонсы",
			];
			foreach($abilities as $i => $title) {
				echo "<label class='checkbox'>";
				echo CHtml::checkBox("can[]", $user->can($i), ["value" => $i]);
				echo $title . "</label>\n";
			}
			?>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label">Инвайтов:</label>
		<div class="controls">
			<?=CHtml::activeTextField($user, "n_invites", ["class" => "span1"]); ?>
		</div>
	</div>

	<?php if(count($sentInvites) > 0): ?>
	<div class="control-group">
		<label class="control-label">Инвайты для <?=$user->login; ?>:</label>
		<div class="controls">
		<?php
		foreach($sentInvites as $invite) {
			echo CHtml::textField("", $invite->getUrlAccept(), ["class" => "span6", "onclick" => '$(this).select()']);
			echo "<span class='help-block'>";
			echo $invite->cdate . " от " . $invite->sender->ahref;
			echo "</span>";
		}
		?>
		</div>
	</div>
	<?php endif ?>

	<div class="control-group">
		<label class='control-label'>Сброс пароля:</label>
		<div class='controls'>
			<?php
			if($remindToken) {
				echo CHtml::textField("", $remindToken->url, ["class" => "span6", "onclick" => '$(this).select()']);;
			} else {
				echo "<a href='/users/{$user->id}/adminRemindToken' class='btn btn-warning'>Получить ссылку</a>";
			}
			?>
			</div>
	</div>

	<div class="form-actions">
		<button type="submit" class="btn btn-primary"><i class="icon-ok icon-white"></i> Сохранить</button>
	</div>

</form>