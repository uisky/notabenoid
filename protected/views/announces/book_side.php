<div class="tools">
	<h5>Анонсы перевода</h5>
	<p>
		Анонсы &mdash; это как бы лента новостей проекта перевода.
		Анонсы <?php
			$A = array(
				"g" => "пишут члены <a href='" . $book->getUrl("members") . "'>группы перевода</a>",
				"m" => "пишут модераторы перевода",
				"o" => "пишет владелец перевода ({$book->owner->ahref})",
			);
			echo $A[$book->ac_announce] . ". ";

			if($book->ac_read == "a") echo "Так как этот перевод &ndash; открытый, все его анонсы попадают также и в <a href='/announces'>общую ленту анонсов</a>.";
			else echo "Так как это &mdash; закрытый перевод, его анонсы не попадают в <a href='/announces'>общую ленту анонсов</a>."
		?>
	</p>

	<?php if($book->can("announce")): ?>
		<a href="<?=$book->getUrl("announces/write"); ?>" class="btn btn-warning"><i class="icon-wrench icon-white"></i> Написать анонс</a>
	<?php endif; ?>
</div>