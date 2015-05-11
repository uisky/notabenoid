/* Book group manager */
var GM = {
	init: function() {},
	hints: {
		"-1": "Забанить / Разбанить",
		0: "Выгнать вон",
		2: "Назначить / Разжаловать модераторов"
	},
	status_set: function(status) {
		$("#inquisition").hide();
		$("#inquisition-actions :submit").text(GM.hints[status]);
		$("#inquisition-actions").show();
		$("#members_manage [name=status]").val(status);
		$("#people tbody tr td:first-child").each(function(i, el) {
			var $td = $(this);
			var id = $(this).attr("data-id");
			var st = $(this).attr("data-status");
			var html;

			if(id == User.id) return true;

			$(this).attr("data-html", $(this).html());

			if(status == 0 && st == 0) {
				html = "";
			} else {
				html = "<input type='hidden' name='id[" + id + "]' value='0' /><input type='checkbox' name='id[" + id + "]' ";
				if(status != 0 && st == status) html += "checked ";
				html += "value='1' />"
			}


			$td.html(html);
		});
	},
	cancel: function() {
		$("#people tbody tr td:first-child").each(function(i, el) {
			$(this).html($(this).attr("data-html"));
			$("#members_manage [name=status]").val("");
			$("#inquisition-actions").hide();
		$("#inquisition-actions :submit").text("Ok");
			$("#inquisition").show();
		});
	}
}
$(GM.init);