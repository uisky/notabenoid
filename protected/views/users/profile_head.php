<?php
	/**
	* @var User $user
	* @var string $h1 = $user->login
	* @var string $html_insert1 = ""
	*/
?>
<div class="row profile-header">

<div class="span1 profile-header-upic">
<?php
	echo "<img src='{$user->upicUrl}' width='50' height='50' alt='' class='upic";
		if(is_array($user->upic) && $user->upic[0]) {
			echo " active' data-upic='" . $user->id . "." . join(".", $user->upic);
		}
	echo "' />";

	if(Yii::app()->user->id == $user->id) {
		echo "<a href='#upic-modal' data-toggle='modal' title='Загрузить новую аватарку, уииии!'>изменить</a>";
	}
?>
</div>

<div class="span7">

<h1><?php echo $user->login . ($h1 != "" ? ": {$h1}" : ""); ?></h1>
<?=$html_insert1; ?>
<ul class="nav nav-tabs">
<?php
	foreach($this->submenu as $action => $label) {
		echo "<li" . ($this->action->id == $action ? " class='active'" : "") . "><a href='" . $user->getUrl($action) . "'>{$label}</a></li>";
	}
?>
</ul>

</div>

</div>

<?php if(Yii::app()->user->id == $user->id): ?>
<div id="upic-modal" class="modal hide">
    <form method="post" enctype="multipart/form-data" class="form-inline" action="<?=$user->getUrl("upic"); ?>">
        <div class="modal-header">
            <a class="close" data-dismiss="modal">×</a>
            <h3>Загрузить новую аватарку, уииии!</h3>
        </div>
        <div class="modal-body">
            <p>Аватар &ndash; ваше второе лицо. Он показывается в вашем профиле и рядом с каждым вашим комментарием. Люди ассоциируют его с вами.</p>
			<p>Пожалуйста, выберите файл в формате JPEG, PNG или GIF не тяжелее 2 мегабайт.</p>
			<p class="i"><input type="file" name="img" /></p>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Загрузить</button>
        </div>
    </form>
</div>
<?php endif; ?>