var CE = {
	init: function() {
		console.log(location.hash);
		var res;
		if(res = /#ed=(\d+)/.exec(location.hash)) {
			console.dir(res);
			CE.ed.go(res[1]);
		}
	},
	
	ed: {
		save_html: "",
		id: -1,

		go: function(id) {
			if(this.id == id) { this.cancel(); return false; }
			if(this.id != -1) this.cancel();
			this.id = id;

			var $tr = $("#Chapters #c_" + id);
			this.save_html = $tr.html();

			$("#Chapters th.dat").text("порядок");
			$("#Chapters td.dat").each(function() {
				$(this).attr("data-cdate", $(this).html());
				$(this).html($(this).attr("data-ord"));
			})
			
			var data = {
				ord: $tr.children("td.dat").attr("data-ord"),
				title: $tr.children("td.t").children("a").html(),
				status: $tr.attr("data-status")
			}
			for(var ac in GLOBALS.ac_areas_chap) data[ac] = $tr.attr("data-" + ac);

			// монструозная конструкция генерации html редактора
			var html =
				"<td colspan='7'><div class='form'>" + 
				"<form method='post' id='chap-rm' action='/book/" + Book.id + "/" + id + "/remove'><input type='hidden' name='really' value='1' /></form>" +
				"<form method='post' id='chap-ed' action='/book/" + Book.id + "/" + id + "/edit'>" +
				"<div class='row2' ><input type='text' name='ord' style='width:70px;' /><label class='l'>порядок</label></div>" +
				"<div class='row2'><select name='status'>" + options(GLOBALS.translation_statuses) + "</select><label class='l'>состояние</label></div>" +
				"<div class='row' ><input type='text' name='title' class='wide' /><label class='l'>название</label></div>" +
				"<div class='row'><fieldset><legend>особые права доступа</legend>";

			// особые права доступа
			var special_chap_id = -1;
			for(var area in GLOBALS.ac_areas_chap) {
				var $check_tr = $("#Chapters tr[id][data-" + area + "!='']");
				if($check_tr.length > 0) {
					special_chap_id = $check_tr.attr("id").substr(2);
					break;
				}
			}
			if(special_chap_id != -1 && special_chap_id != id) {
				html += "<p>Можно указать особые права доступа только для одной главы перевода. Такая глава уже есть, вы можете <a href='#' onclick='return CE.ed.go(" + special_chap_id + ")'>отредактировать её</a>.</p>";
			} else {
				for(var ac in GLOBALS.ac_areas_chap) {
					html += "<div class='b'><select name='" + ac + "'><option value=''>как в переводе</option>" +
							options(GLOBALS.ac_roles) +
							"</select><label class='l'>" + GLOBALS.ac_areas_chap[ac] + "</label></div>";
				}
			}

			html += "</fieldset></div>" + 
					"<div class='row'><input type='submit' value='сохранить' class='btn' /> ";
			if(id != 0) html += "<input type='button' value='удалить' onclick='CE.ed.rm()' class='btn' />";
			html += "<input type='button' value='отмена' onclick='CE.ed.cancel()' class='btn' /></div>" +
					"</form>" +
					"</div></td><td style='background:#000; border-right:1px solid black;'></td>";

			// Создаём редактор и инициализируем его данными главы					
			$tr.html(html).addClass("editing");
			for(var field in data) $("#chap-ed [name='" + field + "']").val(data[field]);
			$("#chap-ed [name='ac_read'] option[value='a']").remove();	// бессмысленно устанавливать для главы ac_read = 'a'
			$("#chap-ed [name='title']").focus();
			
			return false;
		},
		cancel: function() {
			if(this.id == -1) return false;
			if(this.id == 0) {
				$("#info_empty").show();
				$("#Chapters #c_0").remove();
			} else {
				$("#Chapters #c_" + this.id).html(this.save_html).removeClass("editing");
			}
			
			$("#Chapters th.dat").text("добавлено");
			$("#Chapters td.dat").each(function() {
				$(this).html($(this).attr("data-cdate"));
			})

			this.id = -1;
			this.save_html = "";
			
			return false;
		},
		rm: function() {
			if(!confirm("Вы уверены, что хотите удалить эту главу? Будут удалены также все версии перевода!")) return false;
			
			$("#chap-rm").submit();
		},
		/* where: -1 - в начале, 1 - в конце */
		add: function(where) {
			if(this.id != -1) this.cancel();
			
			var html = "<tr id='c_0' data-ac_read='' data-ac_gen='' data-ac_rate='' data-ac_comment='' data-ac_tr=''><td colspan='8'></td></tr>";
			if(where == 0) {
				console.log("add(where = 0)");
				var ord = 0;
				$("#Chapters").append(html);
				$("#info_empty").hide();
				console.log("hide");
			} else if(where == -1) {
				var ord = parseInt($("#Chapters tr[id]:first td.dat").attr("data-ord")) - 10;
				$("#Chapters tr:first").after(html);
			} else if(where == 1){
				var ord = parseInt($("#Chapters tr[id]:last td.dat").attr("data-ord")) + 10;
				$("#Chapters tr:last").after(html);
			}
			
			this.go(0);
			$("#chap-ed [name='ord']").val(ord);
			
			return false;
		}
	}
};
$(CE.init);
