<?php
/**
 * @var SearchFilter $filter
 */
?>
<div class="tools">
	<h5>Анонсы переводов</h5>

	<?php
	/** @var TbActiveForm $form */
	$form = $this->beginWidget('bootstrap.widgets.TbActiveForm', array(
		"method" => "get",
		"action" => "/announces",
		"id" => "form-filter",
		"type" => "vertical",
		"inlineErrors" => false,
	));
	?>
	<div class="control-group">
		<label class="control-label">Из раздела каталога:</label>
		<div class="controls">
			<select name="cat">
				<option value="0">Не важно</option>
				<?php
					$tree = CHtml::listData(Category::model()->indented_list()->findAll(), "id", "title");
					$o = array();
					echo CHtml::listOptions($filter->cat, $tree, $o);
				?>
			</select>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label">Язык оригинала:</label>
		<div class="controls">
			<select name="s_lang">
				<option value="0">Не важно</option>
				<?=Yii::app()->langs->options(Langs::FORM_INF, $filter->s_lang); ?>
			</select>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label">Язык перевода:</label>
		<div class="controls">
			<select name="t_lang">
				<option value="0">Не важно</option>
				<?=Yii::app()->langs->options(Langs::FORM_INF, $filter->t_lang); ?>
			</select>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label">Тема:</label>
		<div class="controls">
			<select name="topic">
				<option value="0">Не важно</option>
				<?php
					echo CHtml::listOptions($filter->topic, Yii::app()->params["blog_topics"]["announce"], $o);
				?>
			</select>
		</div>
	</div>

	<div class="control-group">
		<div class="controls">
			<label class="radio"><input type="radio" value="1" name="ready" <?=$filter->ready==1 ? "checked" : ""; ?>/>Готовые на 100%</label>
			<label class="radio"><input type="radio" value="2" name="ready" <?=$filter->ready==2 ? "checked" : ""; ?>/>Не готовые</label>
			<label class="radio"><input type="radio" value="0" name="ready" <?=$filter->ready==0 ? "checked" : ""; ?>/>Любые</label>

			<label class="checkbox"><input type="checkbox" value="1" name="gen" <?=$filter->gen ? "checked" : ""; ?>/>Доступные для скачивания</label>
			<label class="checkbox"><input type="checkbox" value="1" name="tr" <?=$filter->tr ? "checked" : ""; ?>/>Доступные для перевода</label>
		</div>
	</div>

	<div class="control-group">
		<button type="submit" class="btn btn-primary"><i class="icon-search icon-white"></i> Показать</button>
	</div>

	<?php $this->endWidget(); ?>

	<p>
		<a href="/announces/rss<?php if($filter->topic) echo "?topic={$filter->topic}"; ?>">RSS</a>
	</p>
</div>