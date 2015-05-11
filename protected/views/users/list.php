<?php
	$this->pageTitle = "Переводчики";
?>

<style type='text/css'>
	.grid-view {padding-top:0 !important;}
	.grid-view table.items td.r, .grid-view table.items th.r {background:#eee; font-weight:bold;} /* колонка, по которой идёт сортировка */
</style>

<h1>Рейтинг переводчиков</h1>

<?php
	$this->widget("bootstrap.widgets.TbGridView", array(
		"dataProvider" => $users_dp,
		"type" => "stripped condensed",
		"template" => "{pager} {items} {pager}",
		"columns" => array(
			array("value" => '$this->grid->dataProvider->pagination->currentPage * $this->grid->dataProvider->pagination->pageSize + ($row+1)', "type" => "text", "header" => ""),
			array("value" => '$data->ahref', type => "html", "header" => "ник", "headerHtmlOptions" => array("class" => "m"), ),
			array("name" => "rate_u", type => "number", "header" => "карма"),
			array("name" => "n_trs", type => "number", "header" => "количество переводов"),
			array("name" => "rate_t", type => "number", "header" => "суммарный рейтинг"),
			array("value" => '$data->n_trs ? sprintf("%.02f", $data->rate_t / $data->n_trs) : ""', "header" => "средний рейтинг перевода"),
		),
	));
?>