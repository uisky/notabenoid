<?php
	/**
	 * @var CActiveDataProvider $books_dp
	 * @var Category[] $categories
	 * @var Book[] $books
	 */

	$this->pageTitle = "Модерация: переводы по разделам";
	$books = $books_dp->getData();
?>
<style type="text/css">
#Utils .pagination {margin:0;}
</style>
<script type="text/javascript">
var T = {
	init: function() {
	}
};
$(T.init);
</script>

<?php $this->renderPartial("_header"); ?>

<h1>Переводы по разделам</h1>

<?php
	echo "<div class='row' id='Utils'><div class='span8'>";
	$this->widget('bootstrap.widgets.TbPager', array("pages" => $books_dp->pagination, "maxButtonCount" => 15));

	echo "</div><div class='span4'>";

	$r = rand(1, $books_dp->pagination->pageCount - 1);
	echo "<a href='/moderator/book_cat/Book_page/{$r}' class='btn'>Случайная страница</a>";

	echo "</div></div>";
?>

<form method="post" id="form-cat">
<table class="table table-bordered">
<tr>
	<th>Перевод</th>
	<th>Старые разделы</th>
	<th>Новый раздел</th>
</tr>
<?php
	function topics($book) {
		$T = Yii::app()->params["book_topics"][$book->typ];
		$ret = "";
		foreach($T as $k => $v) {
			if(substr($book->topics, -1 - $k, 1) != 1) continue;
			if($ret != "") $ret .= ", ";
			$ret .= $v;
		}
		return $ret;
	}


	foreach($books as $i => $book) {
		echo "<tr>";
		echo "<td>";
		echo $book->ahref;
		echo " <i class='ac_read {$book->ac_read}'></i> {$book->ready}";
		echo "<br />" . Yii::app()->params["book_types"][$book->typ] . " ";
		echo "[ ";
		if($book->typ == "S") {
			echo "<a href='http://www.imdb.com/find?q=" . urlencode($book->s_title) . "&s=tt' target='_blank'>IMDb</a> | ";
			echo "<a href='http://www.kinopoisk.ru/index.php?first=no&what=&kp_query=" . urlencode($book->s_title) . "&s=tt' target='_blank'>КП</a> | ";
		} else {

		}
		echo "<a href='/search/?t=" . urlencode($book->s_title) . "' target='_blank'>NB</a>";
		echo " ] ";

		echo "</td>";

		echo "<td>";
		echo topics($book);
		echo "</td>";

		echo "<td>";
		echo "<select name='cat_id[{$book->id}]'>";
		echo "<option value='0'></option>";
		echo "<option value='-1'>Вне каталога</option>";
		foreach($categories[$book->typ] as $cat) {
			echo "<option value='{$cat->id}'" . ($cat->id == $book->cat_id ? " selected" : "") . (!$cat->available ? " disabled" : "") . ">{$cat->title}</option>";
		}
		echo "</select>";

		if($i) echo " <a href='#' onclick='return T.copy(this)' title='Как у предыдущего'>&uarr;</a>";

		echo "</td>";

		echo "</tr>";
	}
?>
</table>

<div class="form-actions">
	<button type="submit" class="btn btn-inverse"><i class="icon-ok icon-white"></i> Сохранить</button>
</div>

</form>