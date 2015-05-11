<?php
	/**
	* @var integer $cache_time
	* @var string $mode ("blog", "tblog", "tr")
	* @var CActiveDataProvider $comments
	* @var User $user
	*/

	Yii::app()->clientScript
		->registerScriptFile("/js/profile.js")->registerCssFile("/css/profile.css?3");

	$this->pageTitle = $user->login . ": комментарии";

	$this->renderPartial("profile_head", array("user" => $user, "h1" => "комментарии"));
?>

<style type="text/css">
.comments {margin-left:0;}
.comments .comment {margin-bottom: 10px;}
</style>

<?php
$this->widget('bootstrap.widgets.TbMenu', array(
	'type' => 'pills', // '', 'tabs', 'pills' (or 'list')
	'stacked' => false, // whether this is a stacked menu
	'items'=>array(
		array('label'=>'В общем блоге',      'url'=>'?mode=blog',  'active' => $mode == "blog"),
		array('label'=>'В блогах переводов', 'url'=>'?mode=tblog', "active" => $mode == "tblog"),
		array('label'=>'В переводах',        'url'=>'?mode=tr',    "active" => $mode == "tr"),
	),
));
?>

<?php
	if($comments->totalItemCount == 0) {
		$A = array("blog" => "в общем блоге", "tblog" => "в блогах общедоступных переводов", "tr" => "в общедоступных переводах");
		echo "<p>{$user->login} не написал" . $user->sexy() . " ни одного комментария {$A[$mode]}</p>";
	} else {
		echo "<h2>" . Yii::t("app", "{n} комментарий|{n} комментария|{n} комментариев", $comments->totalItemCount) . "</h2>";
		if($cache_time) {
			echo "<div class='alert alert-box alert-info'>Информация обновляется раз в <strong>" . Yii::t("app", "{n} час|{n} часа|{n} часов", $cache_time) . "</strong></div>";
		}

		$data = $comments->data;

		$this->widget('bootstrap.widgets.TbPager', array("pages" => $comments->pagination));

		echo "<div class='comments'>";
		$view = Yii::app()->user->ini["t.iface"] == 1 ? "//blog/_comment-1" : "//blog/_comment";
		foreach($comments->data as $comment) {
			$x = "";

			if($mode == "blog") {
				if(!isset(Yii::app()->params["blog_topics"]["common"][$comment->post->topics])) {
					echo "<p class='access-denied'>Комментарий написан в блоге, к которому у вас нет доступа.</p>";
					continue;
				}
				$x .= "<a href='{$comment->post->url}#cmt_{$comment->id}'>{$comment->post->title}</a>";
			} elseif($mode == "tblog") {
				$x .= "{$comment->post->book->ahref} - <a href='{$comment->post->url}#cmt_{$comment->id}'>{$comment->post->title}</a>";
			} elseif($mode == "tr") {
				$x .= "<a href='{$comment->orig->url}'>{$comment->orig->chap->book->fullTitle}</a>";
			}

			$this->renderPartial($view, [
					"comment" => $comment, "meta_extra" => $x,
					"disable_dot" => true, "disable_reply" => true,
					"disable_delete" => true, "disable_up" => true,
					"disable_rater" => true,
				]
			);
		}
		echo "</div>";

		$this->widget('bootstrap.widgets.TbPager', array("pages" => $comments->pagination));
	}
?>
