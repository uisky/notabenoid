<?php
	/**
	 * @var Book $book
	 * @var BookBanReason $reason
	 * @var Controller $this
	 */

	$this->pageTitle = "Заблокировать перевод";
?>
<h1>Заблокировать перевод</h1>

<?php
	/** @var TbActiveForm $form  */
	$form = $this->beginWidget("bootstrap.widgets.TbActiveForm", ["method" => "post", "type" => "horizontal"]);
	echo $form->errorSummary($reason);
?>
<div class="control-group">
	<label class="control-label">Название организации</label>
	<div class="controls">
		<?php echo $form->textField($reason, "title", ["class" => "span6"]); ?>
	</div>
</div>
<div class="control-group">
	<label class="control-label">URL</label>
	<div class="controls">
		<?php echo $form->textField($reason, "url", ["class" => "span6"]); ?>
	</div>
</div>
<div class="control-group">
	<label class="control-label">E-mail</label>
	<div class="controls">
		<?php echo $form->textField($reason, "email", ["class" => "span6"]); ?>
	</div>
</div>
<div class="control-group">
	<label class="control-label">Сообщение</label>
	<div class="controls">
		<?php echo $form->textArea($reason, "message", ["class" => "span6", "rows" => 6]); ?>
	</div>
</div>
<div class="form-actions">
	<button type="submit" class="btn btn-danger"><i class="icon-ban-circle icon-white"></i> Забанить</button>
	<button type="button" class="btn btn-success"><i class="icon-ok icon-white"></i> Разбанить</button>
</div>
<?php $this->endWidget(); ?>