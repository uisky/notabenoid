<?php
/**
 * @var CActiveDataProvider $posts
 * @var User $user
 */

Yii::app()->clientScript
	->registerScriptFile("/js/profile.js")->registerCssFile("/css/profile.css?3")
	->registerScriptFile("/js/blog.js");

$this->pageTitle = $user->login . ": посты";

$this->renderPartial("profile_head", array("user" => $user, "h1" => "посты"));
?>

<?php if($posts->totalItemCount == 0): ?>

<p>
	<?=$user->login; ?> не написал<?=$user->sexy(); ?> ни одного поста в блогах.
</p>

<?php else: ?>

<h2><?=Yii::t("app", "{n} пост|{n} поста|{n} постов", $posts->totalItemCount); ?></h2>
<?php
	if($cache_time) {
		echo "<div class='alert alert-box alert-info'>Информация обновляется раз в <strong>" . Yii::t("app", "{n} час|{n} часа|{n} часов", $cache_time) . "</strong></div>";
	}

	$data = $posts->data;

	$this->widget('bootstrap.widgets.TbPager', array("pages" => $posts->pagination, "header" => "<div class='pagination' style='margin-bottom:0'>"));

	foreach($posts->data as $post) {
		$post->author = $user;
		if($post->book_id != 0 && !$post->book->can("blog_r")) {
			echo "<p class='access-denied'>Пост написан в блоге перевода, к которому у вас нет доступа.</p>";
		} elseif ($post->book_id == 0 && !isset(Yii::app()->params["blog_topics"]["common"][$post->topics])) {
			echo "<p class='access-denied'>Пост написан в блоге, к которому у вас нет доступа.</p>";
		} else {
			$this->renderPartial("//blog/_post", array("post" => $post, "placement" => "user", "has" => array("edit" => false)));
		}
	}

	$this->widget('bootstrap.widgets.TbPager', array("pages" => $posts->pagination));
	?>

<?php endif ?>