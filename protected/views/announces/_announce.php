<?php
	/**
	 * @var Announce $announce
	 */
?>
<div class='announce'>
	<div class='a'>
		<?php
			echo "<div class='img'";
			if($announce->book->img->exists) echo " style=\"background-image:url('" . $announce->book->img->getUrl("5050") . "')\""; // $announce->book->img->getTag("5050");
			echo "></div>";
		?>
		<div class='r'><?php echo $announce->book->ready; ?></div>
	</div>
	<div class='b'>
		<h2 title="<?php echo Yii::app()->dateFormatter->formatDateTime($announce->cdate, "medium", "short"); ?>">
			<?php echo $announce->book->ahref; ?>
		</h2>
		<p class='info'>
			<?php
				echo $announce->topicHtml . " ";
				echo Yii::app()->params["book_types"][$announce->book->typ] . " ";
				echo Yii::app()->langs->from_to($announce->book->s_lang, $announce->book->t_lang) . " ";
				echo " от " . $announce->book->owner->ahref;
				if($announce->book->cat_id) echo " в разделе &laquo;<a href='/search?cat={$announce->book->cat_id}'>{$announce->book->cat->title}</a>&raquo;";

				echo "<span class='cmt'>";
				if($announce->n_comments > 0) {
					if($announce->n_new_comments) {
						echo "<a href='{$announce->url}#Comments' title='Комментариев: {$announce->n_comments}, новых: {$announce->n_new_comments}'><i class='icon-nb-comment new'></i> {$announce->seen->n_comments}+{$announce->n_new_comments}</a> ";
					} else {
						echo "<a href='{$announce->url}#Comments' title='Комментариев: {$announce->n_comments}'><i class='icon-nb-comment'></i> {$announce->n_comments}</a> ";
					}
				} else {
					if(!Yii::app()->user->isGuest) echo "<a href='{$announce->url}#Comments' title='Написать комментарий'><i class='icon-nb-comment'></i></a> ";
				}
				echo "</span> ";
			?>
		</p>
		<div class='msg'><i class='v'></i>
			<?php
				echo Yii::app()->parser->out($announce->body);
			?>
		</div>
	</div>
</div>