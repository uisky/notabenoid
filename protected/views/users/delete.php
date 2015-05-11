<?php
	$this->pageTitle = "Огорчить котика";
?>
<style type="text/css">
#sadcat { display: block; margin: 15px auto; }
</style>
<h1>О боже, нет!</h1>
<p>
	Этот кот очень расстроен тем, что вы решили нас покинуть.
</p>
<p>
	<img src="/i/deleteuser/cat<?=rand(1, 5); ?>.jpg" width="320" alt="Сердце у вас каменное" id="sadcat" />
</p>
<p>
	Вообще, вы можете просто не заходить на сайт. Возможно, спустя много лет, мы снова вам понадобимся.
	А если вас, допустим, раздражают письма от нас и других пользователей, вы можете просто <a href="/register/settings">отключить их в настройках сайта</a>.
</p>
<p>
	Но если вы тверды в вашем решении, то введите ваш пароль:
</p>
<form method="POST" class="form-inline" style="text-align:center;">
	<input type="hidden" name="really" value="1" />
	<input type="password" name="pass" class="span3" />
	<button type="submit" class="btn btn-danger"><i class="icon-ban-circle icon-white"></i> Удалить аккаунт</button>
</form>
<p>
	Все ваши переводы, посты, комментарии и оценки, однако, останутся на сайте, потому что у нас тут краудсорсинг и что написано пером, не вырубишь топором.
	Ваш логин (<code><?=Yii::app()->user->login; ?></code>) тоже останется занятым.
</p>
