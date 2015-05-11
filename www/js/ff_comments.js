/**
 * Usage: $(element).comments()
 * element - div-контейнер со всеми комментариями (обычно - <div class='comments'>
 */

(function(d){d.each(["backgroundColor","borderBottomColor","borderLeftColor","borderRightColor","borderTopColor","color","outlineColor"],function(f,e){d.fx.step[e]=function(g){if(!g.colorInit){g.start=c(g.elem,e);g.end=b(g.end);g.colorInit=true}g.elem.style[e]="rgb("+[Math.max(Math.min(parseInt((g.pos*(g.end[0]-g.start[0]))+g.start[0]),255),0),Math.max(Math.min(parseInt((g.pos*(g.end[1]-g.start[1]))+g.start[1]),255),0),Math.max(Math.min(parseInt((g.pos*(g.end[2]-g.start[2]))+g.start[2]),255),0)].join(",")+")"}});function b(f){var e;if(f&&f.constructor==Array&&f.length==3){return f}if(e=/rgb\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*\)/.exec(f)){return[parseInt(e[1]),parseInt(e[2]),parseInt(e[3])]}if(e=/rgb\(\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*,\s*([0-9]+(?:\.[0-9]+)?)\%\s*\)/.exec(f)){return[parseFloat(e[1])*2.55,parseFloat(e[2])*2.55,parseFloat(e[3])*2.55]}if(e=/#([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})/.exec(f)){return[parseInt(e[1],16),parseInt(e[2],16),parseInt(e[3],16)]}if(e=/#([a-fA-F0-9])([a-fA-F0-9])([a-fA-F0-9])/.exec(f)){return[parseInt(e[1]+e[1],16),parseInt(e[2]+e[2],16),parseInt(e[3]+e[3],16)]}if(e=/rgba\(0, 0, 0, 0\)/.exec(f)){return a.transparent}return a[d.trim(f).toLowerCase()]}function c(g,e){var f;do{f=d.css(g,e);if(f!=""&&f!="transparent"||d.nodeName(g,"body")){break}e="backgroundColor"}while(g=g.parentNode);return b(f)}var a={aqua:[0,255,255],azure:[240,255,255],beige:[245,245,220],black:[0,0,0],blue:[0,0,255],brown:[165,42,42],cyan:[0,255,255],darkblue:[0,0,139],darkcyan:[0,139,139],darkgrey:[169,169,169],darkgreen:[0,100,0],darkkhaki:[189,183,107],darkmagenta:[139,0,139],darkolivegreen:[85,107,47],darkorange:[255,140,0],darkorchid:[153,50,204],darkred:[139,0,0],darksalmon:[233,150,122],darkviolet:[148,0,211],fuchsia:[255,0,255],gold:[255,215,0],green:[0,128,0],indigo:[75,0,130],khaki:[240,230,140],lightblue:[173,216,230],lightcyan:[224,255,255],lightgreen:[144,238,144],lightgrey:[211,211,211],lightpink:[255,182,193],lightyellow:[255,255,224],lime:[0,255,0],magenta:[255,0,255],maroon:[128,0,0],navy:[0,0,128],olive:[128,128,0],orange:[255,165,0],pink:[255,192,203],purple:[128,0,128],violet:[128,0,128],red:[255,0,0],silver:[192,192,192],white:[255,255,255],yellow:[255,255,0],transparent:[255,255,255]}})(jQuery);

(function($) {
	var collapsed = [];
	var methods = {
		init: function(options) {
			var opts = $.extend({
				navBox: true,
				multiple: false		// На странице есть ещё комментарии,
			}, options);

			function rateClick(e) {
				e.preventDefault();

				var $this = $(this), mark = $this.hasClass("n") ? -1 : 1, id = methods._getId(this);

				$.ajax({
					type: "POST",
					url: methods._getContainer(this).find("form.reply").attr("action").replace(/\/c(\d+)\/reply$/, "/c" + id + "/rate"),
					data: {id: id, mark: mark},
					dataType: "json",
					success: function(data) {
						$this.parents(".rating").find("span").html(data.rating.toString().replace("-", "&minus;"));
					}
				});
			}

			function scrollUp(e) {
				var $w = $(window), st = $w.scrollTop();
				$($newCmt.get().reverse()).each(function() {
					var $this = $(this), top = $this.offset().top;
					if(top < st) {
						$.scrollTo(this, 100, {offset: -10, onAfter: function () {
							var bg = $this.css("background-color");
							$this.animate({backgroundColor: "#BDBD8E"}, 50).animate({backgroundColor: bg}, 50);
						}});
						return false;
					}
				});
			}

			function scrollDn(e) {
				var $w = $(window), wh = $w.height(), st = $w.scrollTop();
				$newCmt.each(function() {
					var $this = $(this), top = $this.offset().top;
					if(top > st + wh) {
						$.scrollTo(top + $this.height() - wh + 10, 100, {onAfter: function () {
							var bg = $this.css("background-color");
							$this.animate({backgroundColor: "#BDBD8E"}, 50).animate({backgroundColor: bg}, 50);
						}});
						return false;
					}
				});
			}

			this.find("a.up").attr("title", "Кому это отвечают?");
			this.find("a.a").attr("title", "Ссылка на этот комментарий");
			this.find("a.dot").attr("title", "Выделить все комментарии этого автора");
			this.find("a.rm").attr("title", "Удалить комментарий");
			this.find(".rating a.p").attr("title", "Это хороший, правильный комментарий");
			this.find(".rating a.n").attr("title", "Это плохой, неправильный комментарий");

			$(".re_root", this).click(methods.reply);
			$("<div class='scroll-top' title='Промотать к началу комментариев'></div>").appendTo(this).click(function(e) {
				$.scrollTo(this, 500);
			});

			this.delegate("a.dot", "click", methods.dot)
				.delegate("a.re", "click", methods.reply)
				.delegate("a.rm", "click", methods.remove)
				.delegate("a.thread-collapse", "click", methods.collapse_click)
				.delegate("p.collapsed-msg", "click", methods.collapse_click)
				.delegate(".rating a", "click", rateClick)
				.delegate("img.upic", "click", function (e) {
					$(this).toggleClass("bigger");
				});

			var _container = this;
			var $form = $(".thread-form form", this);

			$form.find(".cancel").click(function() { methods.reply(0, _container); });
			if($.fn.elastic) $form.find("textarea").elastic();

			$form.ajaxForm({
				dataType: "json",
				data: {ajax: 1},
				beforeSubmit: function(data, $form) {
					$(":submit", $form).attr("disabled", true);
				},
				success: function(data, status, xhr, $form) {
					if(data.error) return !!alert(data.error);

					var html = "<div class='thread'>" + data.html + "</div>";
					if(data.pid) {
						$("#cmt_" + data.pid).parent().append(html);
					} else {
						$(".thread:last", _container).before(html);
					}

					$("[name=Comment\\[body\\]]", $form).val("");	// если начали набирать новый комментарий, не дождавшись ответа от сервера, будет нехорошо. До ответа стирать нельзя - может вернуться ошибка и коммент нужно будет слать заново
					methods.reply(0, _container);
				},
				complete: function() {
					$(":submit", $form).attr("disabled", false);
				}
			}).keydown(function(e) {
				if(e.ctrlKey && e.which == 13) $(":submit", this).click();
			});

			if(res = location.hash.match(/^#cmt_(\d+)/)) {
				methods.reply(res[1], this);
			}

			// Кнопки для навигации по комментариям
			var $newCmt = $(".comment.new", opts.multiple ? undefined : this), newCmtCur = 0;

			if($newCmt.length && opts.navBox) {
				var $navBox = $("div.comments-nav-box");
				if($navBox.length == 0) {
					$navBox = $("<div class='comments-nav-box' data-used='1'><div class='up' title='Предыдущий непрочитанный комментарий'>&uarr;</div><div class='dn' title='Следующий непрочитанный комментарий'>&darr;</div></div>")
						.appendTo("body");
					$navBox.find(".up").click(scrollUp);
					$navBox.find(".dn").click(scrollDn);
				} else {
					$navBox.data("used", $navBox.data("used") + 1);
				}
			}

			// Сворачивание веток
			methods._loadCookie();
			var collapsedO = {};
			for(var i in collapsed) collapsedO[collapsed[i]] = true;
			this.children(".thread").each(function() {
				if($(".thread", this).length == 0) return true;

				var id = $(this).children(".comment").attr("id").substr(4),
					$btn = $("<a href='#' class='thread-collapse' title='Свернуть ветку'><i class='i icon-minus'></i></a>");

				$("> .comment", this).append($btn);

				if(collapsedO[id]) {
					$this = $(this);
					$this.children(".thread").hide();
					$this.append("<p class='collapsed-msg'>скрытые комментарии (" + $this.find(".thread").length + " шт.)</p>");
					$btn.html("<i class='i icon-plus'></i>").attr("title", "Развернуть ветку");
				}
			});
			collapsedO = {};
		},
		dot: function(e) {
			e.preventDefault();

			if(!(res = /\bu(\d+)\b/.exec($(this).parents(".comment").attr("class")) )) {
				return;
			}
			var uid = res[1];

			$(".u" + uid).toggleClass("highlighted");
		},
		reply: function(e, $container) {
			var pid, nofocus = false;

			if(!$container) {
				e.preventDefault();
				$container = methods._getContainer(this);
				pid = methods._getId(this);
			} else {
				pid = e;
				nofocus = true;
			}

			if(pid == 0) {
				$(".thread-form", $container).appendTo($container);
				$(".cmt_0_btn", $container).hide();
			} else {
				$(".thread-form", $container).appendTo('#cmt_' + pid);
				$(".cmt_0_btn", $container).show();
			}

			var action = $container.find("form.reply").attr("action").replace(/\/c(\d+)\/reply$/, "/c" + pid + "/reply");
			$container.find("form.reply").attr("action", action);
			$container.find("form.reply input[name=Comment\\[pid\\]]").val(pid);
			$container.find("form.reply .cancel").css("display", pid ? "inline" : "none");
			if(!nofocus) $container.find("form.reply textarea[name=Comment\\[body\\]]").focus();
		},
		remove: function(e) {
			e.preventDefault();

			var id = methods._getId(this);
			var $container = methods._getContainer(this);
			var $cmt = $("#cmt_" + id);

			$cmt.addClass("deleting");
			if(!confirm("Вы уверены?")) {
				$cmt.removeClass("deleting");
				return false;
			}

			$.ajax({
				type: "POST",
				url: $container.find("form.reply").attr("action").replace(/\/c(\d+)\/reply$/, "/c" + id + "/remove"),
				data: {ajax: 1, id: id},
				dataType: "json",
				success: function(data) {
					$("#cmt_" + data.id).removeClass("deleting");
					if(data.error) return !!alert(data.error);
					$("#cmt_" + data.id).addClass("deleted").html("<div class='text'>Комментарий удалён.</div>");
				}
			});

			return true;
		},
		collapse_click: function(e) {
			e.preventDefault();
			$thread = $(this).parents(".thread");
			var id = $thread.children(".comment").attr("id").substr(4);

			if($(".collapsed-msg", $thread).length) {
				$thread.children(".thread").show();
				$(".collapsed-msg", $thread).remove();
				$(".thread-collapse", $thread).html("<i class='i icon-minus'></i>").attr("title", "Свернуть ветку");

				var p = $.inArray(id, collapsed);
				if(~p) collapsed.splice(p, 1);
				methods._saveCookie();
			} else {
				$thread.children(".thread").hide();
				$thread.append("<p class='collapsed-msg'>скрытые комментарии (" + $thread.find(".thread").length + " шт.)</p>");
				$(".thread-collapse", $thread).html("<i class='i icon-plus'></i>").attr("title", "Развернуть ветку");

				collapsed.push(id);
				methods._saveCookie();
			}
		},
		destroy: function() {
			var $navBox = $("div.comments-nav-box");
			if($navBox.length) {
				var used = $navBox.data("used");
				if(used <= 1) $navBox.remove();
				else $navBox.data("used", used - 1);
			}

		},

		_getContainer: function(el) {
			return $(el).parents(".comments");
		},
		_getId: function(el) {
			var $p = $(el).parents(".comment");
			return $p.length ? $p.attr("id").substr(4) : 0;
		},
		_saveCookie: function() {
			$.cookie("ff_comments_c", collapsed.join(","), {expires: 365});
		},
		_loadCookie: function() {
			var t = $.cookie("ff_comments_c");
			if(t !== null) collapsed = t.split(",");
		}
	};

	$.fn.ff_comments = function(method) {
		if(methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call( arguments, 1));
		} else if(typeof method === 'object' || !method) {
			return methods.init.apply(this, arguments);
		} else {
			$.error('Method ' +  method + ' does not exist on jQuery.comments');
		}
	}
})(jQuery);
