<?php
/**
 * @var Bookmark[] $bookmarks
 * @var char $typ
 */
?>
<style type="text/css">
#BMList .e {display:none; cursor:pointer;}
#BMList li:hover .e {display:inline;}
#BMList .to-remove {color:#777; text-decoration: line-through;}
</style>

<script type="text/javascript">
var E = {
	editing: null,
	editing_html: "",
	init: function() {
		$("#BMList").sortable({
			update: E.sort_update
		}).disableSelection();

	},
	sort_update: function(e, ui) {
		$.post("/my/bookmarks_ord", $("#BMList").sortable("serialize"), function(data) {
			if(data != "ok") alert(data);
		});
	},
	ed: function(id) {
		if(E.editing) E.cancel();
		var $li = $("#BMList #b_" + id);

		E.editing = id;
		E.editing_html = $li.html();

		var title = $li.find("a:first").text();
		var html = "<form method='post' class='form-inline' id='form-ed' action='/my/bookmarks_edit?id=" + id + "'>" +
			"<input type='text' name='Bookmark[title]' class='span4' /> " +
			"<button type='submit' class='btn btn-primary'>Сохранить</button> " +
			"<button type='button' class='btn btn-danger' onclick='E.rm()'>Удалить</button> " +
			"<button type='button' class='btn' onclick='E.cancel()'>Отмена</button> " +
			"</form>";
		$li.html(html);
		$("#form-ed [name=Bookmark\\[title\\]]").val(title).focus();

		return false;
	},
	cancel: function() {
		if(!E.editing) return false;
		$("#BMList #b_" + E.editing).html(E.editing_html);
		E.editing = null;
		E.editing_html = "";
		return false;
	},
	rm: function() {
		if(!confirm("Вы уверены?")) return false;

		$("#form-rm [name=id]").val(E.editing);
		$("#form-rm").submit();
	},
	mass_rm: function() {
		$("#bookmarks-mass-rm").hide();
		$("#bookmarks-mass-rm-on").show();
		$("#BMList a").click(E.mass_rm_click);
	},
	mass_rm_cancel: function() {
		$("#form-mass-rm [name=id\\[\\]]").remove();
		$("#bookmarks-mass-rm").show();
		$("#bookmarks-mass-rm-on").hide();
		$("#BMList a").unbind("click", E.mass_rm_click).removeClass("to-remove");
	},
	mass_rm_click: function(e) {
		e.preventDefault();
		var $this = $(this);
		var id = $this.parents("li").attr("id").substr(2);
		if($this.hasClass("to-remove")) {
			$("#form-mass-rm [value=" + id + "]").remove();
			$this.removeClass("to-remove");
		} else {
			$("#form-mass-rm").append("<input type='hidden' name='id[]' value='" + id + "' />");
			$this.addClass("to-remove");
		}
		$("#form-mass-rm :submit").attr("disabled", $("#form-mass-rm [name=id\\[\\]]").length == 0);
		return false;
	}
}
$(E.init);
</script>

<h1>Мои закладки</h1>
<ul class='nav nav-pills'>
<?php
	foreach(Yii::app()->params["bookmark_types"] as $k => $v) {
		if($k == "*") continue;
		echo "<li" . ($k == $typ ? " class='active'" : "") . "><a href='?typ={$k}'>{$v}</a></li>";
	}
?>
</ul>

<ul id="BMList">
	<?php
	foreach($bookmarks as $bm) {
		echo "<li id='b_{$bm->id}'>";
		echo "<a href='{$bm->url}'>{$bm->title}</a> ";
		echo "<a href='#' class='e' onclick='return E.ed({$bm->id})'><i class='icon-edit'></i></a> ";
		echo "</li>";
	}
	?>
</ul>

<form method="post" action="/my/bookmarks_remove" id="form-rm"><input type="hidden" name="id" /></form>