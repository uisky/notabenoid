/**CHANGE  TO GLOBALS. **/
			var Ac_chap = {ac_read: "читать", ac_gen: "скачивать", ac_rate: "оценивать", ac_comment: "комментировать", ac_tr: "переводить"};
/************************/

var GLOBALS = {
	ac_areas: {
		ac_read: "читать", ac_gen: "скачивать", ac_rate: "оценивать", ac_comment: "комментировать", ac_tr: "переводить",
		ac_blog_r: "читать блог", ac_blog_c: "комментировать в блоге", ac_blog_w: "писать посты в блоге",
		ac_announce: "создавать анонсы перевода", ac_membership: "управлять членством в группе перевода", ac_chap_edit: "редактировать оглавление", ac_book_edit: "редкатировать свойства перевода",
	},
	ac_areas_chap: {ac_read: "читать", ac_gen: "скачивать", ac_rate: "оценивать", ac_comment: "комментировать", ac_tr: "переводить"},
	ac_roles: {a: "все", g: "группа", m: "модераторы", o: "никто"},
	ac_roles_title: {a: "все", g: "только члены группы перевода", m: "только модераторы", o: "только владелец"},
	translation_statuses: {0: "не определён", 1: "идёт перевод", 2: "перевод редактируется", 3: "перевод готов"}
}

function options(obj) {
	var html = "";
	for(var i in obj) html += "<option value='" + i + "'>" + obj[i] + "</option>";
	return html;
}

/* Common */
function ajaxerr(data) {
	if(data.substr(0, 7) != 'ERROR: ') return false;

	alert(data.substr(7));
	return true;
}

// Возвращает scrolltop
function ST() {
	if (window.pageYOffset) {
		return window.pageYOffset;
	} else if (document.body && document.body.scrollTop) {
		return document.body.scrollTop;
	} else if (document.documentElement && document.documentElement.scrollTop) {
		return document.documentElement.scrollTop;
	}
	return 0;
};

function cururl() {
	return location.protocol + "//" + location.host + location.pathname + location.search;
};

function htmlspecialchars(unsafe) {
	return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

(function($) {
	$.postJSON = function(url, data, callback) {
		$.ajax({
			url: url,
			type: 'POST',
			data: data,
			dataType: "json",
			success: callback,
			error: function(x, t, e) { alert("Ошибка связи. Попробуйте ещё раз.\n(" + t + ")"); }
		});
	}
})(jQuery);

function User(id, login) {
	this.id = id;
	this.login = login;
	this.url = function() {
		return "/users/" + this.id;
	}
	this.ahref = function() {
		return "<a href='" + this.url() + "' class='user'>" + this.login + "</a>";
	}
}


/* Мои переводы */
var MyBooks = {
	show: function() {
		var div = $("#MyBooks");

		setTimeout(function() {$(document).bind("click", MyBooks.click_outside)}, 200);

		if(div.css("display") == "none") {
			div.show(100).load("/site/mybooks");
		} else {
			div.hide(100);
		}
		return false;
	},
	close: function() {
		$(document).unbind("click", MyBooks.click_outside);
		$("#MyBooks").hide();
	},
	click_outside: function(e) {
		var $clicked=$(e.target);
		if(!($clicked.is('#MyBooks') || $clicked.parents().is('#MyBooks'))) $('#MyBooks').hide();
	}
};


/* Мои закладки */
var Bookmarks = {
	show: function() {
		var div = $("#Bookmarks");

		setTimeout(function() {$(document).bind("click", Bookmarks.click_outside)}, 200);

		if(div.css("display") == "none") {
			div.show(100).load("/sys/bookmarks.php");
		} else {
			div.hide(100);
		}

		return false;
	},
	close: function() {
		$(document).unbind("click", Bookmarks.click_outside);
		$("#Bookmarks").hide();
	},
	click_outside: function(e) {
		var $clicked=$(e.target);
		if(!($clicked.is('#Bookmarks') || $clicked.parents().is('#Bookmarks'))) Bookmarks.close();
	}
};

var Tabs = {
	cur_tab: null,

	init: function() {
		$(".tabs_header li a").click(Tabs.select_tab);
	},

	select_tab: function() {
		var tab_id = $(this).attr("href").substr(1);

		$(".tabs_header li").removeClass("on");
		$(this).closest("li").addClass("on");

		$(".tab").removeClass("on");
		$("#" + tab_id).addClass("on");

		$(".tabs_header li a").blur();

		Sidebar.recalc();

		return false;
	}
};

var Sidebar = {
	$layer: null,
	layer_height: 0,

	init: function() {
		this.$layer = $(".sr .fixed");
		if(this.$layer.length == 0) return false;
		if(this.$layer.length > 1) {
			alert("Системная ошибка: SIDEBAR_LEFT_" + this.$layers.length);
			return false;
		}

		this.$layer.css("position", "relative");
		this.recalc();

		$(window).scroll(Sidebar.onscroll);
		$(window).resize(Sidebar.recalc);
		$(window).scroll();
	},

	recalc: function() {
		Sidebar.layer_height = Sidebar.$layer.height();
		Sidebar.cont_height = $("#container #content").height();
		if(Sidebar.cont_height < Sidebar.layer_height) {
			$("#container #content").height(Sidebar.layer_height);
			Sidebar.cont_height = Sidebar.layer_height
		}
	},

	onscroll: function(e) {
		var top = ST();
		if(top + Sidebar.layer_height > Sidebar.cont_height) {
			top = Sidebar.cont_height - Sidebar.layer_height;
		}
		Sidebar.$layer.css("top", top + "px");
	}
};








/* Работа с группами - вступить, выйти. */
function gr_join(el, book_id, reload) {

	var old_html = $("#gr_join_btn").html();
	$("#gr_join_btn").html('<b>минутку...</b>');

	$.getJSON('/sys/gr_join.php', {'book_id': book_id}, function(data, txStatus) {
		if(data.alert) alert(data.alert);
		if(data.txt == '') {
			$("#gr_join_btn").html(old_html);
			return false;
		}
		$("#gr_join_btn").replaceWith("<b>" + data.txt + "</b>");
		if(reload == 1) {
			location.href = location.href;
		}
	});

	return false;
}

/* Подсказки */
// i - внутренний ID окошка с подсказкой, это надо записывать в базу скрытых подсказок
function hide_hint(i, el) {
	var div = el.parentNode;
	div.parentNode.removeChild(div);

	$.post(
		'/sys/hintman.php', {act: 'hide', id: i },
		function(data) {
			if(ajaxerr(data)) return;
		}
	);

	return false;
}





$(function() {
	Sidebar.init();
	Tabs.init();

	$(".upic.active").click(function() {
		var upic = $(this).attr("data-upic").split(".");
		var upic_url = "/i/upic/" + Math.floor(upic[0] / 1000) + "/" + upic[0] + "-" + upic[1] + "_big.jpg";
		console.log("Expand avatar %s", upic_url);
		console.dir(upic);

		var html = "<div id='userinfo_win'><img src='" + upic_url + "' alt='' /></div>";
		$(document.body).append(html);
		setTimeout(function() {$(document).click(
			function() {
				$("#userinfo_win").remove();
				$(document).unbind("click", Upic.click_outside);
			}
		)}, 100);
		return false;

	});
	
	for(var ac in GLOBALS.ac_areas) {
		for(var role in GLOBALS.ac_roles) {
			$("i." + ac + "." + role).attr("title", GLOBALS.ac_areas[ac] + (role == "o" ? " может " : " могут ") + GLOBALS.ac_roles_title[role]);
		}
	}
	
});

