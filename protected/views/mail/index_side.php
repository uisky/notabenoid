<?php
	/**
	 * @var integer $folder
	 */
?>
<div class="tools">
<h5>Почта</h5>
	<ul class="nav nav-pills">
	<?php
		foreach(Mail::$folders as $id => $title) {
			echo "<li" . ($folder == $id ? " class='active'" : "") . "><a href='?folder={$id}'>{$title}</a></li>";
		}
	?>
	</ul>

	<form method="get">
		<input type="hidden" name="folder" value="<?=$folder; ?>" />
		<label class="checkbox">
			<input type="checkbox" name="new" value="1" onclick="this.form.submit()" <?php if($_GET["new"]) echo "checked"; ?>/>
			только непрочитанные
		</label>
	</form>

	<p>
		<a href="/my/mail/write" class="btn btn-success"><i class="icon-envelope icon-white"></i> Написать письмо</a>
	</p>
</div>