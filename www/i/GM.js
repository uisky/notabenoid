var GM = {
	mode: "",
	start: function(mode) {
		GM.mode = mode;
		$("#" + GM.mode).show(100);
		$("#inquisition").hide(100);
		$("#people").addClass("fear");
		$("#people a").click(GM.a_click);
	},
	cancel: function() {
		$("#people a.sel").removeClass("sel");
		$("#" + GM.mode + " input[type='hidden']").remove();
		$("#" + GM.mode + "_list").html("");
		$("#" + GM.mode + " p.hint").show();
		$("#" + GM.mode + " p.t").hide();
		$("#" + GM.mode).hide(100);
		$("#inquisition").show(100);
		$("#people").removeClass("fear");
		$("#people a").unbind("click", GM.a_click);
		GM.mode = "";
	},
	
	a_click: function() {
		var login = $(this).text();
		var id = $(this).attr("data-id");

		if($(this).hasClass("owner")) return false;
		
		if(GM.mode == "subvert") {
			if(!$(this).hasClass("moderator")) return false;
		} else {
			if($(this).hasClass("moderator")) return false;
		}
		
		var users = $("#" + GM.mode + "_list").html();
		if($(this).hasClass("sel")) {
			$(this).removeClass("sel");
			
			users = users.replace(new RegExp("" + login + "(, )?"), "").replace(/,\s+$/, "");
			
			if(users == "") {
				$("#" + GM.mode + " p.hint").show();
				$("#" + GM.mode + " p.t").hide();
			}
			
			$("#" + GM.mode + " input[type='hidden'][value='" + id + "']").remove();
		} else {
			$(this).addClass("sel");

			if(users == "") {
				$("#" + GM.mode + " p.hint").hide();
				$("#" + GM.mode + " p.t").show();
			} else {
				users += ", ";
			}
			users += login;

			$("#" + GM.mode + " form").append("<input type='hidden' name='" + GM.mode + "_ids[]' value='" + id + "' />");
		}

		$("#" + GM.mode + "_list").html(users);

		return false;
	},
	
	ban_start: function() {
		$("#inquisition").hide(200);
		$('#ban-form').show(200);
		$('#ban-switch').hide(200); 
		$("#people").addClass("fear");
		$("#people a").click(GM.ban_click);
		return false;
	},
	ban_cancel: function() {
		$("#people a").unbind("click", GM.ban_click);
		$("#people").removeClass("fear");
		$('#ban-switch').show(200); 
		$('#ban-form').hide(200);
		$("#inquisition").show(200);
		return false;
	},
	ban_click: function() {
		if($(this).hasClass("owner")) return false;
		$("#ban-form [name=ban\\[login\\]]").val($(this).text());
		$("#ban-form [name=ban\\[reason\\]]").focus();
		return false;
	},
	unban: function(id) {
		if(!confirm("Вы уверены?")) return false;
		
		$("#unban-form [name=unban_id]").val(id);
		$("#unban-form").submit();
	}
}
