<?php
	/**
	 * @var Orig $orig
	 * @var boolean $ajax
	 */

	if(!$ajax) {
		echo "<style type='text/css'>\n";
		echo ".orig-editor textarea {width:500px; height:200px;}\n";
		echo "</style>\n";

		$title = ($orig->isNewRecord ? "Создать" : "Редактировать") . " субтитр";
		$this->pageTitle = $orig->chap->book->fullTitle . ": " . $orig->chap->title . ": " . $title;
		echo "<h1>{$title}</h1>";
	}
?>
<div class='orig-editor'>
	<form id='form-orig' method='post' action='<?=$orig->getUrl("edit"); ?>' class="form-inline">
		<div class="control-group">
			<label>Начало:</label> <input type="text" name="Orig[t1]" value="<?=htmlspecialchars($orig->t1); ?>" class="span2" placeholder="ЧЧ:ММ:СС.ддд" maxlength="12" />
			<label>Конец:</label> <input type="text" name="Orig[t2]" value="<?=htmlspecialchars($orig->t2); ?>" class="span2" placeholder="ЧЧ:ММ:СС.ддд" maxlength="12" />
		</div>

		<div class="control-group">
			<textarea name='Orig[body]'><?=htmlspecialchars($orig->body); ?></textarea>
		</div>

		<div class="control-group">
			<button type='submit' class='btn btn-mini btn-primary'>Сохранить</button>
			<?php if(!$orig->isNewRecord): ?><button type='button' class='btn btn-mini btn-danger remove'>Удалить</button><?php endif; ?>
			<button type='button' class='btn btn-mini cancel' onclick="<?=$ajax ? "T.editing_stop()" : "location.href='{$orig->url}'"; ?>">Отмена</button>
		</div>
	</form>
</div>
<script type="text/javascript">
	setTimeout(function() { $("#form-orig textarea").focus(); }, 100);
	<?php if(!$orig->isNewRecord): ?>$("#form-orig .remove").click(T.o_rm);<?php endif; ?>
</script>