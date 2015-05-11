<style type='text/css'>
	p.note {color:#777; font-style:italic;}
	address {margin-top:20px; border-top:1px solid gray; width:200px;}
	blockquote {border-left:2px solid #777; padding:10px 0px 10px 10px;}
</style>
<base href="http://<?=Yii::app()->params["domain"]; ?>" />
<body>
<p>Добрый день!</p>
<p>
	<?=$comment->author->ahref; ?> ответил<?=$comment->author->sexy(); ?> на ваш комментарий в посте &laquo;<a href="<?=$post->url; ?>"><?=$post->title; ?></a>&raquo;.
	Вы писали:
</p>
<blockquote><?=nl2br($parent->body); ?></blockquote>
<p>И вам ответили:</p>
<blockquote><?=nl2br($comment->body); ?></blockquote>
<p>
	<a href="<?=$post->url; ?>#cmt_<?=$comment->id; ?>">Ответить</a>.
</p>

<address>
	С уважением,<br /.>
	<a href='http://<?=Yii::app()->params["domain"]; ?>/'><?=Yii::app()->name; ?></a>
</address>

<p class='note'>P. S. Это письмо написано искусственным интеллектом, отвечать на него не надо.</p>
