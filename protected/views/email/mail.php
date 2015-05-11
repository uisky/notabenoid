<style type='text/css'>
	p.note {color:#777; font-style:italic;}
	address {margin-top:20px; border-top:1px solid gray; width:200px;}
	blockquote {border-left:2px solid #777; padding:10px 0px 10px 10px;}
</style>
<base href="http://<?=Yii::app()->params["domain"]; ?>" />
<body>
<p>Добрый день, <?=$message->buddy->login; ?>!</p>

<p><?=Yii::app()->user->ahref; ?> написал(а) вам личное сообщение на сайте <?=Yii::app()->name; ?>:</p>

<blockquote>
	<?=nl2br($message->body); ?>

	<br /><br />
	<b><a href="/my/mail/write/?reply=<?=$message->id; ?>">Ответить.</a></b>
</blockquote>

<p class='note'>
	P. S. Это письмо написано искусственным интеллектом, отвечать на него не надо.
	Вы получаете эти письма потому, что включили пересылку <a href='/my/mail'>личных сообщений</a> на электронную почту. Отключить её можно на странице
	<a href='/register/settings'>настроек сайта</a>.
</p>
