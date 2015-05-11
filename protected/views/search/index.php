<?php
	/**
	* @var SearchFilter $filter
	* @var CActiveDataProvider $dp
	*/

	$this->pageTitle = "Поиск";
	$this->ad_deny[Controller::AD_PLACE_BOTTOM] = true;
	$this->ad_deny[Controller::AD_PLACE_SIDE] = true;
?>
<style type="text/css">
ul.search-results {list-style:none; padding:0; margin:0;}
ul.search-results li {margin:10px 0; padding:5px; clear: both;}
ul.search-results li:hover { background:#eee; }
ul.search-results li .th {float:left; margin:0 5px 0 0;}
ul.search-results li .th img {width:50px; height:auto;}
ul.search-results li p {margin: 0}
ul.search-results li .ready {float: right;}
ul.search-results li .meta {font-size:11px;}
ul.search-results li .meta .cat {float:right;}
</style>
<script type="text/javascript">
$(function() {
	$("#search-ghost-form [name=t]").bind("change keyup", function() {
		$("#form-search [name=t]").val($(this).val());
	});
	$("#search-ghost-form").submit(function(e) {
		e.preventDefault();
		$("#form-search").submit();
		return false;
	});
});

// Размер шрифтов
var yandex_ad_fontSize = 1;

// Настройки объявлений Директа
var yandex_direct_fontColor = '000000';
var yandex_direct_titleColor = '005580';
var yandex_direct_siteurlColor = '006600';
var yandex_direct_linkColor = '005580';
function yandex_direct_print(){ }

// Настройки объявлений Маркета
var yandex_market_fontColor = '000000';
var yandex_market_catColor = '666666';
var yandex_market_offerlinkColor = '005580';
var yandex_market_linkColor = '005580';
function yandex_market_print(){ }

var yandex_r = Math.round(Math.random() * 100000);
document.write('<sc'+'ript type="text/javascript" src="http://an.yandex.ru/code/102609?rnd=' + yandex_r + '&text=<?php echo urlencode($filter->t); ?>&page-no=<?php echo $dp->pagination->currentPage; ?>&charset=utf-8"></'+'sc'+'ript>');
</script>

<h1>Поиск переводов</h1>

<form id="search-ghost-form" class="form-inline" action="/NOWHERE">
	<input type="text" name="t" value="<?=CHtml::encode($filter->t); ?>" class="span7" />
	<button type="submit" class="btn btn-primary"><i class="icon-search icon-white"></i> Найти</button>
</form>



<?php if($filter->doSearch): ?>
<?php
	/** @var Book[] $book */
	$books = $dp->getData();
	if($dp->totalItemCount == 0) {
		echo <<<HTML
			<p class="alert alert-block alert-info">
				Ничего не найдено.
			</p>
HTML;
	} else {
		echo "<h3>" . Yii::t("app", "Найден {n} перевод|Найдено {n} перевода|Найдено {n} переводов", $dp->totalItemCount) . "</h3>";
		$this->widget('bootstrap.widgets.TbPager', array("pages" => $dp->pagination, "header" => "<div class='pagination' style='margin-bottom:0'>"));
?>

    <!-- Яндекс.Директ должен быть размещен на первом экране страницы с результатами поиска -->
    <script type="text/javascript">yandex_direct_print()</script>

<?php
		echo "<ul class='search-results'>";
		foreach($books as $book) {
			echo "<li>";

//			if($book->img->exists) echo "<div class='th'>" . $book->img->tag . "</div>";

			echo "<p>";
			echo $book->ahref;
			if(!$filter->ready) echo "<span class='ready'>{$book->ready}</span>";

			echo "</p>";

			echo "<div class='meta'>";
			echo "<i class='ac_read {$book->ac_read}'></i><i class='ac_gen {$book->ac_gen}'></i><i class='ac_tr {$book->ac_tr}'></i> ";

			echo Yii::app()->params["book_types"][$book->typ] . " ";
			if(!$filter->s_lang || !$filter->t_lang) echo Yii::app()->langs->from_to($book->s_lang, $book->t_lang) . " ";
			echo "от " . $book->owner->ahref;

			if(!$filter->cat && $book->cat_id) echo " <small class='cat'><a href='/search?cat={$book->cat_id}'>{$book->cat->title}</a></small> ";
			if($filter->sort == 3) echo "<br />создано " . Yii::app()->dateFormatter->format("d.MM.yyyy HH:mm", $book->cdate);
			elseif($filter->sort == 4) echo "<br />последняя активность " . Yii::app()->dateFormatter->format("d.MM.yyyy HH:mm", $book->last_tr);
			echo "</div>";

			echo "</li>";
		}
		echo "</ul>";
		$this->widget('bootstrap.widgets.TbPager', array("pages" => $dp->pagination));
	}
?>
<?php else: ?>
	<div class="alert alert-info">
		Пожалуйста, выберите раздел каталога, язык или введите поисковый запрос.
	</div>
<?php endif; ?>