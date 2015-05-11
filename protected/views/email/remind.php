<?php
/**
 * @var User $user
 * @var RemindToken $token
 */
?>
<body>
<p>Добрый день!</p>
<p>
	Забыли пароль на <?=Yii::app()->name; ?>? Вам сюда:
</p>
<p>
	<a href="<?=$token->url; ?>" style="padding:10px; background: #a1ff80; color: #005580; border-radius: 9px; "><?=$token->url; ?></a>
</p>
<p>
	Если вы ничего не забывали, просто проигнорируйте это сообщение, с вашим аккаунтом ничего не случится.
</p>
<address style="margin-top:20px; border-top:1px solid gray; width:200px;">
	С уважением,<br /.>
	<a href='http://<?=Yii::app()->params["domain"]; ?>/'><?=Yii::app()->name; ?></a>
</address>

<p style="color:#777; font-style:italic;">
	P. S. Это письмо написано искусственным интеллектом, отвечать на него не надо.
</p>
