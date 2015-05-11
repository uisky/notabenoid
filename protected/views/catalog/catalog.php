<?php
	/**
	 * @var Category $cat
	 * @var Category $tree
	 * @var CActiveDataProvider $books_dp
	 */
	$this->pageTitle = "Каталог переводов";
?>
<style type="text/css">
	#Tree div.n {padding:1px 4px}
	#Tree div.current {background:#444; color:#fff;}
	#Tree div.current a {color:#fff;}
	#Tree div a.c {display:none;}
	#Tree div:hover a.c {display:inline;}
</style>

<h1>Каталог: <?=$cat->pathHtml; ?></h1>

<?php if(count($tree) > 1): ?>
<ul id="Tree">
<?php
	$prev_indent = 0;
	$indent = 0;
	foreach($tree as $c) {
		$indent = count($c->mp);

		if($indent > $prev_indent) {
			echo "\n<ul>\n";
		} else {
			echo str_repeat("</li>\n</ul>\n", $prev_indent - $indent) . "</li>\n";
		}
		echo "<li>";

		echo "<div id='n{$c->id}' class='n'>";
		echo "<a href='/search/?cat={$c->id}'>";
		echo $c->title;
		echo "</a>";
		if($c->booksCount > 0) echo " ({$c->booksCount})";
		echo "</div>";

		$prev_indent = $indent;
	}
	echo str_repeat("</li>\n</ul>\n", $indent);
?>
</ul>

<p>
    <a href="/search?cat=<?=$cat->id; ?>&sort=3">Что нового?</a> |
    <a href="/search?cat=<?=$cat->id; ?>&sort=4">Что сейчас переводят?</a>
</p>

<?php endif; ?>

<?php if(false && $books_dp): ?>
<?php
	if($books_dp->totalItemCount) {
		$books = $books_dp->data;

		echo "<h3>" . Yii::t("app", "{n} перевод|{n} перевода|{n} переводов", $books_dp->totalItemCount) . "</h3>";
		$this->widget('bootstrap.widgets.TbPager', array("pages" => $books_dp->pagination));
		echo "<ul class='booklist has-icons'>";
		foreach($books as $book) {
			echo "<li>";
			echo "<i class='ac_read {$book->ac_read}'></i><i class='ac_gen {$book->ac_gen}'></i><i class='ac_tr {$book->ac_tr}'></i> ";
			echo "{$book->ready} ";
			echo "{$book->ahref} ";
			echo Yii::app()->langs->from_to($book->s_lang, $book->t_lang) . " ";
			echo "</li>";
		}
		echo "</ul>";
		$this->widget('bootstrap.widgets.TbPager', array("pages" => $books_dp->pagination));
	} else {
		echo "<div class='alert alert-block'>В этом разделе пока ещё нет ни одного перевода.</div>";
	}
?>

<?php endif; ?>