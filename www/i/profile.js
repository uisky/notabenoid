var Profile = {};

var Karma = {
	key: 0,
	url: location.protocol + "//" + location.host + location.pathname + "/karma",
	rateslist_opened: 0,

	mouseover: function() {
		$("#ProfileHead td.k").removeClass("inactive");
	},
	mouseout: function() {
		$("#ProfileHead td.k").addClass("inactive");
	},
	help: function() {
		Karma.close();
		var el = $("#ProfileHead td.k div.help_popup");
		el.toggle();
		return false;
	},
	help_close: function() {
		$("#ProfileHead td.k div.help_popup").hide();
		return false;
	},
	vote: function(mark) {
		Karma.close();
		Karma.help_close();
		$.postJSON(Karma.url, {mark: mark, key: Karma.key}, function(data) {
			$("#ProfileHead td.k big").html(data.sum);
		});
		return false;
	},
	show: function() {
		Karma.help_close();
		if(Karma.rateslist_opened != 0) {
			Karma.close();
			return false;
		}
		Karma.close();
		Karma.rateslist_opened = 1;

		var em = $("#ProfileHead td.k");
		var html = "<div id='RatesList'><img class='arr' src='/i/rateslist_arrow.gif' alt='' /><p>" + 
			"<table> <tr><th class='plus'>минусы</th><th>плюсы</th></tr> <tr><td class='minus'></td><td class='plus'></td></tr> </table>" +
			"<a href='#' onclick='return Karma.close()'>закрыть</a></p></div>";
		$(document.body).append(html);
		var offset = em.offset();
		$("#RatesList").css("left", offset.left - 18 + "px").css("top", offset.top + 50 + "px");

		$.getJSON(Karma.url, function(data) {
			$("#ProfileHead td.k big").html(data.sum);
			for(var j in [-1, 1]) {
				dir = [-1, 1][j];
				for(var i in data[dir]) {
					var U = new User(data[dir][i].id, data[dir][i].login);
					$("#RatesList td." + (dir < 0 ? "minus" : "plus")).append(U.ahref() + " (" + dir + ")<br />");
				}
			}
			
			$(document).bind('click', Karma.click_outside);
		});

	},
	close: function() {
		if(Karma.rateslist_opened == 0) return;
		$("#RatesList").remove();
		Karma.rateslist_opened = 0;
		$(document).unbind('click', Karma.click_outside);
		return false;
	},
	click_outside: function(e) {
		var $clicked = $(e.target);
		if(!($clicked.is('#RatesList') || $clicked.parents().is('#RatesList'))) {
			Karma.close();
		}
	}
}

$(function() {
	$("#ProfileHead td.k").hover(Karma.mouseover, Karma.mouseout);
});

