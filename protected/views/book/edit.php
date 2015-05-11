<?php
	/**
	* @var Book $model
	*/

	$this->pageTitle = $book->isNewRecord ? "Создать перевод" : ("Свойства перевода \"{$book->fullTitle}\"");

	$this->book->registerJS();
?>
<style type='text/css'>
#BookEditor_descr {height:200px;}
#img_preview a, #img_preview img {display:block; margin:5px 0;}

#facecontrol-change-msg {display:none;}

#ac_details {margin:20px 0 0 27px;}
#ac_details td, #ac_details th {padding:3px 6px; border-bottom:1px solid #aaa;}
#ac_details th {white-space:nowrap;}
#ac_details th.a {text-align:left;}
#ac_details td {width:76px; text-align:center;}
#ac_details td.hint {width:600px; text-align:left; color:#fff; }
#ac_details td.hint a {color:#fff;}
#ac_details tr:hover td.hint {color:#777;}
#ac_details tr:hover td.hint a {color:#444;}
#ac_details td.void {background:#000;}

#ac_presets {}
#ac_presets .row {}
#ac_presets input {vertical-align:baseline;}
#ac_presets label {font-size:16px;}
#ac_presets .hint {margin:2px 0 16px 26px;}
</style>
<script type="text/javascript">
var E = {
	topics_html: {<?php
		$i = 0;
		foreach(array_keys(Yii::app()->params["book_types"]) as $typ) {
			if($i != 0) echo ",\n";
			echo "{$typ}: \"";

			if(is_array(Yii::app()->params["book_topics"][$typ])) {
				echo addcslashes(
				// $form->checkBoxList($model, "topics", $topics, array("separator" => " ", "template" => "<p>{input} {label}</p>")
					CHtml::checkBoxList(
						"topics",
						"",
						Yii::app()->params["book_topics"][$typ],
						array("separator" => " ", "template" => "<p>{input} {label}</p>")
					),
					"\"'"
				);
			}

			echo "\"";
			$i++;
		}
	?>},

	presets: {
		1: {ac_read: "a", ac_gen: "a", ac_rate: "a", ac_comment: "a", ac_tr: "a", ac_blog_r: "a", ac_blog_c: "a", ac_blog_w: "a", ac_announce: "m", ac_chap_edit: "m", facecontrol: 0},
		2: {ac_read: "a", ac_gen: "a", ac_rate: "a", ac_comment: "a", ac_tr: "g", ac_blog_r: "a", ac_blog_c: "a", ac_blog_w: "g", ac_announce: "m", ac_chap_edit: "m", facecontrol: 1},
		3: {ac_read: "a", ac_gen: "a", ac_rate: "g", ac_comment: "g", ac_tr: "g", ac_blog_r: "a", ac_blog_c: "g", ac_blog_w: "g", ac_announce: "m", ac_chap_edit: "m", facecontrol: 1},
		4: {ac_read: "g", ac_gen: "g", ac_rate: "g", ac_comment: "g", ac_tr: "g", ac_blog_r: "g", ac_blog_c: "g", ac_blog_w: "g", ac_announce: "m", ac_chap_edit: "m", facecontrol: 2}
	},

	init: function() {
		$("#img_preview a").click(function() {
			var html = "<img src='" + $(this).attr("href") + "' alt='' />";
			$(this).replaceWith(html);
			return false;
		});

		$("#form-edit [name=BookEditor\\[ac_read\\]]").click(E.ac_read_ch);
//		E.ac_read_ch();

		$("#ac_presets [name=preset]").click(E.preset);
	},

	ac_read_ch: function() {
		var v = $(this).val();
		d = {a: false, g: false, m: false};
		if(v == "g") d = {a: true, g: false, m: false};
		else if(v == "m") d = {a: true, g: true, m: false};
		else if(v == "o") d = {a: true, g: true, m: true};
		for(var i in d) {
			$("#form-edit :radio[value=" + i + "]").not("#form-edit [name=BookEditor\\[ac_read\\]]").attr("disabled", d[i]);
		}
	},

	facecontrol_ch: function() {
		var facecontrol = $("#BookEditor_facecontrol").val();
		if(facecontrol == 0) {
			$("#ac_details input[type=radio][value=g]").attr("disabled", true).each(function() {
				var $r = $(this);
				if($r.attr("checked")) {
					var sel = "#ac_details [name=" + $r.attr("name").replace("[", "\\[").replace("]", "\\]") + "]";
					$(sel + "[value=m]").prop("checked", true);
					$(sel + "[value=a]").prop("checked", true);

				}
			});
			if(Book.facecontrol != 0) $("#facecontrol-change-msg").show(100);
		} else {
			$("#ac_details input[type=radio][value=g]").attr("disabled", false);
			if(Book.facecontrol != 0) $("#facecontrol-change-msg").hide(100);
		}
	},

	typ_switch: function() {
		// рубрик не будет, ура! :)
		return true;
		var typ = $("#BookEditor_typ").val();
		var html = E.topics_html[typ];
		if(!typ || html == "") {
			$("#BookEditor_topics").parent().hide();
		} else {
			$("#BookEditor_topics").parent().show();
		}
		$("#BookEditor_topics_boxes").html(E.topics_html[typ]);
	},

	preset: function() {
		var preset = $("#ac_presets [name=preset]:checked").val();
		var P = E.presets[preset];
		for(var field in P) {
			if(field == "facecontrol") {
				$("#form-edit [name=BookEditor\\[facecontrol\\]]").val(P[field]);
			}
			var $radio = $("#form-edit [name=BookEditor\\[" + field + "\\]]");

			$radio.each(function() {
				if(this.value == P[field]) $(this).click();
			})
		}
		E.facecontrol_ch();
	},

	rm: function() {
		if(!confirm("Вы абсолютно уверены, что хотите удалить этот перевод? Одним движением мышки вы сейчас можете уничтожить труд десятков людей!")) return;

		$("#form-rm").submit();
	}
}
$(E.init);
</script>

<h1>Свойства перевода</h1>

<form id="form-rm" method="post" action="<?=$model->url("remove"); ?>"><input type="hidden" name="really" value="1"/></form>

<?php
	/** @var TbActiveForm $form */
	$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
		"id" => "form-edit",
		"type" => "horizontal",
		"inlineErrors" => false,
		"htmlOptions" => array(
			"enctype" => "multipart/form-data",
		),
	));

	echo $form->errorSummary($model);
?>

<div class="control-group <?=$model->hasErrors("typ") ? " error" : ""; ?>">
	<?php echo $form->labelEx($model, "typ", array("class" => "control-label required")); ?>
	<div class="controls">
	<?php
		if($model->isNewRecord) {
			echo $form->dropDownList($model, "typ", Yii::app()->params["book_types"], array("onchange" => "E.typ_switch()"));
			echo "<p class='help-block'><b>Внимание!</b> после создания перевода изменить его тип не получится!</p>";
		} else {
			echo "<input type='text' value='" . Yii::app()->params["book_types"][$model->typ] . "' disabled='disabled' />";
			echo "<p class='help-block' title='А мы предупреждали'>Изменить тип перевода уже нельзя.</p>";
		}
	?>
	</div>
</div>
<?php
	echo $form->dropDownListRow($model, "s_lang", Yii::app()->langs->select());
	echo $form->textFieldRow($model, "s_title", array("class" => "span6"));
	echo $form->dropDownListRow($model, "t_lang", Yii::app()->langs->select());
	echo $form->textFieldRow($model, "t_title", array("class" => "span6"));
	echo $form->textAreaRow($model, "descr", array("class" => "span6", "hint" => "Здесь можно использовать HTML-теги"));

	$topics = Yii::app()->params["book_topics"][$model->typ];
	if(!is_array($topics)) $topics = array();
	// echo $form->checkBoxListInlineRow($model, "topics", $topics); // <-- этому ряду нужен какой-нибудь ID
?>
<div class="control-group <?=$model->hasErrors("new_img") ? " error" : ""; ?>">
	<?php echo $form->labelEx($model, "new_img", array("class" => "control-label")); ?>
	<div class="controls">
	<?php
		if($model->img[0]) {
			echo "<div id='img_preview'>";
			echo $model->imgTag;
			echo "<label class='checkbox'>" . $form->checkBox($model, "rm_img") . " удалить</label>";
			echo "</div>";
		}
		echo $form->fileField($model, "new_img");
		echo $form->error($model, "new_img");
	?>
	</div>
</div>










<?php
	$ac = array(
		"ac_read" =>       array("читать<sup>*</sup>", "кто может в принципе зайти на любую страницу перевода; можно указать для каждой главы индивидуально"),
		"ac_gen" =>        array("скачивать результат<sup>*</sup>", "можно указать для каждой главы индивидуально"),
		"ac_rate" =>       array("оценивать перевод<sup>*</sup>", "можно указать для каждой главы индивидуально"),
		"ac_comment" =>    array("комментировать перевод<sup>*</sup>", "можно указать для каждой главы индивидуально"),
		"ac_tr" =>         array("переводить<sup>*</sup>", "добавлять новые версии перевода; можно указать для каждой главы индивидуально"),

		"ac_blog_r" =>     array("читать блог", "если выбрать &laquo;никто&raquo; то ссылка на блог вообще исчезнет из меню перевода"),
		"ac_blog_c" =>     array("комментировать блог", "оставлять комментарии в блоге перевода"),
		"ac_blog_w" =>     array("писать в блог", "писать посты в блоге перевода"),

		"ac_announce" =>   array("анонсировать", "размещать анонсы перевода в <a href='/announce/'>соответствующем разделе</a>."),
		"ac_chap_edit" =>  array("редактировать оригинал", "добавлять/изменять главы, импортировать и редактировать оригинал"),
		"ac_book_edit" =>  array("редактировать описание перевод", "изменять название, описание, языки перевода, загружать новую картинку"),
		"ac_membership" => array("управлять группой", "рассматривать заявки на вступление в группу перевода, удалять из группы, приглашать в группу"),
	);
	$who = array("a" => "все", "g" => "группа", "m" => "модераторы", "o" => "никто");
	$who3 = array("g" => "группа", "m" => "модераторы", "o" => "никто");
	$who2 = array("m" => "модераторы", "o" => "никто");
?>
<h2>Права доступа</h2>

<div id="ac_presets">
	<div>
		<label class="radio"><input type="radio" name="preset" value="1" id="preset_1"/> Полностью открытый перевод</label>
		<p class="hint">
			Переводят, оценивают, обсуждают все; Можно создать перевод, назначить пару толковых модераторов и забить.
		</p>
	</div>

	<div>
		<label class="radio"><input type="radio" name="preset" value="2" id="preset_2" /> Перевод группой</label>
		<p class="hint">
			Группа переводчиков переводят, а все остальные - оценивают и обсуждают. Ещё есть модераторы, которые занимаются добавлением новых глав и анонсами.
			Они же рассматривают заявки на вступление в группу. Хороший вариант, если у вас сколотилась компания переводчиков, но вы недостаточно круты,
			чтобы наплевать на общественное мнение, и вы рады видеть новых людей в ваших рядах.
		</p>
	</div>

	<div>
		<label class="radio"><input type="radio" name="preset" value="3" id="preset_3"/> Перевод уверенной в себе группой</label>
		<p class="hint">
			Переводят, оценивают, обсуждают только члены группы. Простые смертные могут только скачать перевод. Членство в группе - по заявкам, которые рассматривают модераторы.
			Отличный выбор для создания идеального перевода группой единомышленников.
		</p>
	</div>

	<div>
		<label class="radio"><input type="radio" name="preset" value="4" id="preset_4"/> Закрытый перевод</label>
		<p class="hint">
			Весь перевод доступен только группе переводчиков. Участие в группе - строго по приглашениям.
			Если вы хотите перевести что-то для себя - это ваш выбор.
		</p>
	</div>

</div>

<h2>Подробности:</h2>
<?php
	echo $form->dropDownListRow(
		$model, "facecontrol",
		array(Book::FC_OPEN => "нет группы", Book::FC_CONFIRM => "после подтверждения модераторами", Book::FC_INVITE => "только по приглашению модераторов"),
		array("onchange" => "E.facecontrol_ch()")
	);

	if($model->facecontrol != Book::FC_OPEN):
?>
		<div id="facecontrol-change-msg" class="alert alert-block alert-warning">
			<a class="close" data-dismiss="alert">×</a>
			<h4 class="alert-heading">Внимание!</h4>
            Вы собираетесь отменить группу перевода. То, что раньше могли делать только члены группы, смогут делать все, а сама
			группа будет распущена, впрочем, модераторы и забаненные останутся в своём прежнем статусе. Статистика участия каждого переводчика также будет сохранена.
		</div>
<?php
	endif;
?>
<table id="ac_details" class="table">
<thead><tr>
	<th>что могут:</th>
	<th><?php echo join("</th><th>", $who); ?></th>
	<td class="hint"></td>
</tr></thead>
<?php
	foreach($ac as $role => $title) {
		echo "<tr>\n";
		echo "\t<th class='a'>{$title[0]}</th>\n";

		if($role == "ac_chap_edit" || $role == "ac_book_edit" || $role == "ac_membership") {
			$w = $who2;
			echo "<td>-</td><td>-</td>";
		} elseif($role == "ac_announce") {
			$w = $who3;
			echo "<td>-</td>";
		} else {
			$w = $who;
		}

		// echo "<td>" . $form->radioButtonList($model, $role, $w, array("template" => "{input}", "separator" => "</td><td>", "uncheckValue" => null)) . "</td>\n";
		foreach($w as $val => $t) {
			echo "<td><input type='radio' name='BookEditor[{$role}]' value='{$val}' " . ($model->$role == $val ? "checked" : "") . "/></td>";
		};

//		echo "<td class='hint'>{$title[1]}</td>";
		echo "</tr>\n";
	}
?>
</table>
<p class="help-block">
<sup>*</sup> эти права можно также указать индивидуально для каждой главы.
</p>

<div class="form-actions">
	<?php
		echo CHtml::htmlButton("<i class='icon-ok icon-white'></i> Сохранить", array("type" => "submit", "class" => "btn btn-primary")) . " ";
		if(!$model->isNewRecord) echo CHtml::htmlButton("<i class='icon-ban-circle icon-white'></i> Удалить", array("onclick" => "E.rm()", "class" => "btn btn-danger")) . " ";
		echo CHtml::htmlButton("<i class='icon-remove icon-white'></i> Отмена", array("onclick" => "location.href='" . ($model->isNewRecord ? Yii::app()->user->url() : $model->url) . "'", "class" => "btn btn-success"));
	?>
</div>
<?php $this->endWidget(); ?>
