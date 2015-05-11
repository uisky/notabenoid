/** Редактор оглавления **/
var CE = {
	init: function() {
		var res;
		if(res = /#ed=(\d+)/.exec(location.hash)) {
			CE.ed.go(res[1]);
		}
	},
	placement: 0,
	save_html: "",
	id: -1,
	ed: function(id) {
		if(this.id == id) { this.cancel(); return false; }
		if(this.id != -1) this.cancel();
		this.id = id;

		var $tr = $("#Chapters #c_" + id);
		this.save_html = $tr.html();
		$tr.html("<td colspan='10' class='loading'><p>Минутку...</td>");

		$.get("/book/" + Book.id + "/" + id + "/edit", {ajax: 1, placement: this.placement}, function(html) {
			html = "<td colspan='10' class='editing'>" + html + "</td>";
			$tr.html(html);
			$("#chap-ed [name='title']").focus();
		});

		return false;
	},
	cancel: function() {
		if(this.id == -1) return false;
		if(this.id == 0) {
			$("#info_empty").show();
			$("#Chapters #c_0").remove();
		} else {
			$("#Chapters #c_" + this.id).html(this.save_html).removeClass("editing");
		}

		$("#Chapters th.dat").text("добавлено");
		$("#Chapters td.dat").each(function() {
			$(this).html($(this).attr("data-cdate"));
		})

		this.id = -1;
		this.save_html = "";

		return false;
	},
	add: function(where) {
		if(this.id != -1) this.cancel();
		this.placement = where;
		var html = "<tr id='c_0'<td colspan='6'></td></tr>";
		if(where == 0) {
			var ord = 0;
			$("#Chapters").append(html);
			$("#info_empty").hide();
		} else if(where == -1) {
//			var ord = parseInt($("#Chapters tr[id]:first td.dat").attr("data-ord")) - 10;
			$("#Chapters tr:first").after(html);
		} else if(where == 1){
//			var ord = parseInt($("#Chapters tr[id]:last td.dat").attr("data-ord")) + 10;
			$("#Chapters tr:last").after(html);
		}

		this.ed(0);
		if(where == 1) $.scrollTo("#c_0");

		return false;
	},
	moderator_toolbar: "",
	reorder_html: "",
	reorder_sortable: null,
	reorder: function() {
		CE.moderator_toolbar = $("#moderator-toolbar").html();
		CE.reorder_html = $("#Chapters").html();

		$("#moderator-toolbar").html(
			"<form id='form-reorder' method='post' action='" + "/book/" + Book.id + "/reorder" + "'>" +
			"<p style='font-size: 11px; float: left; margin-right: 20px; '>Перетаскивайте главы мышкой куда надо. </p>" +
			"<a href='#' onclick='return CE.reorder_done()' class='btn btn-small'><i class='icon-ok'></i> Готово</a> " +
			"<a href='#' onclick='return CE.reorder_cancel()' class='btn btn-small'><i class='icon-ban-circle'></i> Отмена</a></form> "
		);

		CE.reorder_sortable = new Sortable($("#Chapters tbody")[0], {
			scroll: true
		});

		return false;
	},
	reorder_done: function(e) {
		var order = CE.reorder_sortable.toArray();

		for(var i in order) {
			$("#form-reorder").append("<input type='hidden' name='ord[]' value='" + order[i] + "' />");
		}

		$("#form-reorder").submit();

		return false;
	},
	reorder_cancel: function() {
		$("#moderator-toolbar").html(CE.moderator_toolbar);
		$("#Chapters").html(CE.reorder_html);
		$("#Chapters tbody").sortable("destroy").enableSelection();

		return false;
	}
};
$(CE.init);
