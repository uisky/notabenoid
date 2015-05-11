<?php
	/**
	 * @var integer $topic
	 * @var Book $book
	 * @var BlogPost[] $lenta
	 */
	$this->pageTitle = $this->book->fullTitle . " - блог";

	Yii::app()->getClientScript()
		->registerScriptFile("/js/jquery.scrollTo.js")
		->registerScriptFile("/js/blog.js")
		->registerScriptFile("/js/book.js?1");

	$book->registerJS();
?>

<ul class='nav nav-tabs'>
	<li><a href='<?=$book->url; ?>/'>оглавление</a></li>
	<li><a href='<?=$book->getUrl("members"); ?>'>переводчики</a></li>
	<li><a href='<?=$book->getUrl("blog"); ?>'>блог</a></li>
	<li class='active'><a href='<?=$book->getUrl("announces"); ?>'>анонсы</a></li>
</ul>

<h1><?=$book->fullTitle; ?> &ndash; анонсы</h1>

<?php
	$posts = $lenta->getData();
	if($lenta->totalItemCount == 0) {
?>
	<div class='alert alert-info' id="info_empty">
		<?php
			echo "Не создано ещё ни одного анонса.";

			if($book->can("blog_w")) echo " <a href='" . $book->getUrl("announces/write") . "' class='act'>Написать первый анонс</a>.";
		?>
	</div>
<?php
	} else {
		echo "<div id='Lenta'>";
		foreach($posts as $post) {
			$post->book = $book;
			$this->renderPartial("//blog/_post", array("post" => $post, "placement" => "index", "has" => array("bookLink" => false)));
		}
		echo "</div>";
	}

	$this->widget('CLinkPager', array('pages' => $lenta->pagination));
?>