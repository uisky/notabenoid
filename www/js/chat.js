var Chat = {
	room_id: 0,

	box: null,
	room: null,

	timer: null,
	initialized: false,
	on: false,

	since: 0,
	servertime: 0,
	_tickerTimer: null,

	init: function() {
		var $resizer = $("#chat-resizer"), $form = $("#chat-say");

		Chat.box = $("#chat-box");
		Chat.room = $("#chat-room");

		if(Chat.box.length == 0 || Chat.room.length == 0) return false;

		$resizer.mousedown(function(e) {
			var startY = e.pageY, startHeight = Chat.box.height(), height = startHeight;

			function mouseMove(e) {
				e.preventDefault();
				height = startHeight + startY - e.pageY;
				if(height > 45) Chat.box.height(height);
			}

			function mouseUp(e) {
				$(document).unbind("mousemove", mouseMove).unbind("mouseup", mouseUp);
				$("body").removeClass("no-select");
				Chat.room.scrollTop(65535);
				User.ini_set("chat.h", height);
			}

			$("body").addClass("no-select");
			$(document).bind("mousemove", mouseMove).bind("mouseup", mouseUp);
		}).bind("dragstart", function(e) {
				e.preventDefault();
				return false;
			});

		if(typeof Book != "undefined" && Book.id != 0) {
			Chat.room_id = Book.id;
			$form.attr("action", "/chat/room/" + Chat.room_id);
		}


		$form.ajaxForm({
			dataType: "json",
			beforeSerialize: function($form) {
				$form.find("[name=since]").val(Chat.since);
			},
			beforeSubmit: function() {
				$form.find("[name=msg]").attr("disabled", true);
			},
			success: function(data) {
				Chat._handleData(data);

				$form.find("[name=msg]").attr("disabled", false).val("").focus();
			}
		});

		$form.find(".stop").click(Chat.stop);

		Chat.room.delegate("a.user", "click", function(e) {
			e.preventDefault();
			var $input = $form.find("[name=msg]"), nick = $(this).text();
			$input.val(nick + ", " + $input.val()).focus();
		});

		Chat.initialized = true;

		return true;
	},
	start: function() {
		if(!Chat.initialized) if(!Chat.init()) return false;

		Chat.box.show(100);
		Chat.room.scrollTop(65535);

		function refresh() {
			var url = "/chat/room/" + Chat.room_id + "?since=" + Chat.since;
			$.getJSON(url, Chat._handleData);
		}

		refresh();
		Chat.timer = Visibility.every(20 * 1000, refresh);

		Chat.on = true;
		User.ini_set("chat.on", 1);
		Chat.onStart();

		return true;
	},
	stop: function() {
		Visibility.stop(Chat.timer);
		Chat.box.hide(100);
		Chat.on = false;
		User.ini_set("chat.on", 0);
		Chat.onStop();
	},
	toggle: function() {
		if(Chat.on) Chat.stop();
		else Chat.start();
		return false;
	},

	onStart: function() {},
	onStop: function() {},

	_handleData: function(data) {
		Chat.since = data.servertime;
		if(data.room.length) {
			for(var i in data.room) {
				Chat.room.append(Chat._renderLine(data.room[i]));
			}
			Chat.room.scrollTop(65535);
		}
		Chat.servertime = data.servertime;
		if(Chat._tickerTimer === null) Chat._tickerTimer = setInterval(Chat._ticker, 10000);
	},
	_ticker: function() {
		Chat.servertime += 10;

		Chat.room.find("p").each(function() {
			var $p = $(this), $a = $p.find("a.user");
			$a.attr("title", Chat._ago(Chat.servertime - $p.data("time")));
		});
	},
	_renderLine: function(data) {
		var d = new Date(parseInt(data.t) * 1000),
			date = Chat._ago(Chat.since - data.t); // d.toLocaleString();
		return "<p" + (data.i == User.id ? " class='my'" : "") + " data-time='" + data.t + "'><a href='/users/" + data.i + "' class='user' title='" + date + "'>" + data.u + "</a>: " + data.m + "</p>";
	},
	_rus: function(n, t1, t2, t5) {

	},
	_ago: function(time) {
		if(time <= 0) return "только что";

		var t = "", days = 0, d = 0, h = 0, m = 0, s = 0;
		s = time % 60;
		m = Math.floor(time / 60);
		if(m > 60) {
			h = Math.floor(m / 60);
			m = m % 60;
			if(h > 24) {
				days = Math.floor(h / 24);
				h = h % 24;
			}
		}
		s = (Math.round(s / 10) * 10);

		if(days > 0) t += days + " сут. ";
		if(h > 0) t += h + " час. ";
		if(m != 0) t += m + " мин. ";
		if(h == 0 && m == 0) t += s + " сек. ";
		t += "тому назад";

		return t;
	}
};

$(function() {
	$(document).keydown(function(e) {
		if(e.ctrlKey && (e.which == 192 || e.which == 0)) {
			Chat.toggle();
		}
	});
	if(User.ini_get("chat.on") == 1) Chat.start();
});
