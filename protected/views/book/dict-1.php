<?php
	/**
	 * @var Dict[] $dict
	 * @var Book $book
	 * @var boolean $ajax
	 */
?>
<?php if(count($dict) == 0): ?>
	<p>Словарь этого перевода пуст.</p>
<?php else: ?>

<?php
	foreach($dict as $d) {
		echo "<div rel='{$d->id}'>";
		if($book->can("dict_edit")) {
			echo "<a href='#' class='e'><i class='i icon-edit'></i></a> ";
			echo "<a href='#' class='x'><i class='i icon-remove'></i></a> ";
		}
		echo "<span class='o'>{$d->term}</span> ";
		echo "<span class='t'>{$d->descr}</span>";
		echo "</div>";
	}
?>
<?php endif; ?>