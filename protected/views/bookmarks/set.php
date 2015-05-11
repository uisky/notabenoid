<?php
	/**
	 * @var Bookmark $bm
 	 */
?>
<div class="modal-header" style='padding-bottom:0;'>
	<a class="close" data-dismiss="modal">×</a>
	<h3><?=$bm->isNewRecord ? "Поставить закладку" : "Закладка"; ?></h3>
</div>
<div class="modal-body">
	<form method="post" action="/my/bookmarks/set">
		<input type="hidden" name="book_id" value="<?=$bm->book_id; ?>" />
		<input type="hidden" name="orig_id" value="<?=$bm->orig_id; ?>" />
		<div class="control-group">
			<label class="control-label">Пометка:</label>
			<input type="text" name="note" class="span5" value="<?=htmlspecialchars($bm->note); ?>"/>
		</div>
		<div class="control-group">
			<label class="checkbox">
				<input type="checkbox" name="watch" value="1" <?php if($bm->watch) echo "checked"; ?> /> Оповещать меня об изменении статуса перевода и добавлении новых глав
			</label>
		</div>
		<div class="control-group">
			<button type="submit" class="btn btn-primary"><i class="icon-ok icon-white"></i> Сохранить</button>
			<?php if(!$bm->isNewRecord): ?><button type="button" class="btn btn-danger" onclick="if(confirm('Серьёзно?')) $('#bookmark-set-form-rm').submit()"><i class="icon-ban-circle icon-white"></i> Удалить</button><?php endif; ?>
			<button type="button" class="btn" data-dismiss="modal"><i class="icon-remove"></i> Отмена</button>
		</div>
	</form>

	<form method="post" action="/my/bookmarks/remove" id="bookmark-set-form-rm">
		<input type="hidden" name="book_id" value="<?=$bm->book_id; ?>" />
		<input type="hidden" name="orig_id" value="<?=$bm->orig_id; ?>" />
	</form>
</div>
<script type="text/javascript">
(function() {
	$("#bookmark-set [name=note]").focus();
})()
</script>