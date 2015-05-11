<?php
/**
 * @var User $model
 * @var Controller $this
 */
$this->pageTitle = "Регистрация";

function getAttrMinMaxLength($model, $attr, $default=[1, 32]) {
	foreach($model->rules() as $rule) {
		if($rule[0] == $attr && $rule[1] == "length") {
			return [$rule["min"], $rule["max"]];
		}
	}
	return $default;
}
?>
<style type="text/css">
.captcha {
	display:block;
	cursor:pointer;
}
</style>

<h1>Регистрация</h1>

<p>
	Как хорошо, что вы решили зарегистрироваться! После этой нехитрой процедуры вы сможете участвовать в переводах, как
	добавляя свои версии, так и оценивая чужие, общаться в блоге, создавать свои переводы. Ваша жизнь кардинально
	изменится.
</p>

<?php
	/** @var TbActiveForm $form */
	$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
		"id" => "form-register",
		"type" => "horizontal",
		"inlineErrors" => false,
		"focus" => array($model, "verifyCode"),
	));
	CHtml::$afterRequiredLabel = "";
?>
<div class="control-group <?=$model->hasErrors("verifyCode") ? " error" : ""; ?>">
	<?php echo $form->labelEx($model, "verifyCode", array("class" => "control-label required", "style" => "margin-top:50px;")); ?>
	<div class="controls">
		<?php $this->widget(
			"CCaptcha",
			array(
				"clickableImage" => true,
				"showRefreshButton" => false,
				"imageOptions" => array("title" => "показать другую картинку", "class" => "captcha"),
			)); ?>
		<?php echo $form->textField($model, "verifyCode"); ?>
		<?php echo $form->error($model, 'verifyCode'); ?>
		<p class="help-block" title="На самом деле, это необходимо, чтобы убедиться, что вы умеете читать">Защита от роботов: введите буквы, которые видите на картинке, в любом регистре.</p>
	</div>
</div>
<?php
$mm = getAttrMinMaxLength($model, "login", [2, 16]);
echo $form->textFieldRow(
	$model,
	"login",
	[
		"class" => "span6",
		"hint" => "Латинские буквы, арабские цифры, интернациональный символ подчёркивания, от {$mm[0]} до {$mm[1]} штук."
	]
);
?>
<div class="control-group <?=($model->hasErrors("pass") or $model->hasErrors("pass2")) ? " error" : ""; ?>">
	<label class="control-label required">Пароль, 2 раза:</label>
	<div class="controls">
		<?php echo $form->passwordField($model, "pass", array("class" => "span3")); ?>
		<?php echo $form->passwordField($model, "pass2", array("class" => "span3 offset5")); ?>
		<?php echo $form->error($model, "pass"); ?>
		<?php echo $form->error($model, "pass2"); ?>
		<p class="help-block">
			<?php
			$mm = getAttrMinMaxLength($model, "pass, pass2", [5, 32]);
			?>
			От <?=$mm[0]; ?> до <?=$mm[1]; ?> любых символов.
		</p>
	</div>
</div>
<?php
	echo $form->textFieldRow($model, "email", array("class" => "span6", "hint" => "Мы не будем отправлять вам спам."));
	echo $form->radioButtonListInlineRow($model, "sex", array("m" => "мужчина", "f" => "женщина"), array("hint" => "Чтобы знать, как к вам обращаться."));
	echo $form->dropDownListRow($model, "lang", Yii::app()->langs->select());
	echo $form->checkBoxRow($model, "tos");
?>
<div class="form-actions">
<?php
	echo CHtml::htmlButton("<i class='icon-ok icon-white'></i> Зарегистрироваться", array("type" => "submit", "class" => "btn btn-primary")) . " ";
?>
</div>

<?php $this->endWidget(); ?>
