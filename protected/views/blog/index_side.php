<?php
	/**
	 * @var integer[] $topics
	 * @var integer $topic
	 */
?>
<style type="text/css">
#topics .c { display: block; float: left; margin: 5px 5px 0 0; }
#topics .buttons {padding: 5px 0 15px 20px; display: none; }
</style>
<div class='tools'>
<h5>Блог</h5>

<b>Рубрики:</b>
<form method="get" action="/blog">
<ul class="nav nav-list" id="topics">
<?php
	foreach(Yii::app()->params["blog_topics"]["common"] as $k => $v) {
		if(is_array($topics)) {
			echo "<li" . (in_array($k, $topics) ? " class='active'" : "") . ">";
			echo "<input type='checkbox' name='topics[]' value='{$k}'" . (in_array($k, $topics) ? " checked" : "") . " class='c' /> ";
		} else {
			echo "<li" . ($k == $topic ? " class='active'" : "") . ">";
		}
		echo "<a href='/blog/?topic={$k}'>{$v}</a>";
		echo "</li>";
	}
	echo "<li class='buttons'><button class='btn btn-mini'>Показать</button></li>";
	echo "<li" . (is_array($topics) && count($topics) == 0 ? " class='active'" : "") . "><a href='/blog?topics=all'>Все</a></li>";
?>

</ul>

</form>

<?php
	if(!Yii::app()->user->isGuest) echo "<p><a href='/blog/edit" . ($topic ? "?topics[]={$topic}" : "") . "'><i class='icon icon-pencil'></i> Написать пост</a></p>";
?>

</div>

<script type="text/javascript">
(function() {
	$("#topics").find(":checkbox").click(function() {
		$("#topics").find("li.buttons").show(200);
	});
})();
</script>