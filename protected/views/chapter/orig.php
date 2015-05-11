<?php
	/**
	 * @var Chapter $chap
	 * @var GenOptions $options
	 */
	$this->pageTitle = "Скачать оригинальные субтитры {$chap->book->fullTitle}: {$chap->title}";
?>
<h1>Скачать оригинальные субтитры <?php echo "{$chap->book->s_title}: {$chap->title}"; ?></h1>

<form method="get" action="<?=$chap->getUrl("orig_download"); ?>" class="form-horizontal">
	<div class="control-group">
		<label class="control-label">Формат:</label>
		<div class="controls">
			<select name="format">
				<?php
				foreach(GenOptions::$format_options[$chap->book->typ] as $k => $v) {
					echo "<option value='{$k}'" . ($k == $options->format ? " selected" : "") . ">{$v}</option>";
				}
				?>
			</select>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label">Кодировка:</label>
		<div class="controls">
			<select name="enc">
				<?php
				foreach(Yii::app()->params["encodings"] as $k => $v) {
					echo "<option value='{$k}'" . ($k == $options->enc ? " selected" : "") . ">{$v}</option>";
				}
				?>
			</select>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label">Перенос строки:</label>
		<div class="controls">
			<select name="crlf">
				<?php
				foreach(GenOptions::$crlf_options as $k => $v) {
					echo "<option value='{$k}'" . ($k == $options->crlf ? " selected" : "") . ">{$v}</option>";
				}
				?>
			</select>
		</div>
	</div>

	<div class="form-actions">
		<button type="submit" class="btn btn-primary">
			<i class="icon-download-alt icon-white"></i> Скачать
		</button>
		<a href="<?=$chap->book->url; ?>" class="btn">К оглавлению</a>
		<a href="<?=$chap->url; ?>" class="btn">Перевод этой главы</a>
	</div>

</form>