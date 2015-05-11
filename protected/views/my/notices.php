<?php
	/**
	 * @var CActiveDataProvider $notices_dp
	 * @var Notice[] $notices
	 * @var boolean $ajax
	 */

	$this->pageTitle = "Оповещения";
?>
<?php if(!$ajax): ?>
<style type="text/css">
#Notices {margin:0; padding:0; list-style: none;}
#Notices li {
	margin:0 0 10px 0;
	padding:10px;
	border-radius: 5px;
	border:1px solid #ccc;
}
#Notices li.new {
	background:#eee;
	border-color:#777;
}
#Notices li.deleting {
	color:#777;
	background:#d0d0d0 url("/i/pacman-d0d0d0.gif") no-repeat center center;
}
#Notices li.deleting p.meta { border-top: none; }
#Notices li.deleting a {color:#777;}
#Notices li p.meta {
	border-top:1px solid #ccc;
	margin:5px 0 0 0;
	padding:0;
	font-size:11px;
}
#Notices .u {
	display:none;
	float:right;
	margin-top:4px;
}
#Notices li:hover .u { display: block; }
</style>

<script type="text/javascript">
var N = {
	init: function() {
		$("#Notices a.rm").attr("title", "Удалить это оповещение");
		$("#Notices").delegate(".u a.rm", "click", N.rm_click);
	},
	rm_click: function(e) {
		e.preventDefault();
		var $li = $(this).parents("li");
		var id = $li.attr("id").substr(1);
		$li.addClass("deleting");
		$(this).remove();
		$.ajax({
			url: "http://" + location.host + location.pathname + location.search,
			type: "POST",
			data: {rm: id},
			dataType: "html",
			success: function(data) {
				$("#Notices").replaceWith(data);
				N.init();
			}
		});
	}
};
$(N.init);
</script>

<h1>Оповещения</h1>
<?php endif; ?>

<?php if(!$ajax && $notices_dp->totalItemCount == 0): ?>
<div class="alert alert-info">
	У вас нет оповещений.
</div>
<?php else: ?>

<?php
	$notices = $notices_dp->getData();

	if(!$ajax) $this->widget('bootstrap.widgets.TbPager', array("pages" => $notices_dp->pagination, "header" => "<div class='pagination' style='margin-bottom:0'>"));

	echo "<ul id='Notices'>";
	foreach($notices as $notice) {
		echo "<li" . ($notice->seen ? "" : " class='new'") . " id='n{$notice->id}'>";
		echo "<p>" . $notice->render() . "</p>";
		echo "<div class='u'><a href='#' class='rm'><i class='icon-remove'></i></a></div>";
		echo "<p class='meta'>" . Yii::app()->dateFormatter->formatDateTime($notice->cdate, "medium", "medium") . "</p>";
		echo "</li>";
	}
	echo "</ul>";

	if(!$ajax) $this->widget('bootstrap.widgets.TbPager', array("pages" => $notices_dp->pagination, "header" => "<div class='pagination' style='margin-bottom:0'>"));
?>


<?php endif; ?>