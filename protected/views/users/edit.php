<?php
	$this->pageTitle = "Редактор профиля";
?>

<style type="text/css">
#UserEditor_bdate_d {width:30px;}
#UserEditor_bdate_m {margin:0px 5px; width:120px;}
#UserEditor_bdate_y {width:60px;}
#UserEditor_bio {width:400px; height:200px;}
#upic_preview {margin:5px 2px;}
#upic_preview img { display:block; float:left; margin:0 15px 0 0;}

</style>

<h1>Редактор профиля</h1>

<?php
	/** @var TbActiveForm $form */
	$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
		"id" => "form-edit",
		"type" => "horizontal",
		"enableClientValidation" => false,
		"focus" => array($model, "name"),
	));
?>

<?php
	foreach(array("name" => "b", "icq" => "a", "skype" => "a", "lj" => "a", "url" => "b") as $attr => $cssClass) {
		echo $form->textFieldRow($model, $attr);
	}

	echo $form->dropDownListRow($model, "country_id", Yii::app()->params["countries"]);
	echo $form->textFieldRow($model, "city");
?>
<div class="control-group">
	<?php echo $form->labelEx($model, "bdate", array("class" => "control-label")); ?>
	<div class="controls">
	<?php
		echo $form->textField($model, "bdate_d");
		echo $form->dropDownList($model, "bdate_m", Yii::app()->params["month_acc"]);
		echo $form->textField($model, "bdate_y");
		echo $form->error($model, "bdate");
	?>
	</div>
</div>
<?php
	echo $form->textAreaRow($model, "bio");
?>

<div class="form-actions">
	<?php
		echo CHtml::htmlButton("<i class='icon-ok icon-white'></i> Сохранить", array("type" => "submit", "class" => "btn btn-primary")) . " ";
		echo CHtml::htmlButton("<i class='icon-remove icon-white'></i> Отмена", array("onclick" => "location.href='" . Yii::app()->user->url . "'", "class" => "btn btn-success")) . " ";
		echo CHtml::htmlButton("<i class='icon-ban-circle icon-white'></i> Удалить аккаунт", array("onclick" => "location.href='" . Yii::app()->user->getUrl("delete") . "'", "class" => "btn btn-danger"));
	?>
</div>

<?php
	$this->endWidget();
?>