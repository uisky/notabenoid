<?php
	$html = array(-1 => "", 1 => "");
	$cnt = array(-1 => 0, 1 => 0);
	$my_mark = 0;

	foreach($tr->marks as $mark) {
		if($html[$mark->mark] != "") $html[$mark->mark] .= ", ";
		$html[$mark->mark] .= $mark->user->ahref;

		if($mark->user->id == Yii::app()->user->id) $my_mark = $mark->mark;
		$cnt[$mark->mark]++;
	}
?>
<div class="modal-header">
	<a class="close" data-dismiss="modal">×</a>
	<h3>Рейтинг версии перевода = <?=$tr->rating; ?></h3>
</div>
<div class="modal-body">
<?php
	foreach(array(1 => "Плюсы", -1 => "Минусы") as $sign => $title) {
		echo "<h3>{$title}" . ($cnt[$sign] > 0 ? " ({$cnt[$sign]})" : "") . ":</h3>";
		if($cnt[$sign] == 0) echo "<p>Нет.</p>";
		else echo "<p>{$html[$sign]}</p>";
	}
?>
</div>
<div class="modal-footer">
	<?php if($my_mark && $chap->can("rate")): ?> <a href="#" class="btn btn-warning" onclick='T.rate.vote(<?=$tr->id; ?>, 0); return false;' data-dismiss="modal">
		Удалить вашу оценку
		(<?php echo ($my_mark < 0 ? "&minus;" : "+") . $my_mark; ?>)
	</a> <?php endif; ?>
	<a href="#" class="btn" data-dismiss="modal">Закрыть</a>
</div>