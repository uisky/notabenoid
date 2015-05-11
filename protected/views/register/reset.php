<?php
/**
 * @var RegisterController $this
 * @var User $user
 */
?>
<h1>Введите новый пароль</h1>

<form method="post" class="form-horizontal">
	<div class="control-group">
		<label class="control-label">Логин:</label>
		<div class="controls">
			<input type="text" disabled value="<?=CHtml::encode($user->login); ?>" class="span3">
		</div>
	</div>
	<div class="control-group">
		<label class="control-label">Новый пароль:</label>
		<div class="controls">
			<input type="password" name="pass" class="span3">
			<p class="help-block">Не короче 8 символов, пожалуйста.</p>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label">Ещё раз:</label>
		<div class="controls">
			<input type="password" name="pass2" class="span3">
		</div>
	</div>
	<div class="form-actions">
		<button class="btn btn-default">
			<i class="icon icon-ok"></i>
			Установить новый пароль
		</button>
	</div>
</form>