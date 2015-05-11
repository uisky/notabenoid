<?php
	/**
	 * @var Orig $orig
	 */
?>
<form id='form-orig' method='post' action='<?=$orig->getUrl("edit"); ?>' class="form-inline">
    <input type="hidden" name="Orig[ord]" value="<?=htmlspecialchars($orig->ord); ?>" class="span1" />

	<div class="control-group">
		<input type="text" name="Orig[t1]" value="<?=htmlspecialchars($orig->t1); ?>" class="span2" placeholder="ЧЧ:ММ:СС.ддд" maxlength="12" /> &rarr;
		<input type="text" name="Orig[t2]" value="<?=htmlspecialchars($orig->t2); ?>" class="span2" placeholder="ЧЧ:ММ:СС.ддд" maxlength="12" />
	</div>

	<div class="control-group">
		<textarea name='Orig[body]'><?=htmlspecialchars($orig->body); ?></textarea>
	</div>

	<div class="control-group">
		<button type='submit' class='btn btn-mini btn-primary'>Сохранить</button>
		<button type='button' class='btn btn-mini cancel' onclick="<?=$ajax ? "" : "location.href='{$orig->url}'"; ?>">Отмена</button>
	</div>
</form>
