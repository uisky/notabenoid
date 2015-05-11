<?php
/**
 * @var Dict[] $dict
 * @var Book $book
 * @var boolean $ajax
 */

	$this->pageTitle = $book->fullTitle . " - словарь";
	if(!$ajax) $book->registerJS();
?>
<style type="text/css">
#Dict dd {
	x-font-size:12px;
	margin-bottom: 9px;
}

#dict-search-input {width:98%;}

#Dict dt.editing, #Dict dd.editing {
	margin:0;
	padding:15px;
	background:#eee;
	font-weight: normal;
	x-font-size:11px;
}
#Dict dt.editing { padding-bottom:0; border-top-left-radius: 10px; border-top-right-radius: 10px; }
#Dict dd.editing { padding-top:0; border-bottom-left-radius: 10px; border-bottom-right-radius: 10px; margin-bottom:10px;}
#Dict .editing .t {width:400px;}


#Dict dt a.e {display:none;}
#Dict dt:hover a.e {display:inline;}
</style>

<script type="text/javascript">
var Dict = {
	init_done: false,
	init: function() {
		$("#dict-search-input").keyup(Dict.handlerSearch);
		$("#Dict").delegate("a.e", "click", Dict.handlerEdit);
		$("#Dict a.e").attr("title", "Редактировать");
	},
	handlerSearch: function(e) {
		var srch = $(this).val().toLowerCase();
		if(srch == "") {
			$("#Dict dt, #Dict dd").show();
		} else {
			$("#Dict dt").each(function() {
				if($(this).text().substr(0, srch.length).toLowerCase() == srch) {
					$(this).next("dd").andSelf().show();
				} else {
					$(this).next("dd").andSelf().hide();
				}
			});
		}
	},
	handlerEdit: function(e) {
		e.preventDefault();
		var id = $(this).parent("dt").attr("rel");
		Dict.ed(id);
	},

	ed_id: -1, ed_html: {},
	ed: function(id) {
		if(Dict.ed_id != -1) Dict.cancel();
		Dict.ed_id = id;

		$("#dict-tools").hide();

		if(id == 0) {
			$dt = $("<dt rel='0'></dt>").appendTo("#Dict");
			$dd = $("<dd></dd>").appendTo("#Dict");
		} else {
			var $dt = $("#Dict dt[rel=" + id + "]");
			var $dd = $dt.next("dd");
			Dict.ed_html.dt = $dt.html();
			Dict.ed_html.dd = $dd.html();
			var term = $.trim($dt.text());
			var descr = $.trim($dd.text());
		}

		var html_dt = "<input type='hidden' name='id' value='" + id + "' />";
		html_dt += "Слово: <input type='text' name='term' class='t' />";

		var html_dd = "Перевод: <input type='text' name='descr' class='t' /><br />" +
			"<button type='submit' class='btn'>Сохранить</button> ";
		if(id != 0) html_dd += "<button type='button' class='btn' onclick='Dict.rm()'>Удалить</button> ";
		html_dd += "<button type='button' class='btn' onclick='Dict.cancel()'>Отмена</button>";
		$dt.addClass("editing").html(html_dt);
		$dd.addClass("editing").html(html_dd);
		$("#dict-ed-form [name='term']").val(term).focus();
		$("#dict-ed-form [name='descr']").val(descr);

		$("#dict-ed-form").ajaxForm({
			data: {ajax: 1},
			dataType: "json",
			success: function(data) {
				if(data.error) {
					alert(data.error);
					return false;
				}
				if(id == 0) {
					var html = "<dt rel='" + data.id + "'>" + data.term + " <a href='#' class='e'><i class='icon-edit'></i></a></dt>" +
						"<dd>" + data.descr + "</dd>";
					$("#Dict").append(html);
					$dt.remove();
					$dd.remove();
				} else {
					$dt.removeClass("editing").html(data.term + " <a href='#' class='e'><i class='icon-edit'></i></a>");
					$dd.removeClass("editing").html(data.descr);
				}
				Dict.ed_id = -1;
				Dict.ed_html = {};
				$("#dict-tools").show();
			}
		});

		return false;
	},
	rm: function() {
		if(!confirm("Вы уверены?")) return false;
		$.ajax({
			url: Book.url("dict_rm"),
			type: "POST",
			data: {ajax: 1, id: Dict.ed_id},
			dataType: "json",
			success: function(data) {
				if(data.error) return !!alert(data.error);

				$("#Dict [rel='" + data.id + "']").next("dd").andSelf().remove();

				Dict.cancel();
			}
		})
	},
	cancel: function() {
		if(Dict.ed_id == 0) {
			$("#Dict .editing").remove();
		} else {
			var $dt = $("#Dict dt[rel=" + Dict.ed_id + "]");
			var $dd = $dt.next("dd");
			$dt.removeClass("editing").html(Dict.ed_html.dt);
			$dd.removeClass("editing").html(Dict.ed_html.dd);
		}

		Dict.ed_id = -1;
		Dict.ed_html = {};
		$("#dict-tools").show();
		return false;
	}
};
$(Dict.init);
</script>

<?php if(!$ajax): ?>
<ul class='nav nav-tabs'>
	<li><a href='<?=$book->url; ?>/'>оглавление</a></li>
	<li><a href='<?=$book->url("members"); ?>'>переводчики</a></li>
	<li><a href='<?=$book->url("blog"); ?>'>блог</a></li>
</ul>

<h1><?=$book->fullTitle; ?> - словарь</h1>
<?php endif; ?>

<form class="form-inline">
	<input type="text" id="dict-search-input" placeholder="Поиск по словарю..." />
</form>

<?php if(count($dict) == 0): ?>
	<p>Словарь этого перевода пуст.</p>
<?php endif; ?>

<?php
	if($book->can("dict_edit")) echo "<form method='post' action='" . $book->getUrl("dict_edit") . "' id='dict-ed-form'>";
	echo "<dl id='Dict'>";
	foreach($dict as $d) {
		echo "<dt rel='{$d->id}'>{$d->term}";
		if($book->can("dict_edit")) echo " <a href='#' class='e'><i class='icon-edit'></i></a>";
		echo "</dt>";
		echo "<dd>{$d->descr}</dd>";
	}
	echo "</dl>";
	if($book->can("dict_edit")) echo "</form>";
?>

<?php if($book->can("dict_edit")): ?>
<p id="dict-tools">
	<a href="#" onclick="return Dict.ed(0)" class="btn btn-small"><i class="icon-plus-sign"></i> Добавить слово</a>
	<?php if(!$ajax): ?>
		<a href="<?=$book->getUrl(); ?>" class="btn btn-small"><i class="icon-list"></i> К оглавлению</a>
	<?php endif; ?>
</p>
<?php endif; ?>