<?php
	/**
	* @var integer $cache_time
	* @var CActiveDataProvider $translations
	* @var User $user
	*/

	Yii::app()->clientScript
		->registerScriptFile("/js/profile.js")->registerCssFile("/css/profile.css?3");

	$this->pageTitle = $user->login . ": переводы";

	$this->renderPartial("profile_head", array("user" => $user, "h1" => "переводы"));
?>

<style type="text/css">
.verse {
	padding:20px 0 20px 0;
	border-bottom:1px solid #777;
}
.verse:hover {
	background:#f0f0f0;
}
.verse .orig {
	padding:0 0 0 24px;
}
.verse .tr {
}
.verse .x {
	display:block;
	float:right;
	font-size:20px;
	font-weight:bold;
	color:#777;
}
.verse .rate {
	margin-left:10px;
}

</style>

<script type="text/javascript">
$(function() {
	$(".rate").attr("title", "Рейтинг");
});
</script>

<?php if($translations->totalItemCount == 0): ?>

<p>
	<?=$user->login; ?> не предложил<?=$user->sexy(); ?> ни одного варианта перевода.
</p>

<?php else: ?>

<h2><?php
	echo $book->ahref . " ";
//	echo Yii::t("app", "{n} перевод|{n} перевода|{n} переводов", $translations->totalItemCount);
?></h2>
<?php
	if($cache_time) {
		echo "<div class='alert alert-box alert-info'>Информация обновляется раз в <strong>" . Yii::t("app", "{n} час|{n} часа|{n} часов", $cache_time) . "</strong></div>";
	}

	$data = $translations->data;

	$this->widget('bootstrap.widgets.TbPager', array("pages" => $translations->pagination, "header" => "<div class='pagination' style='margin-bottom:0'>"));

	foreach($translations->data as $tr) {
		echo "<div class='verse'>";
		echo "<div class='row'><div class='span4'>";

		echo "<div class='orig'>";
		echo "<b class='x'><a href='{$tr->orig->url}'>&rarr;</a></b>";
		echo nl2br(htmlspecialchars($tr->orig->body));
		echo "</div>";

		echo "</div><div class='span4'>";

		echo "<div class='tr'>";
		echo " <i class='rate'>{$tr->rating}</i>";
		echo nl2br(htmlspecialchars($tr->body));
		echo "</div>";

		echo "</div></div>";

		echo "</div>";
	}

	$this->widget('bootstrap.widgets.TbPager', array("pages" => $translations->pagination));
?>

<?php endif ?>