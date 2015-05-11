var Translator = {
	init: function() {
		Translator.init_hovers();
		$(window).unbind("resize", Sidebar.recalc).bind("resize", Translator.resize_dict);
		Translator.resize_dict();
	},
	init_hovers: function() {
		$("#Translator td.tu").hover(Translator.tu_over, Translator.tu_out);
		$("#Translator td.n").hover(Translator.td_n_over, Translator.td_n_out);
		$("#Translator td.c").hover(Translator.td_c_over, Translator.td_c_out);
		if(Book.show != 1) $("#Translator td.o").hover(Translator.show_gogo, Translator.hide_gogo);
	},

	resize_dict: function() {
		var h = $(window).height();
		h -= 240;
		$("#tab_dict").css("height", h + "px");
		Sidebar.recalc();
	},

	show_gogo: function() {
		var aid = this.parentNode.id.substr(1);
		$(this).append("<small>(<a href='?gogo=" + aid + "'>в контексте</a>)</small>");
	},

	hide_gogo: function() {
		$(this).children("small").remove();
	},

	tu_over: function() {
		var aid = this.parentNode.id.substr(1);
		var html = "";
		if(Book.can_translate) html += "<a href='#' onclick='return Translator.tr(" + aid + ")' title='Перевести' class='tr'>&raquo;&raquo;&raquo;</a>";
		if(Book.can_edit && Book.typ != "P") html += "<a href='#' onclick='return Translator.ed(" + aid + ")' title='Редактировать оригинал'>ред</a>";
		$(this).prepend(html);
	},
	tu_out: function() {
		$(this).children("a").remove();
	},


	td_n_over: function() {
		if(Translator.bm_aid) return false;
		var aid = this.parentNode.id.substr(1);

		var el = $(this).children("img.bm");
		if (el.length > 0) {
			return false;
		}

		$(this).append("<img src='/i/bookmark.gif' width='17' height='16' alt='B' title='Поставить закладку на этот абзац' class='bm new' onclick=\"Translator.bm_set(" + aid + ", '')\">");
	},
	td_n_out: function() {
		if(Translator.bm_aid) return false;
		var el = $(this).children("img.bm.new");
		if (el.length > 0) {
			el.remove();
		}
	},


	td_c_over: function() {
		if(Translator.notes_aid != 0) return false;
		var aid = this.parentNode.id.substr(1);

		var el = $(this).children("img.notes");
		if (el.length > 0) {
			return false;
		}

		$(this).append("<img src='/i/ic_cmt.gif' width='16' height='15' alt='B' title='Оставить примечание' class='notes new' onclick=\"Translator.notes(" + aid + ", '')\">");
	},
	td_c_out: function() {
		if(Translator.bm_aid) return false;
		var el = $(this).children("img.notes.new");
		if (el.length > 0) {
			el.remove();
		}
	},


	/* Заметки */
	notes_aid: 0,
	notes: function(aid) {
		var w, x, y, h, html;

		if(Translator.notes_aid != 0) {
			Translator.notes_close();
			return false;
		}

		w = $(document).width() - 300;
		x = 150;
		y = $(document).scrollTop() + 100;
		Translator.notes_aid = aid;

		html = "<div id='trNotesWin' class='win'>" +
			"<div class='caption'><div class='x' onclick='return Translator.notes_close(); '>закрыть</div>Примечания</div>" +
			"<div id='trNotesBody'></div>" +
			"</div>";

		$(document.body).append(html);

		$('#trNotesWin').css('left', x).css('width', w).css('top', y).show();
		$('#trNotesBody').html("<iframe src='/book/" + Book.id + "/" + Book.chap_id + "/talk/?aid=" + aid + "'></iframe>");
		$('#trNotesWin').draggable();

		$.hotkeys.add('Esc', {}, Translator.notes_close);
		return false;
	},
	notes_close: function() {
		$('#trNotesWin').hide();
		$('#trNotesBody').html("");
		$.hotkeys.remove("Esc");

		Translator.notes_aid = 0;
	},

	/* Закладки */
	bm_aid: 0,
	bm_set: function(aid, set_title) {
		var bm_title;

		if(Translator.bm_aid == aid) {
			Translator.bm_close();
			return false;
		}
		if(Translator.bm_aid != 0) return false;

		var off = $("#Translator #v" + aid + " td.n").offset();

		if(set_title == "") bm_title = Book.fulltitle + ": " + aid;
		else bm_title = set_title;

		bm_title = bm_title.replace("&#039;", "'");
		var html = "<div class='subwindow' id='Bookmark_set'><form method='post' onsubmit='return Translator.bm_save()'>" +
			"<input type='hidden' name='act' value='bm_save' /><input type='hidden' name='verse' value='" + aid + "' />" +
			"<h5>Поставить закладку:</h5>" +
			"<p><input type='text' name='title' class='f' style='width:300px' /></p>" +
			"<p><input type='submit' value='ok' class='f'/> ";
		if(set_title != "") {
			html += "<input type='button' value='удалить' class='f btn_rm' onclick='Translator.bm_rm()' class='f' /> ";
		}
		html += "<input type='button' value='отмена' onclick='Translator.bm_close()' class='f'/></p>" +
			"</form></div>";

		$(document.body).append(html);
		$("#Bookmark_set").css("left", off.left + 5 + "px").css("top", off.top + 5 + "px").show();
		$("#Bookmark_set [name=title]").val(bm_title);

		Translator.bm_aid = aid;

		return false;
	},
	bm_close: function() {
		if(Translator.bm_aid == 0) return false;
		$("#Bookmark_set").remove();
		$("#Translator td.n img.bm.new").remove();
		Translator.bm_aid = 0;
	},
	bm_save: function() {
		var title = $.trim($("#Bookmark_set [name=title]").val());

		if(title == "") {
			alert("Введите название закладки");
			return false;
		}

		$("#Bookmark_set :submit").attr("disabled", "disabled").val("сохраняется...");
		$.postJSON(
			location.href,
			{ajax: 1, act: "bm_save", verse: Translator.bm_aid, title: title},
			function(data) {
				if(data.status == "error") {
					alert(data.msg);
					return false;
				} else if(data.status == "ok") {
					$("#Bookmark_set :submit").attr("disabled", "").val("ok");
					$("#Translator #v" + Translator.bm_aid + " td.n").html(data.html);
					Translator.bm_close();
				} else {
					alert("Системная ошибка (" + data.status + ")");
					return false;
				}
			}
		);

		return false;
	},
	bm_rm: function() {
		if(!confirm("Удалить эту закладку?")) return false;

		$("#Bookmark_set .btn_rm").attr("disabled", "disabled").val("удаляется...");
		$.postJSON(
			location.href,
			{ajax: 1, act: "bm_rm", verse: Translator.bm_aid},
			function(data) {
				if(data.status == "error") {
					alert(data.msg);
					return false;
				} else if(data.status == "ok") {
					$("#Bookmark_set .btn_rm").attr("disabled", "").val("удалить");
					$("#Translator #v" + Translator.bm_aid + " td.n").html(data.html);
					Translator.bm_close();
				} else {
					alert("Системная ошибка (" + data.status + ")");
					return false;
				}
			}
		);
	},


	/* Добавление версий перевода */
	tr_aid: 0,
	tr: function(aid) {
		var html;

		if(Translator.tr_aid == aid) {
			Translator.tr_cancel();
			return false;
		}

		$("#Translator .nothing_here").hide();

		Translator.tr_cancel();
		Translator.rp_cancel();

		html = "<form method='POST' id='tr_form' onsubmit='return Translator.tr_save()'>" +
			"<input type='hidden' name='act' value='tr_add' />" +
			"<input type='hidden' name='aid' value='" + Translator.tr_aid + "' />" +
			"<textarea name='body' rows='2' cols='60' class='f'></textarea><br />" +
			"<input type='submit' value=' сохранить ' class='f'>" +
			"<input type='button' value=' отмена ' onclick='Translator.tr_cancel()' class='f'>" +
			"</form>";
		$("#Translator #v" + aid + " td.t").prepend(html);

		setTimeout(function(){$("#tr_form [name=body]").focus()}, 0);	// крутой хак. прикиньте, чуваки, без таймаута в IE бьётся разметка, ячейка не перерисовывается.

		$.hotkeys.add('Ctrl+return', {}, Translator.tr_save);

		Translator.tr_aid = aid;

		return false;
	},

	newtr: function(aid) {
		// Прячем язык из списка неиспользованных языков (#lang$aid)
		$("#lang" + aid).hide();

		if($("#Translator #v" + aid).length) {
			Translator.tr(aid);
			return false;
		}

		var lang_title = $("#lang" + aid + " a").text();
		var html = "<tr id='v" + aid + "'><td class='o'>" + lang_title + "</td>";
		html += "<td class='tu'></td>";
		html += "<td class='t'></td></tr>";
		$("#Translator").append(html);

		$("#Translator #v" + aid + " td.tu").hover(Translator.tu_over, Translator.tu_out);

		Translator.tr(aid);
		return false;
	},

	tr_cancel: function() {
		if(Translator.tr_aid == 0) return false;
		$("#Translator .nothing_here").show();
		$("#lang" + Translator.tr_aid).show();
		$("#Translator #v" + Translator.tr_aid + " td.t form").remove();
		$.hotkeys.remove('Ctrl+return', {});
		Translator.tr_aid = 0;
	},

	tr_save: function() {
		if(Translator.tr_aid == 0) return false;

		$.hotkeys.remove('Ctrl+return', {});
		$("#Translator .nothing_here").remove();
		$("#lang" + Translator.tr_aid).remove();

		// Отправляем форму, получаем html, которым заменяем всю ячейку #Book
		var F = {act: 'tr_add', book_id: Book.id, chap_id: Book.chap_id, aid: Translator.tr_aid, body: $("#tr_form [name=body]").val()};
		$.postJSON(location.href, F, function(data, txStatus) {
			if(data.status == "error") return !!alert(data.msg);

			$('#Translator #v' + Translator.tr_aid + " td.t").html(data.html);

			Translator.tr_cancel();
			Ratingize();
		});
		return false;
	},

	/* Редактировать версию перевода */
	rp_aid: 0,
	rp_id: 0,
	rp_html: "",
	rp: function(id, aid) {
		if(Translator.tr_aid != 0) {
			Translator.tr_cancel();
		}
		if(Translator.rp_aid == aid) {
			Translator.rp_cancel();
			return false;
		}
		Translator.tr_cancel();
		Translator.rp_cancel();
		Translator.rp_aid = aid;
		Translator.rp_id = id;

		var $td = $("#Translator #v" + aid + " td.t");
		Translator.rp_html = $td.html();

		var txt = $("#Translator #t" + id + " span").html().replace(/<br>\n?/ig, "\n");;
		var html = "<div id='VarEd'><h2>Отредактировать перевод</h2><form method='POST' onsubmit='return Translator.rp_save()' id='rp_form'><input type='hidden' name='act' value='tr_save' />" +
			"<input type='hidden' name='id' value='" + id + "' />" +
			"<input type='hidden' name='aid' value='" + aid + "' />" +
			"<textarea name='body' rows='3' cols='60' class='f W'></textarea><br />" +
			"<input type='submit' value='сохранить' class='f' />" +
			"<input type='button' value='удалить' class='f' onclick='Translator.rp_rm(" + id + ", " + aid + ")' />" +
			"<input type='button' value='отмена' class='f' onclick='Translator.rp_cancel()' />" +
			"</form></div>";

		$td.html(html);
		$("#rp_form [name=body]").val(txt);
		return false;
	},
	rp_cancel: function() {
		if(Translator.rp_aid == 0) return false;

		$("#Translator #v" + Translator.rp_aid + " td.t").html(Translator.rp_html);

		Translator.rp_aid = 0;
		Translator.rp_id = 0;
		return false;
	},
	rp_save: function() {
		var D = {act: 'tr_save', aid: Translator.rp_aid, id: Translator.rp_id, body: $("#rp_form [name=body]").val()};
		$.postJSON(
			location.href,
			D,
			function(data) {
				if(data.status == "error") return !!alert(data.msg);

				$("#Translator #v" + Translator.rp_aid + " td.t").html(data.html);

				Translator.rp_aid = 0;
				Translator.rp_id = 0;
				Translator.rp_html = "";
			}
		)
		return false;
	},
	rp_rm: function (id, aid) {
		if(!confirm("Стереть этот вариант перевода?")) return false;

		$.postJSON(
			location.href,
			{act: 'tr_rm', aid: Translator.rp_aid, id: Translator.rp_id},
			function(data) {
				if(data.status == "error") return !!alert(data.msg);

				$("#Translator #v" + Translator.rp_aid + " td.t").html(data.html);

				Translator.rp_aid = 0;
				Translator.rp_id = 0;
				Translator.rp_html = "";
			}
		);
		return false;
	},


	/* Редактирование оригинала */
	ed_id: -1,
	ed_lock: false,
	ed_html: "",
	ed: function(aid) {
		var h, html;

		if(Translator.ed_lock) return !!alert("Дождитесь, пока сохранится редактируемый абзац");
		if(Translator.ed_id == aid) return Translator.ed_cancel();
		Translator.ed_cancel();

		if(aid != 0) {
			var $td = $("#Translator #v" + aid + " td.o")
			Translator.ed_html = $td.html().replace(/<br>\n?/ig, "\n");
			if(Book.typ == "S") {
				var t, t1, t1s, t2, t2s;
				t = $("#Translator #v" + aid + " td.i").html();

				t1 = t.substr(0, t.indexOf("<"));
				t1s = t1.substr(t1.indexOf(",") + 1);
				t1 = t1.substr(0, t1.indexOf(","));

				t2 = t.substr(t.indexOf(">") + 1);
				t2s = t2.substr(t2.indexOf(",") + 1);
				t2 = t2.substr(0, t2.indexOf(","));
			}
		} else {
			$("#Translator").append("<tr id='v0'><td class='n'>&mdash;</td><td class='o'></td><td class='tu'><img src='/i/0.gif' width='20' height='1' /></td><td class='t'>Абзац будет добавлен в конец перевода.</td><td class='c'></td></tr>");
			var $td = $("#Translator #v0 td.o");
			Translator.ed_html = "";
		}

		try {
			h = td.height();
			h = Math.ceil(h / 20);
		} catch(e) {
			h = 6;
		}
		if(h < 1) h = 6;

		html = "<form id='ed_form' method='POST' onsubmit='return Translator.ed_save()'>";
		if(Book.typ == "S") {
			html += "<input type='text' name='t1' class='f' value='" + t1 + "' style='width:60px' />,<input type='text' name='t1s' class='f' value='" + t1s + "'  style='width:30px' /> &mdash; " +
					"<input type='text' name='t2' class='f' value='" + t2 + "' style='width:60px' />,<input type='text' name='t2s' class='f' value='" + t2s + "'  style='width:30px' /><br />";
		}
		html += "<textarea name='body' name='body' rows=" + h + " cols='60' class='f W'>" + Translator.ed_html + "</textarea><br />" +
			"<input type='submit' value=' сохранить изменения ' class='f' /> ";
		if(aid != 0) html += "<input type='button' id='ed_rm' value=' удалить ' onclick='Translator.ed_rm()' class='f' /> ";
		html += "<input type='button' value=' отмена ' onclick='Translator.ed_cancel()' class='f' /> ";
		html += "</form>";

		$td.html(html);

		Translator.ed_id = aid;

		$.hotkeys.add('Ctrl+return', {type: 'keyup', propagate: true}, Translator.ed_save);

		setTimeout(function(){$("#ed_form textarea").focus()}, 0);

		return false;
	},
	ed_save: function() {
		$("#ed_form :submit").attr("disabled", "disabled").val("сохраняется...");

		// Тут надо запретить редактировать другие абзацы, пока не пришёл ответ
		Translator.ed_lock = true;
		$.hotkeys.remove('Ctrl+return', {type: 'keyup', propagate: true});

		var POST = {'act': 'orig_save', id: Translator.ed_id, body: $("#ed_form [name=body]").val()};
		if(Book.typ == "S") {
			POST.t1 = $("#ed_form [name=t1]").val();
			POST.t1s = $("#ed_form [name=t1s]").val();
			POST.t2 = $("#ed_form [name=t2]").val();
			POST.t2s = $("#ed_form [name=t2s]").val();
		}

		$.postJSON(
			location.href,
			POST,
			function(data, status) {
				Translator.ed_lock = false;

				if(data.status == "error") return !!alert(data.msg);

				// Заменяем текст абзаца на то, что вернул скрипт
				$('#Translator #v' + Translator.ed_id + " td.o").html(data.body);
				$('#Translator #v' + Translator.ed_id + " td.n").html(data.id);
				if(Book.typ == "S") {
					$("#Translator #v" + Translator.ed_id + " td.i").html(data.timing);
				}
				if(Translator.ed_id == 0) {
					$("#Translator #v0 td.t").html("");
					$("#Translator #v0").attr("id", "v" + data.id);
				}
				Translator.ed_id = -1;
				Translator.ed_html = "";
				$("#Translator #empty_msg").remove();
				Translator.init_hovers();
			}
		);

		return false;
	},
	ed_rm: function() {
		if(Translator.ed_lock) return !!alert("Дождитесь, пока сохранится редактируемый абзац");
		if(!confirm("Вы уверены, что хотите удалить этот абзац?")) return false;

		$("#ed_form #ed_rm").attr("disabled", "disabled").val("удаляется...");
		Translator.ed_lock = 1;

		$.post(
			location.href,
			{act: 'orig_rm', chap: Book.chap_id, verse: Translator.ed_id },
			function(data) {
				Translator.ed_lock = 0;

				if(data.status == "error") return !!alert(data.msg);

				$("#Translator #v" + Translator.ed_id).html("<td colspan='10' class='rm'>удалено</td>");
				$("#Translator td.tu:eq(0)").html("<img src='/i/0.gif' width='20' height='1' alt='' style='display:block'/>");

				Translator.ed_id = -1;
				Translator.ed_html = "";

				return;
			}
		);

		return false;
	},
	ed_cancel: function() {
		if(Translator.ed_id == -1) return false;
		if(Translator.ed_id == 0) {
			$("#Translator #v0").remove();
		} else {
			$('#Translator #v' + Translator.ed_id + " td.o").html(Translator.ed_html.replace(/\n/ig, "<br>"));
		}
		$.hotkeys.remove('Ctrl+return', {type: 'keyup', propagate: true});
		Translator.ed_id = -1;
		Translator.ed_html = "";

		return false;
	},




	/* Словарь */
	Dict: {
		load: function() {
			$("#tab_dict").load("/sys/dict.php?book_id=" + Book.id);
			return false;
		},

		ed_id: -1,
		ed_html: "",
		ed: function(id) {
			var html = "<form method='post' id='dict_ed' onsubmit='return Translator.Dict.ed_save()'>" +
				"<input type='hidden' name='act' value='dict_save' /><input type='hidden' name='id' value='" + id + "' />" +
				"<p>Слово: <input type='text' name='term' class='f t' /></p>" +
				"<p>Перевод: <input type='text' name='descr' class='f t' /></p>" +
				"<p><input type='submit' value='сохранить' class='f' /> " +
				"<input type='button' value='отмена' class='f' onclick='Translator.Dict.ed_cancel()' /></p>" +
				"</form>";

			var el = $("#tab_dict #dict_ed_" + id);
			Translator.Dict.ed_html = el.html();
			Translator.Dict.ed_id = id;
			el.html(html);
			$("#dict_ed [name=term]").focus();

			return false;
		},
		ed_save: function() {
			var term = $.trim($("#dict_ed [name=term]").val());
			var descr = $.trim($("#dict_ed [name=descr]").val());

			if(term == "") return !!alert("Введите слово");
			if(descr == "") return !!alert("Введите перевод");

			$.postJSON(
				location.href,
				{act: "dict_save", id: Translator.Dict.ed_id, term: term, descr: descr, ajax: 1},
				function(data) {
					if(data.status == "error") {
						alert(data.msg);
						return false;
					} else if(data.status == "ok") {
						var dl = $("#tab_dict dl");
						if(dl.length == 0) {
							$("#tab_dict p:eq(0)").remove();
							$("#tab_dict").prepend("<dl></dl>");
						}
						$("#tab_dict dl").append(data.html);
						Translator.Dict.ed_cancel();
					} else {
						alert("Системная ошибка (" + data.status + ")");
						return false;
					}
				}
			);

			return false;
		},
		ed_cancel: function() {
			$("#tab_dict #dict_ed_" + Translator.Dict.ed_id).html(Translator.Dict.ed_html);
			Translator.Dict.ed_id = -1;
			Translator.Dict.ed_html = "";
		},
		ed_rm: function() {

		}
	}
};

$(Translator.init);
