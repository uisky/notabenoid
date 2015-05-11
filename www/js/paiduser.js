/**
 * Этот файл бесполезно подключать, если вы не являетесь платным пользователем, все проверки проходят на серверной стороне
 */
$(function() {
	var INFO_REFRESH_RATE = 60;

	var oldInfo = {}, a = ['c', 'n', 'm'];
	for(var j in a) {
		var i = a[j];
		oldInfo[i] = parseInt($("#hm-" + i + " strong b").text().replace("(", "").replace(")", "")) || 0;
	}

	function infoTimer() {
		$.ajax({
			url: "/my/info",
			success: function(data) {
				setTimeout(infoTimer, INFO_REFRESH_RATE * 1000);
				for(var i in data) {
					$("#hm-" + i + " strong b").text(data[i] == 0 ? "" : ("(" + data[i] + ")"));
	//				if(oldInfo[i] != data[i]) {
	//					console.log("new value for %s: %d", i, data[i] - oldInfo[i]);
	//					window.webkitNotifications.createNotification("nf_info.png", "Новый комментарий", "У вас X новых комментариев").show();
	//				}
				}
				oldInfo = data;
			}
			// нужен свой error-handler, который не будет алерты выдавать
		});
	}

	setTimeout(infoTimer, INFO_REFRESH_RATE * 1000);
});