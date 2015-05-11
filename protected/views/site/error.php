<?php
	$this->pageTitle="Ошибка {$code}";

	$codes = array(
		"404" => "Страница не найдена",
		"403" => "Доступ запрещён",
		"500" => "Системная ошибка"
	);
?>
<div class="errorpage">
	<h1><?php echo isset($codes[$code]) ? $codes[$code]: "Ошибка {$code}"; ?></h1>
	<?php
		$p = new CHtmlPurifier();
		$p->options = Yii::app()->params["HTMLPurifierOptions"];
		echo $p->purify($message);
	?>
</div>