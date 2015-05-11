<?php
/**
 * @var Chapter $chap
 */
?>
<h1>Скачать перевод <?php echo "{$chap->book->fullTitle}: {$chap->title}"; ?></h1>
<p>
	В этой главе нет ни одного переведённого фрагмента.
</p>
<p>
	<a href="<?=$chap->book->url; ?>">К оглавлению</a> |
	<?php if($chap->can("tr")) echo "<a href='{$chap->url}'>Перевести</a>"; ?>
</p>