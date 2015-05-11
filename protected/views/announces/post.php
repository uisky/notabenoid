<?php
	Yii::app()->clientScript
		->registerScriptFile("/js/jquery.scrollTo.js")
		->registerScriptFile("/js/jquery.elastic.mod.js")
		->registerScriptFile("/js/ff_comments.js?3")
		->registerScriptFile("/js/blog.js");

	$this->pageTitle = "Анонс перевода " . $book->fullTitle;
?>

<script type='text/javascript'>
	$(function() {
		$(".comments").ff_comments();
	});
</script>

<ul class='nav nav-tabs'>
	<li><a href='<?=$book->url; ?>/'>оглавление</a></li>
	<li><a href='<?=$book->getUrl("members"); ?>'>переводчики</a></li>
	<li><a href='<?=$book->getUrl("blog"); ?>'>блог</a></li>
	<li class='active'><a href='<?=$book->getUrl("announces"); ?>'>анонсы</a></li>
</ul>

<?php
	$post->title = "Анонс";
	$this->renderPartial("//blog/_post", array("post" => $post, "placement" => "post", "has" => array("bookLink" => true)));
?>

<a name="Comments"></a><h2>Комментарии</h2>
<div class='comments'>
	<?php
		$prev_indent = $indent = 0;
		foreach($comments as $comment) {
			$comment->post = $post;

			$indent = count($comment->mp);
			$j = $indent - $prev_indent;

			if($j <= 0) echo str_repeat("</div>", -$j + 1);
			echo "<div class='thread'>";

			$this->renderPartial("//blog/_comment", array("comment" => $comment));

			$prev_indent = $indent;
		}
		echo str_repeat("</div>", $indent);
	?>

	<?php if(!Yii::app()->user->isGuest && $book->can("blog_c")): ?>
	<div class="thread thread-form">
		<div class="comment" id="cmt_0">
			<form method="post" class="reply" action="<?=$post->getUrl("c0/reply"); ?>">
				<div>
					<textarea name="Comment[body]"></textarea>
				</div>
				<div>
					<input type="submit" value="Добавить комментарий" title="Или нажмите Ctrl+Enter" class="btn" />
					<input type="hidden" name="Comment[pid]" value="0" />
				</div>
			</form>
		</div>
	</div>

	<p class="cmt_0_btn" style="display:none">(<a href="#" class="re">комментировать пост</a>)</p>

	<?php else: ?>
	<p class="info">
		Вы не можете писать комментарии в блоге этого перевода.
	</p>
	<?php endif; ?>

</div>
