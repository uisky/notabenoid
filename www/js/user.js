var BM = {
	data: [],
	order: 1,
	title_part: "s",	// "s", "t"
	moder_only: 0,

	init: function() {
		var $win = $("#bookmarks");

		BM.loadIni();

		$("#bookmarks-tb-title [data-v=" + BM.title_part + "]", $win).button('toggle');
		$("#bookmarks-tb-sort [data-v=" + BM.order + "]", $win).button('toggle');
		if(BM.moder_only) $("#bookmarks-tb-status button").button("toggle");

		$("#bookmarks-tb-title button").click(function(e) {
			BM.title_part = $(this).data("v");
			BM.saveIni();
			BM.sort();
			BM.render();
		});

		$("#bookmarks-tb-sort button").click(function(e) {
			BM.order = $(this).data("v");
			BM.saveIni();
			BM.sort();
			BM.render();
		});

		$("#bookmarks-tb-status button").click(function(e) {
			console.log($(this).hasClass("active"));
			BM.saveIni();
		});

		$("#bookmarks-tb-rm").click(function(e) {
			var $this = $(this), $ul = $("#bookmarks-list");

			if($ul.hasClass("ed")) {
				$ul.removeClass("ed");
				$("#bookmarks-tb-ed").removeClass("active");
			}

			if($ul.hasClass("rm")) {
				$ul.removeClass("rm");
				$this.removeClass("active");
			} else {
				$ul.addClass("rm");
				$this.addClass("active");
			}
		});

		$("#bookmarks-tb-ed").click(function(e) {
			var $this = $(this), $ul = $("#bookmarks-list");

			if($ul.hasClass("rm")) {
				$ul.removeClass("rm");
				$("#bookmarks-tb-rm").removeClass("active");
			}

			if($ul.hasClass("ed")) {
				$ul.removeClass("ed");
				$this.removeClass("active");
			} else {
				$ul.addClass("ed");
				$this.addClass("active");
			}
		});

		$("#bookmarks").delegate("#bookmarks-list li .rm", "click", function(e) {
			if(!confirm("Удалить эту закладку?")) return false;
			var t = $(this).closest("li").attr("id").split("-");
			$.ajax({
				type: "POST",
				url: "/my/bookmarks/remove",
				data: {book_id: t[1], orig_id: t[2]},
				dataType: "json",
				success: function(data) {
					if(data.error) return !!alert(data.error);
					$("#bookmark-" + data.book_id + "-" + data.orig_id).remove();
				}
			});
		}).delegate("#bookmarks-list li .ed", "click", function(e) {
				var $li = $(this).closest("li");
				var id = $li.attr("id").split("-");
				var note = $li.children("div").find("span.note").text();
				var save_html = $li.html();
				var html = "<form method='POST' class='form-inline' action='/my/bookmarks/edit'>" +
					"<input type='hidden' name='book_id' value='" + id[1] + "' /><input type='hidden' name='orig_id' value='" + id[2] + "' />" +
					"<input type='text' name='note' class='span3' placeholder='Любое ваше примечание' /> " +
					"<button type='submit' class='btn btn-primary'><i class='icon-ok icon-white'></i> Сохранить</button> " +
					"<button type='button' class='btn cancel'><i class='icon-remove'></i> Отмена</button>" +
					"</form>";
				$li.html(html);
				$li.find("form").ajaxForm({
					dataType: "json",
					success: function(data) {
						$li.html(BM.renderItem(data, id[1]));
					}
				});
				$li.find("button.cancel").click(function() { $li.html(save_html); });
				$li.find("[name=note]").val(note).focus();
			});

		$win.on("shown", BM.loadData);
	},
	check: function() {
		if(BM.order == 0) {
			$("#bookmarks-list").sortable({
				update: function(event, ui) {
					var ids = $(this).sortable("toArray"), id, order = {}, ord;
					for(var i in ids) {
						id = ids[i].split("-")[1];
						ord = ids.length - i;
						order[id] = ord;
					}
					for(var i in BM.data) {
						BM.data[i].ord = order[BM.data[i].book.id];
					}

					$.ajax({
						url: "/my/bookmarks/reorder",
						type: "POST",
						data: order,
						dataType: "html"
					});
				}
			}).disableSelection();
		} else {
			$("#bookmarks-list").sortable("destroy").enableSelection();
		}
	},
	loadIni: function() {
		var ini = $.cookie("bm-ini");
		if(!ini) return;
		var a = ini.split(".");
		BM.order = parseInt(a[0]); BM.title_part = a[1]; BM.moder_only = parseInt(a[2]);
	},
	saveIni: function() {
		$.cookie("bm-ini", [BM.order, BM.title_part, BM.moder_only].join("."), {expires: 365, path: "/"});
	},
	loadData: function() {
		$.getJSON("/my/bookmarks/data", {}, function(data) {
			BM.data = data;
			BM.sort();
			BM.render();
		});
	},
	sort: function() {
		var sort_f = [
			/* 0 */ function(a, b) {
				a = parseInt(a.ord); if(isNaN(a)) a = 0;
				b = parseInt(b.ord); if(isNaN(b)) b = 0;
				return b - a;
			},
			/* 1 */ function(a, b) {
				var f = BM.title_part == "t" ? "t_title" : "s_title";
				a = a.book[f].toLowerCase();
				b = b.book[f].toLowerCase();
				return a == b ? 0 : (a < b ? -1 : 1);
			},
			/* 2 */ function(a, b) {
				a = parseInt(a.group.last_tr); if(isNaN(a)) a = 0;
				b = parseInt(b.group.last_tr); if(isNaN(b)) b = 0;
				return b - a;
			},
			/* 3 */ function(a, b) {
				a = parseInt(a.group.since); if(isNaN(a)) a = 0;
				b = parseInt(b.group.since); if(isNaN(b)) b = 0;
				return b - a;
			},
			/* 4 */ function(a, b) {
				a = parseFloat(a.book.ready); if(isNaN(a)) a = 0;
				b = parseFloat(b.book.ready); if(isNaN(b)) b = 0;
				return b - a;
			},
			/* 5 */ function(a, b) {
				a = parseFloat(a.cdate); if(isNaN(a)) a = 0;
				b = parseFloat(b.cdate); if(isNaN(b)) b = 0;
				return b - a;
			}
		];
		BM.data = BM.data.sort(sort_f[BM.order]);
	},
	render: function() {
		var html, bm;
		if(BM.data.length == 0) {
			html = "<p>Вы ещё не поставили ни одной закладки. Закладку на весь перевод можно поставить в его оглавлении, на любой фрагмент &mdash; в левой колонке интерфейса перевода. Также в закладки добавляются закрытые переводы, куда вы пытаетесь вступить.</p>";
		} else {
			html = "<ul id='bookmarks-list' class='" + $("#bookmarks-list").attr("class") + "'>";
			for(var i in BM.data) {
				bm = BM.data[i];

				html += "<li id='bookmark-" + bm.book.id + "-0' data-status='" + bm.group.status + "'><div>";
//				if(bm.group.status == 2) html += " <i class='icon-briefcase' title='Вы - модератор этого перевода'></i>";
				html += BM.renderItem(bm);
				html += "</div></li>";
			}
			html += "</ul>";
		}
		$("#bookmarks .modal-body").html(html);
		BM.check();
	},
	renderItem: function(bm, book_id) {
		var html = "";
		if(bm.orig) {
			html += "<i class='icon-remove rm'></i> <i class='icon-edit ed'></i> ";
			html += "<a href='/book/" + book_id + "/" + bm.orig.chap_id + "/" + bm.orig.id + "'>" + bm.orig.body + "</a>";
			if(bm.note) html += " &mdash; <span class='note'>" + bm.note + "</span>";
		} else {
			title = bm.book[BM.title_part == "t" ? "t_title" : "s_title"];
			if(bm.nOrigs > 0) html += "<i class='icon-plus ho' onclick='BM.origs(" + bm.book.id + ")' title='Закладок на фрагменты: " + bm.nOrigs + "'></i>";
			html += "<i class='icon-remove rm'></i> <i class='icon-edit ed'></i> ";
			if(title == "") title = "<i>без названия</i>";
			html += "<a href='/book/" + bm.book.id + "'>" + title + "</a> ";
			if(bm.group.status == 2) html += " <i class='icon-briefcase' title='Вы - модератор этого перевода'></i>";
			if(bm.watch) html += " <i class='icon-eye-open' title='Изменения статуса перевода будут приходить мне на почту'></i>";
			if(bm.note) html += " &mdash; <span class='note'>" + bm.note + "</span>";
			html += "<span class='r'>" + bm.book.ready + "</span>";
		}
		return html;
	},
	origs: function(book_id) {
		var $li = $("#bookmark-" + book_id + "-0"),
			$btn = $(".ho", $li);

		if($btn.hasClass("icon-refresh")) {
			return;
		} else if($btn.hasClass("icon-plus")) {
			$btn.removeClass("icon-plus").addClass("icon-refresh");
			$.getJSON("/my/bookmarks/data?book_id=" + book_id, function(data) {
				$btn.removeClass("icon-refresh").addClass("icon-minus");
				var html = "<ul class='origs'>", bm;
				for(var i in data) {
					bm = data[i];
					html += "<li id='bookmark-" + book_id + "-" + bm.orig.id + "'><div>";
					html += BM.renderItem(bm, book_id);
					html += "</div></li>";
				}
				html += "</ul>";
				$li.append(html);
			});
		} else {
			$btn.removeClass("icon-minus").addClass("icon-plus");
			$(".origs", $li).remove();
		}
	},
	set: function(book_id, orig_id, callback) {
		$("<div id='bookmark-set' class='modal'><p class='loading'>минуточку...</p></div>")
			.appendTo("body")
			.modal()
			.load("/my/bookmarks/set", {book_id: book_id, orig_id: orig_id}, function() {
				$("#bookmark-set form").ajaxForm({
					dataType: "json",
					success: function(data) {
						if(callback) callback(data);
						$("#bookmark-set").modal("hide").remove();
					}
				});
			});
	}
};


$(function() {
	BM.init();
//	$("#bookmarks").modal("show");
});