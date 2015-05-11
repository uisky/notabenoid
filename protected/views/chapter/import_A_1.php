<?php
	/**
	 * @var TextSource $options
	 * @var Chapter $chap
	 */

	$this->pageTitle = "Импортировать текст в перевод {$chap->book->fullTitle}";

	Yii::app()->bootstrap->registerTabs();
?>
<style type="text/css">
	#TextSource_text {height:200px;}
	form.form-hide-errors .error span.help-block {display:none;}
</style>
<script type="text/javascript">
var I = {
	src_type: function(type) {
		$("#form-prepare-text [name=TextSource\\[src_type\\]]").val(type);
		return false;
	}
}
</script>
<h1>Импортировать текст</h1>
<p>
	Перевод: <?=$chap->book->ahref; ?>, <?=$chap->ahref; ?>
</p>
<?php
	if($chap->n_verses != 0) {
		echo "<div class='alert alert-block alert-warning'><strong>Внимание!</strong> В этой главе уже есть оригинальный текст. Если вы импортируете новый оригинал, старый текст будет уничтожен вместе с переводами и комментариями!</div>";
	}
?>
<!-- form method='post' id='form-prepare-text' class="form-inline" action="<?=$chap->getUrl("import"); ?>" enctype="multipart/form-data" -->
<?php
	/** @var TbActiveForm $form */
	$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
		"id" => "form-prepare-text",
		"action" => $chap->getUrl("import"),
		"type" => "horizontal",
		"inlineErrors" => false,
		"htmlOptions" => array(
			"class" => "form-hide-errors",
			"enctype" => "multipart/form-data",
		),
	));
?>
	<input type='hidden' name='TextSource[src_type]' value='1' />

	<div class="tabbable">
		<ul class="nav nav-tabs">
			<li <?=$options->src_type == 1 ? "class='active'" : ""; ?>><a href="#1" data-toggle="tab" onclick='return I.src_type(1)'>Вставить или набрать</a></li>
			<li <?=$options->src_type == 2 ? "class='active'" : ""; ?>><a href="#2" data-toggle="tab" onclick='return I.src_type(2)'>Из файла</a></li>
<?php if(0) : // NOT IMPLEMENTED, TODO Импорт оригинала из HTML ?>
	<li <?=$options->src_type == 3 ? "class='active'" : ""; ?>><a href="#3" data-toggle="tab" onclick='return I.src_type(3)'>Из интернета</a></li>
<?php endif; ?>
		</ul>
		<div class="tab-content">
			<div id="1" class="tab-pane <?=$options->src_type == 1 ? "active" : ""; ?>">
				<div class="control-group">
					<textarea name='TextSource[text]' class='span8' id='TextSource_text'></textarea>
					<p class='help-block'>Пожалуйста, не более 500 килобайт. Тексты большего размера разбейте на отдельные главы.</p>
				</div>
			</div>
			<div id="2" class="tab-pane <?=$options->src_type == 2 ? "active" : ""; ?>">
				<?php echo $form->fileFieldRow($options, "file", array("hint" => "Пожалуйста, только файлы .TXT не тяжелее 500 килобайт. Тексты большего размера разбейте на отдельные главы.")); ?>
				<?php echo $form->dropDownListRow($options, "encoding", Yii::app()->params["encodings"]); ?>
			</div>
<?php if(0) : // NOT IMPLEMENTED, TODO Импорт оригинала из HTML ?>
			<div id="3" class="tab-pane <?=$options->src_type == 3 ? "active" : ""; ?>">
				<?php echo $form->textFieldRow($options, "url", array("placeholder" => "http://", "class" => "span7", "hint" => "Из страницы будет загружено не более 500 КБ. Если вы хотите перевести более длинную страницу, сохраните её себе на диск, откройте в браузере и скопируйте содержимое частями через буфер обмена.")); ?>
			</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
		echo $form->dropDownListRow($options, "chopper", $options->choppers);
	?>

	<div class="form-actions">
		<button type="button" class="btn btn-success" onclick="location.href='<?=$chap->book->url; ?>'">
			<i class="icon-remove icon-white"></i>
			Отмена
		</button>
		<button type="submit" class="btn btn-primary pull-right">
			Далее
			<i class="icon-white icon-arrow-right"></i>
		</button>
	</div>
<?php $this->endWidget(); ?>
