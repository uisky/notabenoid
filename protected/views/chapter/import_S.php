<?php
	/**
	 * @var ImportSubsForm $options
	 * @var Chapter $chap
	 */

	$this->pageTitle = "Импортировать субтитры";
?>
<style type="text/css">
form.form-hide-errors .error span.help-block {display:none;}
</style>
<h1>Импортировать субтитры</h1>
<p>
	Перевод: <?=$chap->ahref; ?>
</p>
<?php
	if($chap->n_verses != 0) {
		echo "<div class='alert alert-block alert-warning'><strong>Внимание!</strong> В этой главе уже есть оригинальный текст. Если вы импортируете новый оригинал, старый текст будет уничтожен вместе с переводами и комментариями!</div>";
	}

	/** @var TbActiveForm $form */
	$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
		"id" => "form-import",
		"type" => "horizontal",
		"inlineErrors" => false,
		"action" => $chap->getUrl("import_subs"),
		"htmlOptions" => array(
			"class" => "form-hide-errors",
			"enctype" => "multipart/form-data",
		),
	));

	echo $form->errorSummary($options);

	echo $form->fileFieldRow($options, "src", array("hint" => "Не более 1 мегабайта, пожалуйста"));
	echo $form->dropDownListRow($options, "format", array("srt" => "SRT"));
	echo $form->dropDownListRow($options, "encoding", Yii::app()->params["encodings"]);
?>
<div class="form-actions">
	<button type="submit" class="btn btn-primary">
		<i class="icon-ok icon-white"></i>
		Импортировать
	</button>
	<button type="button" class="btn btn-success" onclick="location.href='<?=$chap->book->url; ?>'">
		<i class="icon-remove icon-white"></i>
		Отмена
	</button>
</div>
<?php
	$this->endWidget();
?>