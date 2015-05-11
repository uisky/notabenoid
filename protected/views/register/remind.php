<?php
	$this->pageTitle = "Напомнить пароль";
?>

<h1>Склероз</h1>

<form method="post" action="/register/remind">
	<label for="clue">
		Введите Ваш логин или e-mail, который указывали при регистрации:
	</label>
	<input type="text" name="clue" id="clue" class="span6" autofocus value="<?=CHtml::encode($_POST["clue"]); ?>">
	<p>
	<button type="submit" class="btn btn-primary"><i class='icon-ok icon-white'></i> Восстановить</button>
	</p>
</form>
