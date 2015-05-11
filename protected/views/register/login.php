<?php
	$this->pageTitle = "Вход";
?>

<h1>Вход на сайт</h1>

<?php
	/** @var TbActiveForm $form */
	$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
		"id" => "form-login",
		"type" => "horizontal",
		"inlineErrors" => false,
		"focus" => array($model, "login"),
	));

	echo $form->errorSummary($model);
?>

<div class="control-group">
	<?php echo $form->labelEx($model, 'login', array("class" => "control-label")); ?>
	<div class="controls">
		<?php echo $form->textField($model, 'login'); ?>
		<span class="help-inline">
			<a href="/register/">зарегистрироваться</a>
		</span>
	</div>
</div>

<div class="control-group">
	<?php echo $form->labelEx($model,'pass', array("class" => "control-label")); ?>
	<div class="controls">
		<?php echo $form->passwordField($model,'pass'); ?>
		<span class="help-inline">
			<a href="/register/remind">забыли?</a>
		</span>
	</div>
</div>

<div class="control-group">
	<div class="controls">
		<label class="checkbox">
			<?php echo $form->checkBox($model,'remember'); ?>
			Запомнить меня на сайте
		</label>
	</div>
</div>

<div class="form-actions">
	<?php echo CHtml::htmlButton("<i class='icon-ok icon-white'></i> Войти", array("type" => "submit", "class" => "btn btn-primary")) . " "; ?>
</div>

<?php $this->endWidget(); ?>
