<?php
	/**
	 * @var Chapter $chap
	 * @var GenOptions $options
	 * @var array $authors
	 */
	$this->pageTitle = "Скачать перевод {$chap->book->fullTitle}: {$chap->title}";
?>
<h1>Скачать перевод <?php echo "{$chap->book->fullTitle}: {$chap->title}"; ?></h1>

<form method="get" action="<?=$chap->getUrl("download"); ?>" class="form-horizontal">
	<div class="control-group">
		<label class="control-label">Использовать:</label>
		<div class="controls">
			<?php
				foreach(GenOptions::$algorithm_options as $k => $v) {
					echo "<label class='radio'>";
					echo "<input type='radio' name='algorithm' value='$k'" . ($k == $options->algorithm ? " checked" : "") ." />";
					echo $v;
					echo "</label>";
				}
			?>
		</div>
	</div>

	<div class="control-group">
		<div class="controls">
			<label class="checkbox">
				<input type="hidden" name="skip_neg" value="0" />
				<input type="checkbox" name="skip_neg" value="1" <?php if($options->skip_neg) echo " checked"; ?> />
				Пропускать варианты с отрицательным рейтингом
			</label>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label">От автора:</label>
		<div class="controls">
			<select name="author_id">
				<option value="0">Не важно</option>
				<optgroup label="* * * * * * * * * *"></optgroup>
				<?php
					foreach($authors as $author) {
						echo "<option value='{$author["id"]}'" . ($options->author_id == $author["id"] ? " selected" : "") . ">{$author["login"]}</option>";
					}
				?>
			</select>
		</div>
	</div>

	<?php if($chap->d_vars < $chap->n_verses): ?>
	<div class="control-group">
		<label class="control-label">Непереведённые фрагменты:</label>
		<div class="controls">
			<?php
			foreach(GenOptions::$untr_options as $k => $v) {
				echo "<label class='radio'>";
				echo "<input type='radio' name='untr' value='$k'" . ($k == $options->untr ? " checked" : "") ." />";
				echo $v;
				echo "</label>";
			}
			?>
		</div>
	</div>
	<?php endif; ?>

	<?php if($chap->book->typ == "S"): ?>
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
	<?php elseif($chap->book->typ == "A"): ?>
		<input type="hidden" name="format" value="h" />
	<?php endif; ?>

	<div class="form-actions">
		<button type="submit" class="btn btn-primary">
			<i class="icon-download-alt icon-white"></i> Скачать
		</button>
		<a href="<?=$chap->book->url; ?>" class="btn">К оглавлению</a>
		<a href="<?=$chap->url; ?>" class="btn">Перевод этой главы</a>
	</div>

</form>