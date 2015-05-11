var T = {
    init: function() {

	    $("#Tr td.n i.icon-bell").attr("title", "Счётчик количества переводов только что был обновлён для этого фрагмента");
		$("#Tr td.o a.e").attr("title", "Редактировать оригинал");
		$("#Tr td.u a.t").attr("title", "Добавить версию перевода");
		$("#Tr td.t a.e").attr("title", "Редактировать эту версию перевода");
	    $("#Tr td.t a.x").attr("title", "Удалить эту версию перевода");

		$("#Tr tr").each(function() {
			var id = this.id.substr(1), $this = $(this), $aord = $this.find("td.n a.ord"), ord = $aord.text();
			if(!id) return true;
			$aord.attr("href", Book.url(Chap.id + "/" + id + "#" + ord)).attr("title", "Ссылка на этот фрагмент");
		});

		var re = /i(phone|pad|pod)/i;
//		if(re.test(navigator.userAgent)) {
//			alert("Привет, айфон! " + $("#Tr td.u").length);

//			$("#Tr td.u a.t").bind("click", T.tr);
//			$("#Tr td.u a.c").bind("click", T.comments.start);
//		} else {
			$("#Tr")
				.delegate("td.o a.e", "click", T.o_edit)
				.delegate("td.u a.c", "click", T.comments.start)
				.delegate("td.u a.t", "click", T.tr)
				.delegate("td.t a.e", "click", T.tr_edit)
				.delegate("td.t a.x", "click", T.tr_rm)
				.delegate(".rater .m", "click", T.rate.minus)
				.delegate(".rater .p", "click", T.rate.plus)
				.delegate(".rate", "click", T.rate.describe);
//		}


		$("#filter-modal ul.options input[type=text]").bind("click focus", function(e) {
			$(this).parents("li").find("input[type=radio]").attr("checked", true);
		});
		$("#filter-modal ul.options input[type=radio]").click(function(e) {
			$(this).parents("li").find("input[type=text]").focus();
		});

		$("#timeshift-modal").find("a.advanced").click(function(e) {
			e.preventDefault();
			var $this = $(this), $modal = $("#timeshift-modal");
			$modal.find("div.advanced").show(400);
			$modal.find("[name=from]").focus();
			$this.parents(".control-label").text("На время:")
		});

		if($.cookie("wow.1")) {
			$(".switchiface a").attr("title", "Переключиться на новый интерфейс перевода");
		} else {
			$(".switchiface a").popover({
				trigger: 'manual',
				title: 'Новый интерфейс',
				content: "Хотите попробовать новый интерфейс перевода? Нажмите на эту кнопку."
			}).popover('show');
			$(".popover").click(function(e) {$(".switchiface a").popover("hide"); });
			$.cookie("wow.1", 1, {expires: 365, path: '/'});
		}
	},

	rate: {
		getId: function(obj) {
			var id = null, res;
			$(obj).parents("div").each(function() {
				if(res = /t(\d+)/.exec(this.id)) {
					id = res[1];
					return false;
				}
			});
			return id;
		},
		vote: function(id, mark) {
			$.ajax({
				url: "/book/" + Book.id + "/" + Chap.id + "/rate_tr",
				dataType: "json",
				type: "POST",
				data: {id: id, mark: mark},
				success: function(data) {
					$("#t" + data.id + " .rate").text(data.rating);
				}
			});
		},
		minus: function(e) {
			e.preventDefault();
			var id = T.rate.getId(this);
			if(!id) return;
			T.rate.vote(id, -1);
		},
		plus: function(e) {
			e.preventDefault();
			var id = T.rate.getId(this);
			if(!id) return;
			T.rate.vote(id, 1);
		},
		describe: function(e) {
			e.preventDefault();
			var id = T.rate.getId(this);
			var el = this;
			$.get("/book/" + Book.id + "/" + Chap.id + "/rating_describe", {id: id}, function(html) {
				$("#rating-descr").html(html).modal();
			})
		}
	},

	setStats: function(n_vars, d_vars, n_verses) {
		var p = n_verses == 0 ? 0 : (Math.floor(d_vars / n_verses * 1000) / 10);
		if(p) {
			$("#progress-info").show();
			$("#progress-info .bar").css("width", p + "%");
			$("#progress-info .text a")
				.text("Готово: " + p + "%, скачать")
				.attr("title", "Скачать результат. \nФрагментов: " + n_verses + ", вариантов: " + n_vars + ", разных: " + d_vars)
		} else {
			$("#progress-info").hide();
		}

	},

	chaptersLoaded: false,
	loadChapters: function() {
		if(T.chaptersLoaded) return false;
		$.ajax({
			"url": Book.url("chapters"),
			dataType: "json",
			success: function(data) {
				var html = "";
				html += "<li class='divider'></li>";
				for(var i in data) {
					html += "<li><a href='" + Book.url(data[i].id) + "'>" + data[i].title + "</a></li>";
				}
				$("#chapter-list").append(html);
				T.chaptersLoaded = true;
			}
		});
		return true;
	},

    editing_mode: "",
    editing_id: null,
    editing_html: "",
    editing_start: function(mode, id) {
        if(T.editing_mode != "") {
            if(T.editing_mode == mode && T.editing_id == id) {
                T.editing_stop();
                return true;
            } else {
                T.editing_stop();
            }
        }

        T.editing_mode = mode;
        T.editing_id = id;

        return false;
    },
    editing_stop: function() {
        if(T.editing_mode == "tradd") {
            $("#o" + T.editing_id).find(".tr-editor").remove();
        } else if(T.editing_mode == "tredit") {
            $("#t" + T.editing_id).html(T.editing_html);
		} else if(T.editing_mode == "origedit") {
            $("#o" + T.editing_id).find("td.o").html(T.editing_html);
        }
        T.editing_mode = "";
        T.editing_id = null;
        T.editing_html = "";
    },


	tr_next: null,
	tr_origlen: 0,
	// jquery.hotkeys, горите в аду!
	trKey: function(e) {
		if(e.ctrlKey && e.shiftKey && e.which == 13) {
			T.tr_next = null;
			$("#form-tr :submit").click();
		} else if(e.ctrlKey && e.which == 13) {
			var $tr = $("#o" + T.editing_id);
			T.tr_next = $tr.next("tr").find("td.u a.t");
			if($.trim($("#form-tr [name=Translation\\[body\\]]").val()) == "") {
				T.editing_stop();
				if(T.tr_next && T.tr_next.length) T.tr_next.click();
			} else {
				$("#form-tr :submit").click();
			}
		} else if(e.which == 27) {
			$("#form-tr .cancel").click();
		}
	},
	trEditKey: function(e) {
		if(e.ctrlKey && e.which == 13) {
			$("#form-tr :submit").click();
		} else if(e.which == 27) {
			$("#form-tr .cancel").click();
		}
	},
	tr_ccnt: {
		origLen: 0,
		init: function(orig_id) {
			var $o = $("#o" + orig_id + " td.o");
			T.tr_ccnt.origLen = $("span.b", $o).text().replace(/\n/g, "").length;
			$("#tr-ccnt b.o").text(T.tr_ccnt.origLen);
		},
		update: function() {
			var t = $(this).val().replace(/\n/g, "");
			$("#tr-ccnt b.t").text(t.length);
		},
		close: function() {}
	},
    tr: function(e) {
        e.preventDefault();

		T.tr_next = null;

        var $tr = $(this).parents("tr");
        var orig_id = $tr.attr("id").substr(1);

        if(T.editing_start("tradd", orig_id)) return;

		var html =
			"<div class='tr-editor'><form id='form-tr' method='post' action='/book/" + Book.id + "/" + Chap.id + "/" + orig_id + "/translate'>" +
			"<textarea name='Translation[body]'></textarea>" +
			"<button type='submit' class='btn btn-mini btn-primary' title='Ctrl+Enter &ndash; сохранить и перейти к следующему\nCtrl+Shift+Enter &ndash; сохранить.'>Добавить</button> " +
			"<button type='button' class='btn btn-mini cancel' onclick='T.editing_stop()'>Отмена</button> " +
			"<span id='tr-ccnt' title='Длина в символах'>Оригинал: <b class='o'>?</b> / Перевод: <b class='t'>?</b></span>" +
			"</form></div>";

        $tr.children("td.t").append(html);
		T.tr_ccnt.init(orig_id);
		$ta = $("#form-tr textarea");
		$ta.elastic().keydown(T.trKey).bind("keyup change blur click", T.tr_ccnt.update).focus().keyup();
		setInterval(function() {$ta.keyup()}, 250);

		$("#form-tr").ajaxForm({
			dataType: "json",
	        data: {ajax: 1},
			beforeSubmit: function() {
			    $("#form-tr :submit").attr("disabled", true);
			},
            success: function(data) {
                if(data.error) {
				    $("#form-tr :submit").attr("disabled", false);
				    alert(data.error);
				    return false;
				}

				// если не открыли другой редактор, то закрываем его
				if(T.editing_mode == "tradd" && T.editing_id == orig_id) T.editing_stop();

				$tr.children("td.t").html(data.text);

                T.setStats(data.n_vars, data.d_vars, data.n_verses);

				if(T.tr_next && T.tr_next.length) T.tr_next.click();
            }
        });
    },
	tr_edit: function(e) {
        e.preventDefault();

        var $tr = $(this).parents("tr");
	    var orig_id = $tr.attr("id").substr(1);

        var $div = $(this).closest("div");
        var tr_id = $div.attr("id").substr(1);

        T.editing_start("tredit", tr_id);

        var tr_text = $div.find("span.b").text();

        var html =
			"<div class='tr-editor'><form id='form-tr' method='post' action='/book/" + Book.id + "/" + Chap.id + "/" + orig_id + "/translate?tr_id=" + tr_id + "'>" +
			"<textarea name='Translation[body]'></textarea>" +
			"<button type='submit' class='btn btn-mini btn-primary'>Сохранить</button> " +
			"<button type='button' class='btn btn-mini cancel' onclick='T.editing_stop()'>Отмена</button> ";
		if(Book.membership.status != 2) html += "<small class='help-inline'>рейтинг будет обнулён</small>";
		html +=
			"<span id='tr-ccnt' title='Длина в символах'>Оригинал: <b class='o'>?</b> / Перевод: <b class='t'>?</b></span>" +
			"</form></div>";

        T.editing_html = $div.html();

        $div.html(html);
	    $("#form-tr [name=Translation\\[body\\]]").val(tr_text);
		T.tr_ccnt.init(orig_id);
		$ta = $("#form-tr textarea");
		$ta.elastic().keydown(T.trEditKey).bind("keyup change blur click", T.tr_ccnt.update).focus().keyup();
		setInterval(function() {$ta.keyup()}, 250);

		$("#form-tr").ajaxForm({
			dataType: "json",
			data: {ajax: 1},
			beforeSubmit: function() {
				$("#form-tr :submit").attr("disabled", true);
			},
			success: function(data) {
				if(data.error) {
					$("#form-tr :submit").attr("disabled", false);
					alert(data.error);
					return false;
				}

				// если не открыли другой редактор, то закрываем его
				if(T.editing_mode == "tredit" && T.editing_id == tr_id) T.editing_stop();
				$tr.children("td.t").html(data.text);

				T.setStats(data.n_vars, data.d_vars, data.n_verses);
			}
		})
    },
	tr_rm: function(e) {
		e.preventDefault();

		var $tr = $(this).parents("tr");
		var orig_id = $tr.attr("id").substr(1);

		var $div = $(this).closest("div");
		var tr_id = $div.attr("id").substr(1);

		$div.addClass("deleting");

		if(!confirm("Вы уверены, что хотите удалить этот вариант перевода? Отменить эту процедуру нельзя.")) {
			$div.removeClass("deleting");
			return;
		}

		$.ajax({
			url: "/book/" + Book.id + "/" + Chap.id + "/" + orig_id + "/tr_rm",
			dataType: "json",
			type: "POST",
			data: {tr_id: tr_id},
			success: function(data) {
				$div.removeClass("deleting");
				if(data.error) {
					alert(data.error);
					return false;
				} else if(data.status == "ok") {
					$div.remove();
					T.setStats(data.n_vars, data.d_vars, data.n_verses);
				} else {
					alert("Произошла какая-то ошибка, удалить перевод не удалось. Попробуйте обновить страницу.");
					return false;
				}
			},
			error: function(xhr) {
				$div.removeClass("deleting");
			}
		});
	},

    o_edit: function(e) {
        e.preventDefault();

        var $tr = $(this).parents("tr");
        var $td = $tr.children("td.o");
        var orig_id = $tr.attr("id").substr(1);

        T.editing_start("origedit", orig_id);

        T.editing_html = $td.html();

        $td.load("/book/" + Book.id + "/" + Chap.id + "/" + orig_id + "/edit?ajax=1", {}, function() {
	        $("#form-orig").ajaxForm({
		        dataType: "json",
		        beforeSubmit: function() {
			        $("#form-orig :submit").attr("disabled", true);
		        },
		        success: function(data) {
			        if(data.error) {
				        $("#form-orig :submit").attr("disabled", false);
				        alert(data.error);
				        return false;
			        }

			        // если не открыли другой редактор, то закрываем его
			        if(T.editing_mode == "origedit" && T.editing_id == orig_id) T.editing_stop();

			        $td.html(data.body + " <a href='#' class='e'><i class='icon-edit'></i></a>");
			        $tr.find("td.n .t1").text(data.t1);
			        $tr.find("td.n .t2").text(data.t2);
			        $tr.find("td.n .ord").text(data.ord);
		        },
		        data: {ajax: 1}
	        });

			$("#form-orig").keydown(function(e) {
				if(e.ctrlKey && e.which == 13) $("#form-orig :submit").click();
				else if(e.which == 27) $("#form-orig .cancel").click();
			});
        });
    },
	o_rm: function(e) {
		e.preventDefault();

		var $tr = $(this).parents("tr");
		var $td = $tr.children("td.o");
		var orig_id = $tr.attr("id").substr(1);

		$td.addClass("deleting");

		if(!confirm("Вы абсолютно уверены, что хотите удалить этот фрагмент оригинала вместе со всеми его переводами и комментариями? Отменить эту процедуру нельзя.")) {
			$td.removeClass("deleting");
			return false;
		}

		$.ajax({
			url: "/book/" + Book.id + "/" + Chap.id + "/" + orig_id + "/remove",
			dataType: "json",
			type: "POST",
			success: function(data) {
				$td.removeClass("deleting");
				if(data.error) {
					alert(data.error);
					return false;
				} else if(data.status == "ok") {
					$tr.remove();
					T.setStats(data.n_vars, data.d_vars, data.n_verses);
				} else {
					alert("Произошла какая-то ошибка, удалить перевод не удалось. Попробуйте обновить страницу.");
					return false;
				}
			},
			error: function(xhr) {
				$td.removeClass("deleting");
			}
		});

	},
	o_add: function() {

	},

	comments: {
		orig_id: null,
		start: function(e) {
			e.preventDefault();

			var $tr = $(this).parents("tr");
			var orig_id = $tr.attr("id").substr(1);

			if($tr.next("tr").hasClass("cmt")) {
				$tr.next("tr").remove();
				return false;
			}

			$tr.after("<tr id='c" + orig_id + "' class='cmt'><td class='n'></td><td colspan='3' class='b loading'></td></tr>");
			var $comments_td = $("#c" + orig_id + " td.b");

			$comments_td.load("/book/" + Book.id + "/" + Chap.id + "/" + orig_id + "/comments?ajax=1", {}, function() {
				$comments_td.removeClass("loading");
				$comments = $("#c" + orig_id + " .comments");
				$comments.ff_comments();
				$comments.find(".stop").click(function() { T.comments.stop(orig_id); });
			});
		},
		stop: function(orig_id) {
			$("#c" + orig_id).remove();
		}
	},

	mytalks: function(orig_id, btn) {
		$(btn).val("...").attr("disabled", true);
		$.ajax({
			type: "POST",
			url: "/my/comments/add",
			data: {ajax: 1, orig_id: orig_id},
			dataType: "json",
			success: function(data) {
				if(data.error) {
					alert(data.error);
					return false;
				}
				$(btn).remove();
			}
		});
	},

	bm: {
		set: function(orig_id) {
//			var title = $("#Tr #o" + orig_id + " td.o").text();
//			if(title.length > 50) title = title.substr(0, 50);
//			title = Book.s_title + ", " + Chap.title + ": " + title;
			BM.set(Book.id, orig_id, function(data) {
				$tdn = $("#Tr #o" + data.orig_id + " td.n");
				if(data.status == "rm") {
					$("a.b", $tdn).removeClass("set").html("<i class='icon-star-empty'></i>").attr("title", "Поставить закладку");
				} else {
					$("a.b", $tdn).addClass("set").html("<i class='icon-star'></i>").attr("title", "Закладка" + (data.note != "" ? (": \"" + data.note + "\"") : ""));
				}
			});
			return false;
		},
		rm: function(orig_id) {
			BM.rm(bm_id, function() {
				$tdn = $("#Tr #o" + orig_id + " td.n");
				$("a.bs", $tdn).remove();
				$tdn.append("<a href='#' onclick=\"return T.bm.set(" + orig_id + ")\" class='b' title='Поставить закладку'><i class='icon-star-empty'></i>");
			});
			return false;
		}
	},

	dict: {
		init_done: false,
		show: function() {
			var $dict = $("#dict-dialog");
			var pos = "right", w = 300, h = 300, a;

			if($.cookie("dict_p")) {
				a = $.cookie("dict_p").split(".");
				pos = []; pos[0] = parseInt(a[0]); pos[1] = parseInt(a[1]);
			}

			if($.cookie("dict_s")) {
				a = $.cookie("dict_s").split(".");
				w = parseInt(a[0]); h = parseInt(a[1]);
			}

			if(!T.dict.init_done) {
				$dict.dialog({
					autoOpen: false,
					closeText: "Закрыть",
					position: pos, // "right",
					height: h,
					width: w,
					resizeStop: function(event, ui) {
						var x = (Math.floor(ui.position.left) - $(window).scrollLeft()),
							y = (Math.floor(ui.position.top) - $(window).scrollTop()),
							w = ui.size.width,
							h = ui.size.height;

						var position = [x, y];
						$(event.target).parent().css('position', 'fixed');
						$dict.dialog('option', 'position', position);

						$.cookie("dict_s", w + "." + h);
					},
					dragStop: function(event, ui) {
						var x = (Math.floor(ui.position.left) - $(window).scrollLeft()),
							y = (Math.floor(ui.position.top));
						$.cookie("dict_p", x + "." + y);
					}
				});

				$dict.load(Book.url("dict"), {ajax: 1}, T.dict.dialog_init);

				T.dict.init_done = true;
			}
			if($dict.dialog("isOpen")) $dict.dialog("close");
			else $dict.parent().css({position:"fixed"}).end().dialog("open");

			return false;
		}
	},

	timeshift: function() {

	}
};

$(T.init);