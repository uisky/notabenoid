<style type='text/css'>
	p.note {color:#777; font-style:italic;}
	address {margin-top:20px; border-top:1px solid gray; width:200px;}
	blockquote {border-left:2px solid #777; padding:10px 0px 10px 10px;}
</style>
<base href="http://<?=Yii::app()->params["domain"]; ?>" />
<body>
<p>Добрый день, <?=$user->login; ?>!</p>
<p>
	<?=nl2br($Notice->render()); ?>
</p>
<address>
	С уважением,<br /.>
	<a href='http://<?=Yii::app()->params["domain"]; ?>/'><?=Yii::app()->name; ?></a>
</address>

<p class='note'>
	P. S. Это письмо написано искусственным интеллектом, отвечать на него не надо.
	Вы получаете эти письма потому, что включили пересылку <a href='/my/notices'>оповещений</a> на электронную почту. Отключить её можно на странице
	<a href='/register/settings'>настроек сайта</a>.
</p>
