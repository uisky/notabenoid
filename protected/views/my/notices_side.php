<?php
	/**
	 * @var integer[] $new_ids
	 */
?>
<div class="tools">
	<h5>Оповещения</h5>
	<ul class="nav nav-pills">
		<li <?=$_GET["new"] == 1 ? "" : "class='active'"; ?>><a href="/my/notices">все</a></li>
		<li <?=$_GET["new"] == 1 ? "class='active'" : ""; ?>><a href="?new=1">только непрочитанные</a></li>
	</ul>

	<form method="post" action="/my/notices_rmseen">
		<input type="hidden" name="really" value="1" />
		<?php
			if(is_array($new_ids)) foreach($new_ids as $id) echo "<input type='hidden' name='x[]' value='{$id}' />";
		?>
		<button type="submit" class="btn"><i class="icon-remove"></i> Удалить все прочитанные</button>
	</form>
</div>