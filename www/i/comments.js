var Cmt = {
	init: function() {
		$("#Comments a.dot").attr("title", "Выделить все комментарии этого автора");

		var af_options = {
			dataType: "json",
			success: Cmt.after_save,
			data: {ajax: 1}
		}
		$("#Comments form.reply").ajaxForm(af_options);

		$("#Comments form.reply textarea").bind("keyup", function(e) {
			if(e.ctrlKey && e.keyCode == 13) {
				$("#Comments form.reply").ajaxSubmit(af_options);
			}
		});

		$(document).bind("keyup", Cmt.key_document);
	},

	scroll_cmt: -1,
	key_document: function(e) {
		if(e.ctrlKey || e.altKey) {
			if(e.keyCode == 40) {
				$cmt = $("#Comments .comment.new").eq(Cmt.scroll_cmt + 1);
				if($cmt.length) {
					Cmt.scroll_cmt++;
					$.scrollTo($cmt, 100, {offset: -100});
				}
			} else if(e.keyCode == 38) {
				if(Cmt.scroll_cmt > 0) {
					Cmt.scroll_cmt--;
					$.scrollTo($("#Comments .comment.new").eq(Cmt.scroll_cmt), 100, {offset: -100});
				}
			}
		}
	},
	dot: function(uid) {
		$("#Comments .u" + uid).toggleClass("highlighted");
		return false;
	},
	re: function(pid, nofocus) {
		if(pid) {
			$("#cmt_0").appendTo('#cmt_' + pid);
			$("#cmt_0_btn").show();
		} else {
			$("#cmt_0").prependTo("#Comments .thread:last");
			$("#cmt_0_btn").hide();
		}

		var action = $("#Comments form.reply").attr("action").replace(/\/c(\d+)\/reply$/, "/c" + pid + "/reply");
		$("#Comments form.reply").attr("action", action);
		$("#Comments form.reply input[name=Comment\\[pid\\]]").val(pid);
		if(!nofocus) $("#Comments form.reply textarea[name=Comment\\[body\\]]").focus();

		return false;
	},

	rm: function(id) {
		$("#cmt_" + id).addClass("deleting");

		if(!confirm("Вы уверены?")) {
			$("#cmt_" + id).removeClass("deleting");
			return false;
		}
		
		$.postJSON(
			location.protocol + "//" + location.host + location.pathname + "/c" + id + "/remove",
			{ajax: 1, id: id},
			function(data) {
				console.dir(data);
				
				$("#cmt_" + data.id).removeClass("deleting");
				
				if(data.error) {
					return !!alert(data.error);
				}
				
				$("#cmt_" + data.id).addClass("deleted").html("<p>Комментарий удалён.</p>");
			}
		);

		return false;
	},

	after_save: function(data) {
		if(data.error) {
			alert(data.error);
			return false;
		}

		var html = "<div class='thread'>" + data.html + "</div>";
		if(data.pid) {
			$("#Comments #cmt_" + data.pid).parent().append(html);
		} else {
			$("#Comments .thread:last").before(html);
		}

		// $.scrollTo("#cmt_" + data.id, 200, {offset: {left:0, top: -300}}); // не работает, скроллит слишком сильно вниз

		$("#Comments form.reply [name=Comment\\[body\\]]").val("");	// если начали набирать новый комментарий, не дождавшись ответа от сервера, будет лажа. До ответа стирать нельзя - может вернуться ошибка и коммент нужно будет слать заново
		Cmt.re(0, true);
	}
}

$(Cmt.init);
