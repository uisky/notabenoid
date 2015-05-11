<?php
	Yii::app()->clientScript
		->registerScriptFile("/js/jquery.scrollTo.js")
		->registerScriptFile("/js/jquery.elastic.mod.js")
		->registerScriptFile("/js/ff_comments.js?3")
		->registerScriptFile("/js/blog.js");

	$this->pageTitle = $post->title;

	$this->renderPartial("//blog/_post", array("post" => $post, "placement" => "post"));
?>

<script type='text/javascript'>
	$(function() {
		$(".comments").ff_comments();
	});
</script>

<a name="Comments"></a><h2>Комментарии</h2>
<div class='comments'>
	<?php
		$view = Yii::app()->user->ini["t.iface"] == 1 ? "//blog/_comment-1" : "//blog/_comment";
		$prev_indent = $indent = 0;
		foreach($comments as $comment) {
			$comment->post = $post;

			$indent = count($comment->mp);
			$j = $indent - $prev_indent;

			if($j <= 0) echo str_repeat("</div>", -$j + 1);
			echo "<div class='thread'>";

			$this->renderPartial($view, array("comment" => $comment));

			$prev_indent = $indent;
		}
		echo str_repeat("</div>", $indent);
	?>

	<?php if(!Yii::app()->user->isGuest): ?>
	<div class="thread thread-form">
		<div class="comment">
			<form method="post" class="reply" action="<?=$post->getUrl("c0/reply"); ?>">
				<div>
					<textarea name="Comment[body]"></textarea>
				</div>
				<div>
					<input type="submit" value="Добавить" title="Или нажмите Ctrl+Enter" class="btn btn-mini btn-primary" />
					<input type="button" value="Отмена" class="btn btn-mini cancel" />
					<input type="hidden" name="Comment[pid]" value="0" />
				</div>
			</form>
		</div>
	</div>

	<p class="cmt_0_btn"><i class="i icon-comment"></i> <a href="#" class="re ajax">Комментировать пост</a></p>
	<?php endif; ?>
</div>
