<?php
	Yii::app()->clientScript->registerScriptFile("http://cloud.github.com/downloads/malsup/cycle/jquery.cycle.all.latest.js");

	$this->pageTitle = "Опрос для науки";

	$images = [
		"<img src='/i/poll/1.jpg' width='304' height='300' alt='' />",
		"<img src='/i/poll/2.png' width='514' height='514' alt='' />",
		"<img src='/i/poll/3.jpg' width='500' height='415' alt='' />",
		"<img src='/i/poll/4.png' width='600' height='400' alt='' />",
		"<img src='/i/poll/5.jpg' width='423' height='248' alt='' />",
		"<img src='/i/poll/6.jpg' width='304' height='300' alt='' />",
		"<img src='/i/poll/7.jpg' width='304' height='300' alt='' />",
		"<img src='/i/poll/8.jpg' width='304' height='300' alt='' />",
		"<img src='/i/poll/9.jpg' width='304' height='300' alt='' />",
		"<img src='/i/poll/10.jpg' width='304' height='300' alt='' />",
		"<img src='/i/poll/11.jpg' width='304' height='300' alt='' />",
		"<img src='/i/poll/12.jpg' width='304' height='300' alt='' />",
		"<img src='/i/poll/13.jpg' width='304' height='300' alt='' />",
		"<img src='/i/poll/14.jpg' width='304' height='300' alt='' />",
		"<img src='/i/poll/15.jpg' width='304' height='300' alt='' />",
	];
?>
<script type="text/javascript">
$(function() {
	$('#thankyou').cycle({
		fx: 'fade',
		timeout:3333
	}).click(function() {
			$(this).cycle("toggle");
	});
});
</script>
<style type="text/css">
	#thankyou { width:100%; margin:30px auto 20px; min-height:800px; }
</style>
<h1>Большое спасибо!</h1>

<p>
	Вы здорово помогли науке. Учёные обещали проанализировать ваши ответы и, используя полученные данные,
	поскорее изобрести телепортацию, холодный термоядерный синтез и бесплатное пиво.
</p>
<p>
	<a href="/">На главную</a>
</p>
<div id="thankyou">
	<?php
		$shift = rand(0, 14);
		for($i = 1; $i <= 15; $i++) {
			$img = ($i + $shift) % 15 + 1;
			printf("<img src='/i/poll/%d.%s' />", $img, $img == 2 || $img == 4 ? "png" : "jpg" );
		}
	?>
</div>

