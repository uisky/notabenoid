<?php
/**
 * @var Comment[] $comments
 * @var Orig $orig
 */
?>
<div class='comments'>
	<?php
		$view = Yii::app()->user->ini["t.iface"] == 1 ? "//blog/_comment-1" : "//blog/_comment";
		$prev_indent = $indent = 0;
		foreach($comments as $comment) {
			$comment->orig = $orig;

			$indent = count($comment->mp);
			$j = $indent - $prev_indent;

			if($j <= 0) echo str_repeat("</div>", -$j + 1);
			echo "<div class='thread'>";

			$this->renderPartial($view, array("comment" => $comment));

			$prev_indent = $indent;
		}
		echo str_repeat("</div>", $indent);
	?>

	<?php if($orig->chap->can("comment")): ?>
	<div class="thread thread-form">
		<div class="comment">
			<form method="post" class="reply" action="<?=$orig->getUrl("c0/reply"); ?>">
				<div>
					<textarea name="Comment[body]"></textarea>
				</div>
				<div>
					<input type="submit" value="Добавить" title="Или нажмите Ctrl+Enter" class="btn btn-mini btn-primary" />
					<?php if(!$orig->seen->track): ?>
						<input type="button" value="В мои обсуждения" class="btn btn-mini mytalks" title="Показывать новые комментарии к этому фрагменту в &laquo;Моих обсуждениях&raquo;" onclick="T.mytalks(<?=$orig->id; ?>, this)" />
					<?php endif; ?>
					<input type="button" value="Отмена" class="btn btn-mini cancel" />
					<button type="button" class="btn btn-mini stop">Закрыть</button>
					<input type="hidden" name="Comment[pid]" value="0" />
				</div>
			</form>
		</div>
	</div>

	<p class="cmt_0_btn"><i class="i icon-comment"></i> <a href="#" class="re ajax">Комментировать фрагмент</a></p>
	<?php endif; ?>
</div>