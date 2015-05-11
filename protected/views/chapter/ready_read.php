<?php
	/**
	 * @var Chapter $chap
	 * @var GenOptions $options
	 * @var ReadyGenerator_base $generator
	 */

	$this->pageTitle = "Готовый перевод {$chap->book->fullTitle}: {$chap->title}";
?>
<h1><?php echo "Готовый перевод {$chap->book->fullTitle}: {$chap->title}"; ?></h1>
<?php
	$generator->generate(false);
?>