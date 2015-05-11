<?php
	$this->pageTitle = "{$book->fullTitle} - скопировать словарь";
?>

<h1>Скопировать словарь</h1>

<p>
	Вы можете скопировать в перевод &laquo;<?=$book->fullTitle; ?>&raquo; словарь из любого другого перевода, в котором
	являетесь модератором. Если слово уже есть в словаре, оно не будет скопировано. Выберите, откуда копировать словарь:
</p>

<?php if(count($sources) == 0): ?>
<div class="alert alert-block alert-info">
	К сожалению, вы не являетесь модератором ни одного перевода со словарём.
</div>
<?php else: ?>
<ul>
<?php
	foreach($sources as $b) {
		echo "<li><a href='?from={$b->id}'>{$b->fullTitle}</a> ({$b->dict_cnt})</li>";
	}
?>
</ul>
<?php endif; ?>
<p>
    &larr; <a href="<?=$book->url; ?>">Вернуться к оглавлению перевода.</a>
</p>