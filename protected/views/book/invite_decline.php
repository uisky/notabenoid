<?php
	$this->pageTitle = $this->book->fullTitle;
?>
<h1><?=$this->book->fullTitle; ?></h1>
<p class="info">
	Вы отказались от участия в этом переводе.
</p>
<p>
	<a href="<?=$this->book->url; ?>">Оглавление перевода</a> |
	<?php
		$here = $this->book->url("invite_decline");
		if($_SERVER["HTTP_REFERER"] != "" and substr($_SERVER["HTTP_REFERER"], -strlen($here)) != $here) {
			echo "<a href='{$_SERVER["HTTP_REFERER"]}'>Назад</a> | ";
		}
	?>
	<a href="/">Главная</a>
</p>