function check_post() {
	// Проверяем, помечена ли хоть одна рубрика
	var ok = false;
	$('form input').each(function () {
		if((this.type == 'checkbox' || this.type == 'radio') && this.checked) {
			ok = true;
			return false;
		}
	});

	if(!ok) {
		alert("Выберите хотя бы одну рубрику");
		return false;
	}

	if(!confirm("Опубликовать пост?")) return false;

	// Стираем нахер слой с imguploader, чтобы не отправлять во всей форме то, что там могло остаться в <input type='file'>
	$('#imguploader').remove();

	return true;
}


function rm_post() {
	if(!confirm("Удалить этот пост?")) return false;

	document.ed.act.value = 'rm';
	document.ed.title.value = '';
	document.ed.body.value = '';
	document.ed.submit();
}





/* Кросс-пост */
var ohmblog_xpost_savehtml = '';
function ohmblog_xpost() {
	ohmblog_xpost_savehtml = $('#ohmblog_xpost').html();
	$('#ohmblog_xpost').load('/sys/ohmblog/xpost.php');

	return false;
}

function ohmblog_xpost_cancel() {
	$('#ohmblog_xpost').html(ohmblog_xpost_savehtml);
	return false;
}

function ohmblog_xpost_add() {
	$.post(
		'/sys/ohmblog/xpost.php',
		{act: 'add', hosting:document.ed.ohmblog_xpost_h.value, login:document.ed.ohmblog_xpost_l.value, pass:document.ed.ohmblog_xpost_p.value, community:document.ed.ohmblog_xpost_c.value},
		function(data, tstatus) {
			$('#ohmblog_xpost').html(data);
		}
	);
}

function ohmblog_xpost_rmb(id) {
	if(!confirm("Вы уверены, что хотите удалить эту запись из списка ваших внешних блогов?")) return false;
	$.post(
		'/sys/ohmblog/xpost.php',
		{act: 'rm', id:id},
		function(data, tstatus) {
			$('#ohmblog_xpost').html(data);
		}
	);
	return false;
}



/* imguploader */
String.prototype.trim = function() {
	return this.replace(/^\s+|\s+$/g,"");
}
String.prototype.ltrim = function() {
	return this.replace(/^\s+/,"");
}
String.prototype.rtrim = function() {
	return this.replace(/\s+$/,"");
}

function imguploader_go() {

	//starting setting some animation when the ajax starts and completes
	$("#loading").ajaxStart(function() {
		$('#imguploader_btnok').attr('disabled', true);
		$(this).show();
	}).ajaxComplete(function() {
		$('#imguploader_btnok').attr('disabled', false);
		$(this).hide();
	});

	$.ajaxFileUpload (
		{
			url: '/sys/ohmblog/imgupload.php', 
			secureuri: false,
			fileElementId: 'imguploader_f',
			dataType: 'json',
			success: function (data, status) {
				var html;

				if(typeof(data.error) != 'undefined')
				{
					alert(data.error);
					return;
				}

				document.ed.body.value = document.ed.body.value.rtrim() + "\n\n<img src='" + data.img + "' width='" + data.w + "' height='" + data.h + "' border='1'>\n\n";
				document.ed.body.scrollTop = document.ed.body.scrollHeight;

				html = "<p><input type='text' value='" + data.th + "'><br/><img src='" + data.th + "' width='" + data.th_w + "' height='" + data.th_h + "'></p>";
				$('#imguploader_Fotos').append(html);

				document.ed.body.focus();
			},
			error: function (data, status, e) {
				alert(e);
			}
		}
	);

	return false;
}
