<?php
	Yii::app()->getClientScript()
		->registerScriptFile("/js/jquery.scrollTo.js")
		->registerScriptFile("/js/blog.js");

	$newComments = Yii::app()->user->newComments;
	$this->pageTitle = "Мои обсуждения" . ($newComments > 0 ? " ({$newComments})" : "");
?>
<style type='text/css'>
#Lenta .orig {margin:0 0 15px 0}
#Lenta .orig .t {color:#777; font-size:11px;}
#Lenta .orig .meta {
	background:#e5e5e5; 	 padding:2px 8px;
	border-bottom:1px solid gray;
	font-size:11px;
}
#Lenta .orig .meta a.new {color:#c00; font-weight:bold;}
</style>

<h1>Мои обсуждения</h1>
<ul class="nav nav-tabs">
<?php
	foreach($modes as $k => $v) {
		echo "<li" . ($k == $mode ? " class='active'" : "") . "><a href='?mode={$k}'>{$v}</a></li>";
	}
?>
</ul>

<div id="Lenta">
<?php
	if($lenta->totalItemCount == 0) {
		echo "<p class='info'>";
		if(Yii::app()->user->ini_get(User::INI_MYTALKS_NEW)) {
			echo "Новых комментариев {$modes[$mode]}, где вы отметились, нет.";
		} else {
			$A = array("p" => "пост", "o" => "фрагмент оригинала");
			echo "Вы ещё не добавили в &laquo;Мои обсуждения&raquo; ни один {$A[$mode]}. Посты и фрагменты появляются здесь автоматически, если вы прокомментировали их или нажали на кнопку &laquo;в мои обсуждения&raquo;.";
		}
		echo "</p>";
	} else {
		$user = Yii::app()->user;
		if($mode == "p") {
			foreach($lenta->getData() as $post) {
				// автофикс seen.n_comments
				if($post->seen->n_comments > $post->n_comments) {
					$post->seen->n_comments = $post->n_comments;
					$post->seen->save(false, "n_comments");
				}

				$this->renderPartial("//blog/_post", array("post" => $post, "placement" => "talks"));
			}
		} elseif($mode == "o") {
			foreach($lenta->getData() as $orig) {
				// автофикс seen.n_comments
				if($orig->seen->n_comments > $orig->n_comments) {
					$orig->seen->n_comments = $orig->n_comments;
					$orig->seen->save(false, "n_comments");
				}

				echo "<div class='orig'>";
				if($orig->t1) echo "<div class='t'>{$orig->t1} - {$orig->t2}</div>";
				echo "<p>" . nl2br($orig->body) . "</p>";
				echo "<div class='meta'>";
				echo $orig->chap->ahref . " | ";
				echo "<a href='{$orig->url}'>Комментариев: {$orig->n_comments}</a>";
				if($orig->seen->n_comments != $orig->n_comments) echo " / <a href='{$orig->url}' class='new'>Новых: " . ($orig->n_comments - $orig->seen->n_comments) . "</a>";
				echo " | <a href='/my/comments/rm/?orig_id={$orig->id}' class='talks'>не показывать</a>";
				echo "</div>";
				echo "</div>";
			}
		}
	}
?>
</div>

<?php
	$this->widget('CLinkPager', array('pages' => $lenta->pagination));
?>