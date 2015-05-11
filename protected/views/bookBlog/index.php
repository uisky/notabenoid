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
	<li class='active'><a href='<?=$book->getUrl("blog"); ?>'>блог</a></li>
	<li><a href='<?=$book->getUrl("announces"); ?>'>анонсы</a></li>
</ul>

<h1><?=$book->fullTitle; ?> &ndash; блог</h1>

<?php
	$posts = $lenta->getData();
	if($lenta->totalItemCount == 0) {
?>
	<div class='alert alert-info' id="info_empty">
		<?php
			if($topic) echo "В этом разделе нет постов. <a href='" . $book->getUrl("blog") . "'>Показать посты из всех разделов</a>.";
			else echo "Блог перевода пуст.";

			if($book->can("blog_w")) echo " <a href='" . $book->getUrl("blog/edit" . ($topic ? "?topic={$topic}" : "")) . "' class='act'>Написать первый пост</a>.";
		?>
	</div>
	<table class="items" id="Chapters"></table>
<?php
	} else {
		echo "<div id='Lenta'>";
		foreach($posts as $post) {
			$post->book = $book;
			$this->renderPartial("//blog/_post", array("post" => $post, "placement" => "index", "has" => array("bookLink" => false)));
		}
		echo "</div>";
	}

	$this->widget('CLinkPager', array(
		'pages' => $lenta->pagination,
	));
?>