<div class='tools'>
	<h5>Настройки</h5>

	<?php
//		echo "<ul>";
//		foreach(array("format", "algorithm", "skip_neg", "untr", "enc", "crlf") as $k) {
//			echo "<li>{$k} = '<b>{$options->$k}</b>'</li>";
//		}
//		echo "</ul>";
	?>

    <p><strong>Готово:</strong></p>
    <p><?php
		if($chap->n_vars == 0 || $chap->n_verses == 0) echo "&mdash;";
		else {
			$procent = floor($chap->d_vars / $chap->n_verses * 10000) / 100;
			$classes = array(100 => "progress-danger", 80 => "progress-warning", 60 => "progress-success", 40 => "", 20 => "progress-info");
			foreach($classes as $p => $class) {
				if($procent >= $p) {
					break;
				}
			}
			$class = "progress-success";

			echo "<div class='progress progress-striped {$class}' style='margin-bottom:2px;'>";

			printf("<div class='bar' style='width: %d%%;'></div>", $procent);

			echo "<div class='text'>";
			printf("<span title='фрагментов: %d, переведено: %d'>%0.02f%%</span>", $chap->n_verses, $chap->d_vars, $procent);
			if($chap->d_vars > 0 and $chap->n_vars > 0) printf(" <abbr title='Коэффициент Плюрализма: среднее количество вариантов перевода каждого фрагмента'>КП</abbr> = %.01f", $chap->n_vars / $chap->d_vars);
			echo "</div>";

			echo "</div>";
		}

		?></p>


	<form method="get" action="<?=$chap->getUrl("ready"); ?>" class="form-vertical">
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

		<div class="control-group">
			<div class="controls">
				<label class="checkbox">
					<input type="hidden" name="skip_neg" value="0" />
					<input type="checkbox" name="skip_neg" value="1" <?php if($options->skip_neg) echo " checked"; ?> />
					Пропускать варианты с отрицательным рейтингом
				</label>
			</div>
		</div>
		<?php if($chap->can("gen_untr")): ?>
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
		<?php else: ?>
		<p style="color:#888" title="Подставлять оригинальный текст вместо непереведённых фрагментов можно, если вы являетесь владельцем перевода, либо он закрыт для общего доступа, либо он готов более, чем на 95%.">
			Непереведённые фрагменты будут пропущены.
		</p>
		<?php endif; ?>

		<button type="submit" class="btn btn-primary btn-mini">Обновить</button>
	</form>

	<a href="<?=$chap->getUrl("download?format=t&enc=UTF-8"); ?>"><i class="icon-download-alt"></i> Скачать как .txt файл</a><br />
  <?php
  parse_str($_SERVER['QUERY_STRING'], $queryString);
  $readyLink = 'ready?' . http_build_query($queryString);
  ?>
	<a href="<?=$chap->getUrl($readyLink);?>" title="Чтобы поделиться ей, нажмите правой кнопкой мыши и выберите &quot;Скопировать адрес ссылки&quot;"><i class="icon-share"></i> Ссылка на эту страницу</a><br />
	<a href="<?=$chap->book->url; ?>"><i class="icon-list"></i> Оглавление перевода</a><br />
	<a href="<?=$chap->getUrl(); ?>"><i class="icon-fire"></i> Интерфейс перевода</a>
</div>
