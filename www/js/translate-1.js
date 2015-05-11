$(function() {
	var $table = $("#Tr");

	function getOrigLength(orig_id) {
		var $e = $("#o" + orig_id + " td.o .text"), t;
		if($e.length == 0) {
			t =  $("#o" + orig_id + " textarea").val();
		} else {
			t = $e.text();
		}
		return t.replace(/\n/g, "").length;
	}

	function fixWrapper($tr) {
		var $td = $tr.find("td.o");
		$td.children("div").css("min-height", 0).css("min-height", $td.height());
	}

	function _getOrigId(el) {
		return $(el).parents("tr").attr("id").substr(1);
	}

	function bmAdd(e) {
		e.preventDefault();
		var orig_id = _getOrigId(this);

		BM.set(Book.id, orig_id, function(data) {
			var $a = $table.find("#o" + data.orig_id + " td.b a");
			if(data.status == "rm") {
				$a.html("<i class='icon-star-empty'></i>").attr("title", "Поставить закладку");
			} else {
				$a.html("<i class='icon-star'></i>").attr("title", "Закладка" + (data.note != "" ? (": \"" + data.note + "\"") : ""));
			}
		});
		return false;
	}

	function toggleOrigEdit(e) {
		e.preventDefault();
		$table.toggleClass("translator-oe-hide");
		User.ini_set("t.oe_hide", $table.hasClass("translator-oe-hide") ? 1 : 0);
	}

	function toggleTrEdit(e) {
		e.preventDefault();
		var $tr = $("#Tr");
		$tr.toggleClass("translator-te-hide");
		User.ini_set("t.te_hide", $tr.hasClass("translator-te-hide") ? 1 : 0);
	}

	function commentsToggle(e) {
		e.preventDefault();

		var $a = $(this), $i = $a.find("i"),
			$tr = $a.parents("tr"),
			$trCmt,
			orig_id = $tr.attr("id").substr(1),
			scrollTop = $(window).scrollTop();

		function close(e) {
			var nComments = $trCmt.find(".thread .comment").not(".thread-form .comment").not(".deleted").length;

			if(nComments) $a.html("<i class='icon-comment'></i> " + nComments);
			else $a.html("<i class='i icon-comment-empty'></i>");

			$tr.removeClass("commented");
			$trCmt.find(".comments").ff_comments("destroy");
			$trCmt.remove();

			if($(this).is("button")) $("body").animate({scrollTop: scrollTop}, 200);
		}

		if($tr.hasClass("commented")) {
			$trCmt = $tr.next("tr.comments-row");
			close();
		} else {
			$trCmt = $("<tr class='comments-row loading'><td colspan='5'>Минутку...</td></tr>")

			$tr.addClass("commented");
			$i.data("old-class", $i.attr("class")).attr("class", "icon-remove-sign");
			$trCmt.insertAfter($tr);

			$trCmt.find("td").load("/book/" + Book.id + "/" + Chap.id + "/" + orig_id + "/comments?ajax=1", {}, function() {
				$trCmt.removeClass("loading");
				$trCmt.find(".comments").ff_comments();
				$trCmt.find(".mytalks").click(function(e) {
					var $btn = $(this);
					$btn.val("Минутку...").attr("disabled", true).addClass("disabled");
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
							$btn.remove();
						}
					});
				});
				$trCmt.find(".thread-form button.stop").click(close);
			});
		}
	}

	function hlUser(e) {
		var res;
		if(!(res = /\bu(\d+)\b/.exec($(this).parents("div").attr("class")) )) return;
		$table.find("td.t .u" + res[1]).toggleClass("highlighted");
	}

	/* Toolbar */
	var tb = {
		init: function() {
			var $toolbar = $("#tb-main"), $sidebar = $("#tr-sidebar"),
				$filter = $("#tb-filter"), $filterBtn = $toolbar.find(".tb-filter"),
				trTop, trHeight,
				tbTop = $toolbar.offset().top, tbHeight = $toolbar.outerHeight(),
				// это будет меняться при изменении размеров окна
				winH = $(window).height();

			function scrollHandler() {
				var st = $(window).scrollTop(), sbTop, sbBottom;
				// @todo: optimize: пересчитывать trTop нужно только при изменении плавающего состояния тулбара
				if (st > tbTop) {
					$toolbar.addClass("fixed");
					trTop = $table.offset().top;
					sbTop = tbHeight;
				} else {
					$toolbar.removeClass("fixed");
					trTop = $table.offset().top;
					sbTop = trTop - st;
				}
				$sidebar.css("top", sbTop);
				trHeight = $table.outerHeight();

				if(st + winH > trTop + trHeight) {
					sbBottom = st + winH - trTop - trHeight;
				} else {
					sbBottom = 5;
				}
				$sidebar.css("bottom", sbBottom);
				$("#dict-body").mCustomScrollbar("update");
			}

			function filterHide(e) {
				$filterBtn.removeClass("active");
				$filter.hide();
				$("html").unbind("click", filterHide).unbind("keydown", filterEsc);
			}

			function filterEsc(e) {
				if(e.which == 27) filterHide();
			}

			function switchWide(e) {
				e.preventDefault();

				var $c = $(".container-fluid");
				console.log($c);
				if($c.length) $c.removeClass("container-fluid").addClass("container");
				else $(".container").removeClass("container").addClass("container-fluid");
			}

			if($table.length) $(window).scroll(scrollHandler).scroll();

			$toolbar.find(".tb-index a.dropdown-toggle").click(tb.loadChapters);

			$filterBtn.click(function(e) {
				e.preventDefault();
				e.stopPropagation();

				if($filterBtn.hasClass("active")) {
					filterHide();
				} else {
					$filterBtn.addClass("active");
					$filter.show();

					setTimeout(function() {
						$filter.click(function(e) { e.stopPropagation(); });
						$("html").click(filterHide).bind("keydown", filterEsc);
					}, 100);
				}
			});

			$filter.find("input[type=radio]").click(function() {
				$(this).siblings("input[type=text]").focus();
			});
			$filter.find("input[type=text]").bind("click focus", function() {
				$(this).siblings("input[type=radio]").attr("checked", true);
			});
			if(User.notGuest) $filter.find("input[name=show_user]").bind("keydown", function(e) {
					if(e.which == 73 && e.ctrlKey) $(this).val(User.login);
				});

			$toolbar.find(".tb-wide").click(switchWide);

			var $chatBtn = $toolbar.find(".tb-chat");
			Chat.onStart = function() { $chatBtn.addClass("active"); };
			Chat.onStop = function() { $chatBtn.removeClass("active"); };
			$chatBtn.click(Chat.toggle);
			if(Chat.on) $chatBtn.addClass("active");


			tb.stats();
		},

		chaptersLoaded: false,
		loadChapters: function() {
			if(tb.chaptersLoaded) return true;
			$.ajax({
				url: Book.url("chapters"),
				dataType: "json",
				success: function(data) {
					var html = "<li class='divider'></li>";
					for(var i in data) {
						html += "<li><a href='" + Book.url(data[i].id) + "'>" + data[i].title + "</a></li>";
					}
					$("#tb-chapter-list").append(html);
					tb.chaptersLoaded = true;
				}
			});
			return true;
		},

		stats: function(n_vars, d_vars, n_verses) {
			if(typeof n_vars !== "undefined") Chap.n_vars = n_vars;
			if(typeof d_vars !== "undefined") Chap.d_vars = d_vars;
			if(typeof n_verses !== "undefined") Chap.n_verses = n_verses;

			var p = Chap.n_verses ? (Math.floor(Chap.d_vars / Chap.n_verses * 1000) / 10) : 0;
			var html = "<div class='progress progress-striped progress-success'>" +
				"<div class='bar' style='width: " + p + "%;'></div>" +
				"<div class='text' title='Фрагментов: " + Chap.n_verses + ", вариантов: " + Chap.n_vars + ", разных: " + Chap.d_vars + "'>" + p + "%";
			if(Chap.n_vars > 0) html += " &middot; <a href='" + Book.url(Chap.id + "/ready") + "'>Скачать</a>";
			html += "</div></div>";

			$("#tb-main .tb-progress").html(html);
		}
	};

	/* Словарь */
	var dict = {
		loaded: false,
		shown: false,
		init: function() {
			$("#tb-main .tb-dict").click(function(e) {
				e.preventDefault();
				if(dict.shown) dict.hide();
				else dict.show();
			});

//			$(window).scroll(dict.createPager);
//			$("#dict-body").scroll(dict.createPager);
			$("#dict-search input").keyup(dict.search);
			$("#dict-search a.b").click(dict.hide);
			if(User.ini_get("t.dict") == 1) dict.show();
		},
		show: function() {
			var $body = $("body"), $this = $(this);
			$body.addClass("sidebar-on");
			$("#tb-main .tb-dict").addClass("active");
			dict.shown = true;
			if(!dict.loaded) dict.load();
			User.ini_set("t.dict", 1);
		},
		hide: function(e) {
			if(e) e.preventDefault();
			var $body = $("body"), $this = $(this);
			$body.removeClass("sidebar-on");
			$("#tb-main .tb-dict").removeClass("active");
			dict.shown = false;
			User.ini_set("t.dict", 0);
		},
		createPager: function() {
			var $body = $("#dict-body"),
				h = $body.height(), ch = $("#dict-body-content").height(),
				nPages = Math.ceil(ch / h),
				page = Math.round($body.scrollTop() / $body.height()),
				i, html = "";

			if(nPages > 20) return;

			for(i = 0; i < nPages; i++) {
				html += "<a href='#' data-page='" + i + "' title='Страница " + (i + 1) + "'>&bull;</a>";
			}
			$("#dict-pages").html(html).find("a").eq(page).addClass("active");
		},
		goPage: function(e) {
			e.preventDefault();
			var $this = $(this), $body = $("#dict-body"), page = $this.data("page");
			$body.scrollTop($body.height() * page);
			$("#dict-pages").find("a").removeClass("active");
			$this.addClass("active");
		},
		load: function() {
			$("#dict-body-content").load(Book.url("dict") + "?ajax=1", function() {
//				dict.createPager();
				$("#dict-body").mCustomScrollbar("update");

				$("#dict-pages")
					.delegate("a", "click", dict.goPage);

				$("#dict-tools")
					.delegate("a.add", "click", dict.edit);

				$("#dict-body-content")
					.delegate("a.e", "click", dict.edit)
					.delegate("a.x", "click", dict.rm);

				setTimeout(function() {
					$("#dict-body").mCustomScrollbar({
						scrollInertia: 0,
						mouseWheel: true,
						scrollButtons: { enable: true }
					});
				}, 200);

				dict.loaded = true;
			});
		},
		search: function(e) {
			var $this = $(this), srch = $this.val().toLowerCase();
			if(srch == "") {
				$("#dict-body-content > div").show();
			} else {
				$("#dict-body-content .o").each(function() {
					var $this = $(this);
					if($this.text().substr(0, srch.length).toLowerCase() == srch) {
						$this.parent("div").show();
					} else {
						$this.parent("div").hide();
					}
				});
			}
		},
		editId: null,
		editHtml: "",
		edit: function(e) {
			e.preventDefault();
			var $this = $(this), $div, id, html = "";

			if(dict.edit !== null) {
				if(dict.edit == id) {
					dict.cancel(); return;
				}
				dict.cancel();
			}

			if($this.hasClass("add")) {
				id = 0;
				$div = $("#dict-add").show(50);
			} else {
				$div = $this.parent("div");
				id = $div.attr("rel");
			}

			var orig = $div.find(".o").text(), tr = $div.find(".t").text();

			html = "<form id='dict-edit' method='post' action='" + Book.url("dict_edit") + "'>" +
				"<input type='hidden' name='id' value='" + id + "' />" +
				"<input type='text' name='term' placeholder='Оригинал' />" +
				"<input type='text' name='descr' placeholder='Перевод' />" +
				"<button type='submit' class='btn btn-mini btn-primary'>Сохранить</button> " +
				"<button type='button' class='btn btn-mini cancel'>Отмена</button>" +
				"</form>";

			dict.editId = id;
			dict.editHtml = $div.html();
			$div.html(html);

			var $form = $("#dict-edit");
			$form.find("[name=term]").val(orig);
			$form.find("[name=descr]").val(tr);
			$form.find("[name=" + (id ? "descr" : "term") + "]").focus();
			$form.find(".cancel").click(dict.cancel);
			$form.ajaxForm({
				data: {ajax: 1},
				dataType: "json",
				success: function(data) {
					if(data.error) {
						alert(data.error);
						dict.cancel();
						return;
					}
					if(!id) {
						$div = $("<div />").attr("rel", data.id).appendTo("#dict-body-content");
					}
					dict.cancel();
					$div.html(
						"<a href='#' class='e'><i class='i icon-edit'></i></a> " +
						"<a href='#' class='x'><i class='i icon-remove'></i></a> " +
						"<span class='o'>" + data.term + "</span> " +
						"<span class='t'>" + data.descr + "</span> "
					);
					$("#dict-body").mCustomScrollbar("update");
				}
			});
		},
		cancel: function(e) {
			if(e) e.preventDefault();

			if(dict.editId == 0) {
				$("#dict-add").hide(50);
			} else {
				$("#dict-body-content [rel=" + dict.editId + "]").html(dict.editHtml);
//				dict.createPager();
				$("#dict-body").mCustomScrollbar("update");
			}

			dict.editId = null;
			dict.editHtml = "";
		},
		rm: function(e) {
			e.preventDefault();
			if(!confirm("Вы это серьёзно? Вот так взять, и удалить это слово из словаря?")) return;
			var id = $(this).parents("div").attr("rel");

			$.ajax({
				url: Book.url("dict_rm"),
				type: "POST",
				data: {ajax: 1, id: id},
				dataType: "json",
				success: function(data) {
					if(data.error) return !!alert(data.error);
					$("#dict-body-content [rel='" + data.id + "']").remove();
//					dict.createPager();
					$("#dict-body").mCustomScrollbar("update");
				}
			});
		},

		init_done: false,
		showOldDialog: function() {
			var $dict = $("#dict-dialog");
			var pos = "right", w = 300, h = 300, a;

			if($.cookie("dict_p")) {
				a = $.cookie("dict_p").split(":");
				pos = []; pos[0] = parseInt(a[0]); pos[1] = parseInt(a[1]);
			}

			if($.cookie("dict_s")) {
				a = $.cookie("dict_s").split(":");
				w = parseInt(a[0]); h = parseInt(a[1]);
			}

			if(!dict.init_done) {
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

						$.cookie("dict_s", w + ":" + h);
					},
					dragStop: function(event, ui) {
						var x = (Math.floor(ui.position.left) - $(window).scrollLeft()),
							y = (Math.floor(ui.position.top));
						$.cookie("dict_p", x + ":" + y);
					}
				});

				$dict.load(Book.url("dict"), {ajax: 1});

				dict.init_done = true;
			}
			if($dict.dialog("isOpen")) $dict.dialog("close");
			else $dict.parent().css({position:"fixed"}).end().dialog("open");

			return false;
		}
	};

	var tr = {
		id: null,
		orig_id: null,
		saveHtml: "",
		next: null,
		timer: null,

		cancel: function() {
			if(tr.id == 0) $("#t0").remove();
			else $("#t" + tr.id).removeClass("editing").html(tr.saveHtml);
			clearInterval(tr.timer);
			fixWrapper($("#o" + tr.orig_id));
			tr.id = null;
			tr.orig_id = null;
			tr.saveHtml = "";
		},
		edit: function(e) {
			e.preventDefault();

			var html, $div, $form, $tr = $(this).parents("tr"),
				orig_id = $tr.attr("id").substr(1),
				id;

			if($(this).hasClass("edit")) {
				id = $(this).parents("div[id^=t]").attr("id").substr(1);
			} else {
				id = 0;
			}

			if(tr.id !== null) {
				if((id == 0 && tr.id == 0 && tr.orig_id == orig_id) || (id != 0 && tr.id == id)) { tr.cancel(); return; }
				tr.cancel();
			}

			tr.id = id;
			tr.orig_id = orig_id;

			if(id == 0) {
				$div = $("<div id='t0' />").appendTo("#o" + orig_id + " td.t");
			} else {
				$div = $("#t" + id);
				tr.saveHtml = $div.html();
			}

			tr.next = null;

			html =
				"<form method='post' action='" + ("/book/" + Book.id + "/" + Chap.id + "/" + orig_id + "/translate" + (id ? ("?tr_id=" + id) : "")) + "'>" +
				"<div class='text'>" +
				"<textarea name='Translation[body]'></textarea>" +
				"</div><div class='info'>" +
				"<button type='submit' class='btn btn-mini btn-primary'>" + (id ? "Сохранить" : "Добавить") + "</button> " +
				"<button type='button' class='btn btn-mini cancel'>Отмена</button> ";
			if(id != 0 && Book.membership.status != 2) html += "<small class='help-inline'>Рейтинг будет обнулён</small>";
			html +=
				"<div id='tr-ccnt'>Оригинал/перевод: <b class='o'>" + getOrigLength(orig_id) + "</b>/<b class='t'>?</b></div>" +
				"</div>" +
				"</form>";

			$form = $(html);

			if(id != 0) $form.find("textarea").val($("#t" + id + " .text").text());
			else if(User.ini_get("t.copy") == 1) $form.find("textarea").val($tr.find("td.o p.text").text());

			$form.find("button.cancel").click(tr.cancel);

			$form.ajaxForm({
				dataType: "json",
				data: {ajax: 1},
				beforeSubmit: function() {
					$div.addClass("loading");
					$form.find(":submit").attr("disabled", true);
				},
				success: function(data) {
					$div.removeClass("loading");
					if(data.error) {
						$form.find(":submit").attr("disabled", false);
						alert(data.error);
						return;
					}

					$("#o" + orig_id + " td.t").html(data.text);

					tb.stats(data.n_vars, data.d_vars, data.n_verses);
					fixWrapper($tr);

					tr.id = 0;
					tr.orig_id = 0;

					if(tr.next && tr.next.length) tr.next.click();
				},
				error: function(xhr) {
					$div.removeClass("loading");
					$form.find(":submit").attr("disabled", false);
				}
			});

			$div.addClass("editing").html($form);

			if(window.opera && window.opera.buildNumber) {
				setTimeout(function() {
					var $w = $(window), st = $w.scrollTop();
					if($form.offset().top - st < 50) $w.scrollTop(st - 100);
				}, 100);
			}

			var $ta = $form.find("textarea");
			$ta	.elastic()
				.bind("keydown", tr.keydown)
				.bind("keyup change blur click", tr.ccnt.update)
				.keyup()
				.focus();
			tr.timer = setInterval(function() { $ta.keyup() }, 333);

			fixWrapper($tr);
		},
		rm: function(e) {
			e.preventDefault();

			var $tr = $(this).parents("tr");
			var orig_id = $tr.attr("id").substr(1);

			var $div = $(this).parents("div[id^=t]");
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
						$div.removeClass("deleting");
						return false;
					} else if(data.status == "ok") {
						$div.remove();
						tb.stats(data.n_vars, data.d_vars, data.n_verses);
						fixWrapper($tr);
					} else {
						alert("Произошла какая-то ошибка, удалить перевод не удалось. Попробуйте обновить страницу.");
						$div.removeClass("deleting");
						return false;
					}
				},
				error: function(xhr) {
					$div.removeClass("deleting");
				}
			});
		},
		keydown: function(e) {
			// e.shiftKey
			if(e.ctrlKey && e.which == 13) {
				var $tr = $("#o" + tr.orig_id);

				if(tr.id != 0 || e.shiftKey) {
					tr.next = null;
					$tr.find("form :submit").click();
				} else {
					tr.next = $tr.next("tr").find("td.u a");

					if($.trim($tr.find("textarea[name=Translation\\[body\\]]").val()) == "") {
						tr.cancel();
						if(tr.next && tr.next.length) tr.next.click();
					} else {
						$tr.find("form :submit").click();
					}
				}
			} else if(e.which == 27) {
				tr.cancel();
			}
		},
		ccnt: {
			update: function() {
				var t = $(this).val().replace(/\n/g, "");
				$("#tr-ccnt b.t").text(t.length);
			}
		}
	};

	var orig = {
		id: null,
		saveHtml: "",
		edit: function(e) {
			e.preventDefault();

			var id, after_id, $td, url, $ptr = $(this).parents("tr");

			var $tr = $("<tr id='o0'><td class='b'></td><td class='o'><div></div></td><td class='u'></td><td class='t'></td><td class='c'></td></tr>");
			if($(this).hasClass("add")) {
				id = 0;
				after_id = $ptr.attr("id").substr(1);
				$tr.insertAfter($ptr);
			} else if($(this).hasClass("create")) {
				id = 0;
				after_id = 0;
				$tr.appendTo($table.find("tbody"));
			} else {
				id = $ptr.attr("id").substr(1);
				after_id = 0;
				$tr = $ptr;
			}

			if(orig.id !== null) orig.cancel();
			orig.id = id;

			if(id == 0) {
				$td = $tr.find("td.o > div");
				url = "/book/" + Book.id + "/" + Chap.id + "/" + id + "/edit?after=" + after_id + "&ajax=1";
			} else {
				$td = $(this).parents("td.o > div");
				orig.saveHtml = $td.html();
				url = "/book/" + Book.id + "/" + Chap.id + "/" + id + "/edit?ajax=1";
			}

			$td.addClass("loading").load(url, {}, function() {
				// $(this) == $td
				$td.removeClass("loading").addClass("editing");

				$td.find("button.cancel").click(orig.cancel);
				var $ta = $td.find("textarea");
				$ta .elastic()
					.bind("keyup change blur click", orig.ccntUpdate)
					.keyup().focus();
				orig.timer = setInterval(function() { $ta.keyup() }, 333);

				$td.find("form").ajaxForm({
					dataType: "json",
					data: {ajax: 1},
					beforeSubmit: function() {
						$td.find("form :submit").attr("disabled", true);
						orig.id = null;
					},
					success: function(data) {
						if(data.error) {
							$td.find("form :submit").attr("disabled", false);
							alert(data.error);
							return;
						}

						$td.removeClass("editing").html(data.body);

						if(id == 0) {
							$tr.find("td.b").append("<a href='#'><i class='icon-star-empty'></i></a>");
							$tr.find("td.u").append("<a href='#'><i class='icon-arrow-right'></i></a>");
							$tr.find("td.c").append("<a href='#'><i class='icon-comment-empty'></i></a>");
							$tr.attr("id", "o" + data.id);
							Chap.n_verses++;
							tb.stats();
						}
					}
				}).keydown(function(e) {
					if(e.ctrlKey && e.which == 13) $(":submit", this).click();
					else if(e.which == 27) $(".cancel", this).click();
				});
			});
		},
		cancel: function() {
			if(orig.id == 0) $("#o0").remove();
			else $("#o" + orig.id + " td.o div").removeClass("editing").html(orig.saveHtml);
			clearInterval(orig.timer);
			orig.id = null;
			orig.saveHtml = "";
		},
		rm: function(e) {
			e.preventDefault();

			var $tr = $(this).parents("tr");
			var $td = $tr.children("td.o");
			var orig_id = $tr.attr("id").substr(1);

			$td.addClass("deleting");

			if(!confirm("Вы абсолютно уверены, что хотите удалить этот фрагмент оригинала вместе со всеми его переводами и комментариями? Отменить эту процедуру нельзя.")) {
				$td.removeClass("deleting");
				return;
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
						tb.stats(data.n_vars, data.d_vars, data.n_verses);
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
		ccntUpdate: function() {
			var t = $(this).val().replace(/\n/g, "");
			$("#o-ccnt b").text(t.length);
		},
		create: function(e) {
			e.preventDefault();
		}
	};

	/* Рейтинг */
	(function() {
		var $votesBox = null, id = null;

		var explain = function(e) {
			e.preventDefault();
			e.stopPropagation();

			// Грязный костыль на случай "клик по .rating .current, клик по редактированию этой версии, отмена"
			if($(this).siblings(".votes").length) {
				hide();
				return;
			}

			if($votesBox !== null) hide();

			$votesBox = $("<div class='votes loading' id='rating-explain'><div class='pane list pos'><h3></h3><ul></ul></div><strong class='base total'>Минутку...</strong><div class='pane list neg'><h3></h3><ul></ul></div></div>").appendTo($(this).parents(".rating"));
			$votesBox.click(function(e) { e.stopPropagation(); });
			$("html").click(hide);

			id = $(this).parents("div[id^=t]").attr("id").substr(1);
			$.ajax({
				url: "/book/" + Book.id + "/" + Chap.id + "/rating_explain?id=" + id,
				dataType: "json",
				success: function(data) {
					var d, html, $ulNeg = $votesBox.find(".list.neg ul"), $ulPos = $votesBox.find(".list.pos ul");
					for(var i in data) {
						d = data[i];
						html = "<li><a href='/users/" + d.id + "'>" + d.login + "</a>";
						if(User.id == d.id) html += " <a href='#' class='btn-txt rmmy'>×</a>";
						html += "</li>";
						$(html).appendTo(d.mark < 0 ? $ulNeg : $ulPos);
					}
					$votesBox.removeClass("loading");
					$votesBox.find(".list.pos h3").html($ulPos.children("li").length > 0 ? "Понравилось:" : "Никто не плюсовал");
					$votesBox.find(".list.neg h3").html($ulNeg.children("li").length > 0 ? "Не понравилось:" : "Никто не минусовал");
					$votesBox.find(".total").html(data.length ? (data.length + Global.rusEnding(data.length, " голос", " голоса", " голосов")) : "&mdash;");
					$votesBox.find("a.rmmy").click(function(e) {
						e.preventDefault();
						vote($(this).parents("div[id^=t]").attr("id").substr(1), 0);
						hide();
					});
				}
			});

		};

		var hide = function() {
			$("html").unbind("click", hide);
			// Продолжение грязного костыля
			if($votesBox === null) $("#rating-explain").remove();
			else $votesBox.remove();
			$votesBox = null;
			id = null;
		};

		var voteClick = function(e) {
			e.preventDefault();
			vote($(this).parents("div[id^=t]").attr("id").substr(1), $(this).hasClass("neg") ? -1 : 1);
		};

		var vote = function(id, mark) {
			$.ajax({
				url: "/book/" + Book.id + "/" + Chap.id + "/rate_tr",
				dataType: "json",
				type: "POST",
				data: {id: id, mark: mark},
				success: function(data) {
					var $current = $("#t" + data.id + " .rating .current");
					$current.text(data.rating);
					$current.removeClass("pos").removeClass("neg");
					if(data.rating > 0) $current.addClass("pos");
					else if(data.rating < 0) $current.addClass("neg");
				}
			});
		};

		$("#Tr")
			.delegate(".rating .current", "click", explain)
			.delegate(".rating .vote", "click", voteClick)
	})();

	/* Paginator */
	(function() {
		var $div = $(".chic-pages");
		if(!$div.length) return;

		var $ul = $div.find("ul"), maxPage = $div.data("maxpage"),
			activeLeft = $ul.find("li.active").position().left,
			ulWidth = $ul.width(), $lastLi = $ul.find("li:last-child"), $lastLiWidth = $lastLi.width(),
			$blurLeft = $div.find(".blur.left"), $blurRight = $div.find(".blur.right");

		function scrollUl(v) {
			$ul.scrollLeft(v);

			if(v > 0) $blurLeft.show();
			else $blurLeft.hide();

			if($lastLi.position().left + $lastLiWidth > ulWidth) $blurRight.show();
			else $blurRight.hide();
		}

		$("input:text", $div).keypress(function(e) {
			if(e.which == 13) {
				var go = parseInt($(this).val());
				if(isNaN(go) || go < 1) go = 1;
				else if(go > maxPage) go = maxPage;
				if(/Orig_page=\d+/.test(location.search)) {
					location.search = location.search.replace(/Orig_page=\d+/, "Orig_page=" + go);
				} else if(location.search.length) {
					location.search += "&Orig_page=" + go;
				} else {
					location.search = "Orig_page=" + go;
				}
			}
		}).focus(function(e) { $(this).select(); });

		scrollUl(activeLeft - ulWidth / 2);

		$ul.bind("mousedown", function(e) {
			var self = this, startX = e.pageX, startScroll = $ul.scrollLeft();
			if($(e.target).is("input")) return;

			$(document).bind("mousemove", function(e) {
				scrollUl(startScroll + startX - e.pageX);
			});

			$(document).bind("mouseup", function(e) {
				$(document).unbind("mousemove");
				$(self).unbind("mouseup");
			});
		}).bind("dragstart", function(e) {
			return false;
		}).bind("mousewheel", function(e, delta) {
			e.preventDefault();
			scrollUl($ul.scrollLeft() - 15 * delta);
		});
	})();

	tb.init();
	dict.init();

	(function() {
		var $alert = $("#alert-empty");
		if(!$alert.length) return;

		$alert.find("a.create").click(orig.edit);

	})();

	/* Скролл к текущему фрагменту по ссылке #o12345 */
	setTimeout(function() {
		var r, $o;
		if(r = /#o(\d+)$/.exec(location.hash)) {
			$o = $(r[0]).addClass("m");
			$(window).scrollTop($o.offset().top - $("#tb-main").outerHeight() - 5);
		}
	}, 500);

	$table.find("tr").each(function() {
		var id = this.id.substr(1);
		if(!id) return true;

		var $this = $(this), $tdo = $this.find("td.o");
		$tdo.children("div").css("min-height", $tdo.height());

		var $aord = $tdo.find(".info a.ord");
		$aord.attr("href", Book.url(Chap.id + "/" + id + $aord.text())).attr("title", "Ссылка на этот фрагмент");
	});

	$table.find("td.o").each(function() {
		var $this = $(this);
		$this.children("div").css("min-height", $this.height());
	});

	$table.find("td.b a:not([title])").attr("title", "Поставить закладку");
	$table.find("td.o .tools a.edit").attr("title", "Редактировать оригинал");
	$table.find("td.o .tools a.add").attr("title", "Добавить фрагмент оригинала");
	$table.find("td.o .tools a.rm").attr("title", "Удалить фрагмент оригинала");
	$table.find("td.t .tools a.edit").attr("title", "Редактировать перевод");
	$table.find("td.t .tools a.rm").attr("title", "Удалить перевод");
	$table.find("td.t .info i.icon-flag").attr("title", "Выделить все переводы этого автора");
	$table.find("td.u a").attr("title", "Предложить свою версию перевода");
	$table.find("td.c a").attr("title", "Комментарии");

	$table
		.delegate("td.b a", "click", bmAdd)
		.delegate("td.o .tools a.xp", "click", toggleOrigEdit)
		.delegate("td.o .tools a.edit", "click", orig.edit)
		.delegate("td.o .tools a.add", "click", orig.edit)
		.delegate("td.o .tools a.rm", "click", orig.rm)
		.delegate("td.u a", "click", tr.edit)
		.delegate("td.t .tools a.xp", "click", toggleTrEdit)
		.delegate("td.t .tools a.edit", "click", tr.edit)
		.delegate("td.t .tools a.rm", "click", tr.rm)
		.delegate("td.t .info i.icon-flag", "click", hlUser)
		.delegate("td.c a", "click", commentsToggle);

	$("#timeshift-modal").find("a.advanced").click(function(e) {
		e.preventDefault();
		var $this = $(this), $modal = $("#timeshift-modal");
		$modal.find("div.advanced").show(400);
		$modal.find("[name=from]").focus();
		$this.parents(".control-label").text("На время:")
	});

//	Скролл окна по пикселу на Alt-стрелки для дебага
//	$("html").bind("keydown", function(e) {
//		if(!e.altKey) return;
//		$w = $(window);
//		if(e.which == 38) $w.scrollTop($w.scrollTop() - 1);
//		else if(e.which == 40) $w.scrollTop($w.scrollTop() + 1);
//	});

});



var T = {
	// Заглушка для старого кода onclick='T.comments.stop()' в //orig/comments.php
	comments: { stop: function(a) { return false; }},
	mytalks: function(a,b) { return false; }
};
