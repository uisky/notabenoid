<?php
	/**
	 * @var Orig $orig
	 * @var boolean $ajax
	 */

	if(!$ajax) {
		echo "<style type='text/css'>\n";
		echo ".orig-editor textarea {width:500px; height:200px;}\n";
		echo "</style>\n";

		$title = ($orig->isNewRecord ? "Создать" : "Редактировать") . " фрагмент оригинала";
		$this->pageTitle = $orig->chap->book->fullTitle . ": " . $orig->chap->title . ": " . $title;
		echo "<h1>{$title}</h1>";
	}
?>
<div class='orig-editor'>
	<form id='form-orig' method='post' action='<?=$orig->getUrl("edit"); ?>' class="form-inline">
		<div class="control-group">
			<label class="control-label">Порядковый номер:</label>
			<input type="text" name="Orig[ord]" value="<?=htmlspecialchars($orig->ord); ?>" class="span1" />
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