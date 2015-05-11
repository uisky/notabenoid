<style type="text/css">
	#Tree div.n {padding:1px 4px}
	#Tree div.current {background:#444; color:#fff;}
	#Tree div.current a {color:#fff;}
	#Tree div a.c {display:none;}
	#Tree div:hover a.c {display:inline;}
</style>

<div class="modal-header">
	<a class="close" data-dismiss="modal">×</a>
	<h3>Выберите раздел</h3>
</div>
<div class="modal-body">

<ul id="Tree">
<?php
	$prev_indent = 0;
	$indent = 0;
	foreach($tree as $cat) {
		$indent = count($cat->mp);

		if($indent > $prev_indent) {
			echo "\n<ul>\n";
		} else {
			echo str_repeat("</li>\n</ul>\n", $prev_indent - $indent) . "</li>\n";
		}
		echo "<li>";

		echo "<div id='n{$cat->id}' class='n" . ($book->cat_id == $cat->id ? " current" : "") . "'>";
		echo "<a href='/search/?cat={$cat->id}' class='cat'>";
		echo $cat->title;
		echo "</a>";
		if($cat->booksCount > 0) echo " ({$cat->booksCount})";
		echo "</div>";

		$prev_indent = $indent;
	}
	echo str_repeat("</li>\n</ul>\n", $indent);
?>
</ul>

</div>
<div class="modal-footer">
	<a href="#" class="btn" data-dismiss="modal">Отмена</a>
</div>