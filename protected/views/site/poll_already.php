<?php
	/**
	 * @var string $when
	 */
	$this->pageTitle = "Опрос для науки";
?>
<h1>Опрос для науки</h1>
<p>
	Вы уже прошли этот опрос <?php echo Yii::app()->dateFormatter->formatDateTime($when, "medium", ""); ?>, за что
	мы вам безмерно благодарны!
</p>
<p>
	Если хотите, можете <a href="?again=1">пройти его снова</a>.
</p>