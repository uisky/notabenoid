<?php
/**
 * @var RegInvite $invite
 */
?>
<p>Добрый день!</p>
<p>
	Некто <?=$invite->sender->login; ?> приглашает вас в закрытый клуб переводчиков
	<a href='http://notabenoid.org'>Notabenoid.Org</a>. Это приглашение — ваш единственный
	шанс туда попасть.
	Чтобы зарегистрироваться, пройдите по ссылке:
</p>
<p>
	<a href='<?=$invite->urlAccept; ?>'><?=$invite->urlAccept; ?></a>
</p>
<?php if($invite->message != ""): ?>
<p>
	Кстати, <?=$invite->sender->login; ?> просил передать вам следующее:<br>
	<?=nl2br(htmlspecialchars($invite->message)); ?>
</p>
<?php endif; ?>

<p style='color:#777; font-style:italic;'>P. S. Это письмо написано искусственным интеллектом, отвечать на него не надо.</p>