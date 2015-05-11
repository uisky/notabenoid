<?php
	/**
	 * @var CActiveDataProvider $dp
	 * @var Announce[] $announces
	 */

	$this->pageTitle = "Анонсы переводов";

	$announces = $dp->getData();
?>

<h1>Анонсы переводов</h1>

<div id="Announces">
<?php
	if($dp->getTotalItemCount() == 0) {
		echo "<div class='alert alert-info'>Ничего не найдено. Попробуйте расширить критерии поиска.</div>";
	} else {
		$this->widget('bootstrap.widgets.TbPager', array("pages" => $dp->pagination));
		foreach($announces as $announce) {
			$this->renderPartial("_announce", array("announce" => $announce));
		}
		$this->widget('bootstrap.widgets.TbPager', array("pages" => $dp->pagination));
	}
?>
</div>