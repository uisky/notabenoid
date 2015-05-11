<?php
/**
 * @var Comment $comment
 * @var Comment $parent
 * @var Orig $orig
 */
?>
<style type='text/css'>
    p.orig {}
    p.note {}
    address {}
    blockquote {}
</style>
<base href="http://<?=Yii::app()->params["domain"]; ?>" />
<body>
<p>Добрый день!</p>
<p>
	<?=$comment->author->ahref; ?> ответил<?=$comment->author->sexy(); ?> на ваш комментарий в переводе
    &laquo;<a href="<?=$orig->url; ?>"><?php echo $orig->chap->book->fullTitle . ": " . $orig->chap->title; ?></a>&raquo;:<br />
</p>
<p style="margin-left:10px;">
	<?=nl2br($orig->body); ?>
</p>

<p>Вы писали:</p>
<blockquote style="border-left:2px solid #777; margin:10px 0px 10px; padding:10px 0 10px 10px;">
	<?=nl2br($parent->body); ?>
</blockquote>

<p>И вам ответили:</p>
<blockquote style="border-left:2px solid #777; margin:10px 0px 10px; padding:10px 0 10px 10px;">
	<?=nl2br($comment->body); ?>
</blockquote>

<address style="margin-top:20px; border-top:1px solid gray; width:200px;">
    С уважением,<br />
	<a href='http://<?=Yii::app()->params["domain"]; ?>/'><?=Yii::app()->name; ?></a>
</address>

<p style="color:#777; font-style:italic; font-size:11px;">
    P. S. Это письмо написано искусственным интеллектом, отвечать на него не надо. Вы можете отключить почтовые уведомления на странице
    <a href="http://<?=Yii::app()->params["domain"]; ?>/register/settings">настроек сайта</a>.
</p>
