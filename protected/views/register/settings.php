<?php
	$this->pageTitle = "Настройки сайта";

	Yii::app()->clientScript
		->registerCssFile("/css/jPicker-1.1.6.css")
		->registerScriptFile("/js/jPicker-1.1.6.min.js");

	$user = Yii::app()->user;
?>
<style type='text/css'>
#E td {	padding:10px 10px 20px 0; }
#UserSettings_email {width:400px;}
fieldset {margin-bottom:42px;}
.jPicker {margin-left:5px;}

#nc-controls { float:left; }
#nc-controls .controls {white-space: nowrap;}
#nc-demo {
	margin-left:50px;
	float:left;
}
#nc-demo .comment {margin:6px 0 0 0;}
<?php
	foreach(WebUserIni::$newCommentsSchemes as $id => $scheme) {
		echo $user->ini->getCssComments($id, ".comments.demo-{$id}") . "\n";
	}
?>
</style>

<script type="text/javascript">
function restore() {
	var dflt = {bgcolor:"ffffff", color:"000000", fontsize:13, lineheight: 18};
	for(var k in dflt) {
		var v = dflt[k];
		$("#form-settings [name=ini\\[l\\]\\[" + k + "\\]]").val(v);
	}

}

$(function() {
	$("#nc-controls :radio").click(function(e) {
		var scheme_id = $(this).val();
		$("#nc-demo").attr("class", "comments demo-" + scheme_id);
	});
    $("#UserSettings_old_pass").val('');
    $(".colorpicker").jPicker();
});
</script>

<h1>Настройки сайта</h1>

<?php
	/** @var TbActiveForm $form */
	$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
		"id" => "form-settings",
		"type" => "horizontal",
		"inlineErrors" => false,
	));

	echo $form->errorSummary($model);

	if(!empty($_SERVER["HTTP_REFERER"])) echo CHtml::hiddenField("referer", $_SERVER["HTTP_REFERER"]);
?>

<h3>Изменить пароль:</h3>
<fieldset>
<?php
	echo $form->passwordFieldRow($model, "old_pass");
	echo $form->passwordFieldRow($model, "new_pass");
	echo $form->passwordFieldRow($model, "new_pass2");
?>
</fieldset>

<h3>Внешний вид:</h3>
<p>
	Вы можете настроить цвет фона, текста и размер шрифта на страницах сайта. Это &ndash; экспериментальная функция,
	используйте её на свой страх и риск. Все настройки сохраняются только на этом компьютере.
</p>
<fieldset>
	<div class="control-group">
		<label class="control-label">Цвет фона страниц</label>
		<div class="controls">
			<input type="text" name="ini[l][bgcolor]" value="<?=htmlspecialchars($user->ini["l.bgcolor"]); ?>" class="colorpicker" />
			<button type="button" class="btn" onclick="restore()">Вернуть значения по умолчанию</button>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label">Цвет текста</label>
		<div class="controls">
			<input type="text" name="ini[l][color]" value="<?=htmlspecialchars($user->ini["l.color"]); ?>" class="colorpicker" />
		</div>
	</div>
	<div class="control-group">
		<label class="control-label">Подписи и тайминг</label>
		<div class="controls">
			<select name="ini[l][metascheme]" class="span4">
			<?php
				$A = [0 => "Светлые, сереют под курсором", 1 => "Всегда серые", 2 => "Серые, чернеют под курсором", 3 => "Всегда чёрные"];
				foreach($A as $k => $v) {
					echo "<option value='{$k}'" . ($user->ini["l.metascheme"] == $k ? " selected" : "") . ">{$v}</option>";
				}
			?>
			</select>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label">Размер шрифта</label>
		<div class="controls">
			<input type="text" name="ini[l][fontsize]" value="<?=htmlspecialchars($user->ini["l.fontsize"]); ?>" /> px
		</div>
	</div>
	<div class="control-group">
		<label class="control-label">Высота строки</label>
		<div class="controls">
			<input type="text" name="ini[l][lineheight]" value="<?=htmlspecialchars($user->ini["l.lineheight"]); ?>" /> px
		</div>
	</div>

	<div class="control-group">
		<label class="control-label">Интерфейс перевода</label>
		<div class="controls">
			<select name="ini[t][iface]">
				<?php
					$A = array(0 => "им. Поля Дирака", 1 => "им. Питера Хиггса (тестируется)");
					foreach($A as $k => $v) {
						echo "<option value='{$k}'" . ($user->ini["t.iface"] == $k ? " selected" : "") . ">{$v}</option>";
					}
				?>
			</select>
		</div>
	</div>

	<?php if($user->ini["t.iface"] == 1): ?>
		<div class="control-group" id="nc-controls">
			<label class="control-label">Выделение новых комментариев</label>
			<div class="controls">
				<?php
					foreach(WebUserIni::$newCommentsSchemes as $id => $scheme) {
						echo "<label class='radio'>";
						echo "<input type='radio' name='ini[c][sc]' value='{$id}' " . ($user->ini["c.sc"] == $id ? "checked" : "") . " />";
						echo $scheme["_title"];
						echo "</label>";
					}
				?>
			</div>
		</div>

		<div id="nc-demo" class="comments <?php echo "demo-{$user->ini["c.sc"]}"; ?>">
		<?php
			$comments = array(
				array("text" => "Люк!\nЯ &mdash; твой отец!", "login" => "darth_vader"),
				array("text" => "Без булдырабыз!", "login" => "mintimer"),
				array("text" => "Viva la revolucion!", "login" => "4e"),
				array("text" => "Я этого никогда не говорил.", "login" => "1stein"),
				array("text" => "Мои лучшие друзья &mdash; девушки!.", "login" => "aLmAzIk1997"),
			);

			$comment = new Comment();
			$c = $comments[rand(0, count($comments) - 1)];
			$comment->user_id = -1;
			$comment->author = new User();
			$comment->author->login = $c["login"];
			$comment->body = $c["text"];
			$comment->is_new = true;
			$this->renderPartial("//blog/_comment-1", array("comment" => $comment, "disable_rating" => true));
		?>
		</div>

		<div class="control-group clear">
			<div class="controls">
                <label class="checkbox"><input type="checkbox" name="ini[t][copy]" value="1" <?php if($user->ini["t.copy"] == 1) echo "checked"; ?> />Копировать текст оригинала при добавлении версии перевода</label>
			</div>
		</div>

		<div class="control-group">
			<div class="control-label">Как выделять итоговые варианты перевода:</div>
			<div class="controls">
				<?php
					$A = [0 => "Никак", 1 => "Козявкой в правом верхнем углу", 2 => "Жирным шрифтом"];
					foreach($A as $k => $v) {
						echo "<label class='radio'>";
						echo "<input type='radio' name='ini[t][hlr]' value='{$k}' " . ($k == $user->ini["t.hlr"] ? "checked " : "") . "/> {$v}";
						echo "</label>";
					}
				?>
			</div>
		</div>


	<?php endif; ?>

</fieldset>


<h3>Изменить пол:</h3>
<fieldset>
<?php
	echo $form->radioButtonListInlineRow($model, "sex", array("m" => "мужчина", "f" => "женщина", "x" => "не скажу"));
?>
</fieldset>

<h3>Почтовые голуби:</h3>
<fieldset>
<?php
	echo $form->textFieldRow($model, "email");
?>
<div class="control-group">
<div class="controls">
<?php
	$set_ini = array(
		User::INI_MAIL_PMAIL => "личные сообщения",
		User::INI_MAIL_NOTICES => "оповещения",
		User::INI_MAIL_COMMENTS => "комментарии в ваших постах и ответы на ваши комментарии",
		User::INI_MAIL_NEWS => "важные новости сайта",
	);
	foreach($set_ini as $k => $label) {
		echo "<label class='checkbox'>";
		echo $form->checkBox($model, "set_ini[{$k}]") . " " . $label;
		echo "</label>";
	}
?>
</div></div>
</fieldset>

<h3>Прочее</h3>
<fieldset>
<?php
	echo $form->checkBoxRow($model, "set_ini[" . User::INI_ADDTHIS_OFF . "]");
?>
</fieldset>

<div class="form-actions">
<?php
	echo CHtml::htmlButton("<i class='icon-ok icon-white'></i> Сохранить", array("type" => "submit", "class" => "btn btn-primary")) . " ";
?>

</div>

<?php $this->endWidget(); ?>

