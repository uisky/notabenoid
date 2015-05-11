<?php
	/**
	 * @var Comment $comment
	 * @var boolean $disable_reply - не выводить ссылку "ответить", иначе - если не гость
	 * @var boolean $disable_delete - не выводить ссылку удалить, иначе - $comment->can("delete")
	 * @var boolean $disable_dot - не выводить точку
	 * @var boolean $disable_up - не выводить ссылку на родительский комментарий
	 * @var boolean $disable_rating - не выводить рейтинг
	 * @var boolean $disable_rater - не выводить кнопки для голосования
	 * @var string $meta_extra - допишется в #meta
	 */

	$class = "comment";
	if($comment->user_id !== '') $class .= " u{$comment->user_id}";
	if($comment->isDeleted()) $class .= " deleted";
	if($comment->is_new and $comment->user_id != Yii::app()->user->id) {
		$class .= " new";
		echo "<a name='cmt_new'></a>";
	}
	echo "<div class='{$class}' id='cmt_{$comment->id}'>";

	if($comment->isDeleted()) {
		echo "<div class='text'>Удалённый комментарий.</div>";
	} else {
		echo "<div class='text'>";
		$body = '<p>' . preg_replace('/\n{2,}/', '</p><p>', $comment->body) . '</p>';
		echo Yii::app()->parser->parse($body);
		echo "</div>";

		echo "<div class='meta'>";
		echo "<img src='{$comment->author->upicUrl}' class='upic' />";
		echo $comment->user_id ? $comment->author->ahref : "Анонимно";
		echo " " . Yii::app()->dateFormatter->format("d.MM.yy в H:mm", $comment->cdate) . " &middot; ";

		echo "<a href='#cmt_{$comment->id}' class='a ajax'>#</a> &middot; ";

		// if($comment->can("reply")) ...
		if(!$disable_reply and !Yii::app()->user->isGuest) echo "<a href='#cmt_{$comment->id}' class='re ajax'>Ответить</a> ";

		if(!$disable_dot) echo "<a href='#' class='dot b'><i class='i icon-flag'></i></a> ";
		if(!$disable_delete and $comment->can("delete")) echo "<a href='#' class='rm b'><i class='i icon-remove'></i></a>  ";

		if(!$disable_rating) {
			echo "<div class='rating'>";
			if(!$disable_rater && $comment->can("rate")) echo "<a class='p' href='#'>+</a>";
			echo "<span>" . str_replace("-", "&minus;", $comment->rating) . "</span>";
			if(!$disable_rater && $comment->can("rate")) echo "<a class='n' href='#'>&minus;</a>";
			echo "</div>";
		}

		echo $meta_extra;
		echo "</div>";
	}

	if(!$disable_up && $comment->pid && !$comment->isDeleted()) echo "<a href='#cmt_{$comment->pid}' class='up'><i class='i icon-up'></i></a> ";

	echo "</div>";
?>