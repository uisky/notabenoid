<?php
	/**
	 * @var User $user
	 * @var String $img
	 * @var int $img_w
	 * @var int $img_h
	 */

	$Img = $_SESSION["upicEditor"]["img"];

	if($Img["w"] < $Img["h"]) {
		$ini_x = 0;
		$ini_w = $ini_h = $Img["w"];
		$ini_y = (int) (($Img["h"] - $ini_w) / 2);
	} else {
		$ini_y = 0;
		$ini_h = $ini_w = $Img["h"];
		$ini_x = (int) (($Img["w"] - $ini_h) / 2);
	}

?>
<div class="tools">
	<h5>Что получится</h5>

	<div id="preview">
        <img src="/i/tmp/upiccut/<?=$Img["name"]; ?>" width="<?=$Img["w"]; ?>" height="<?=$Img["h"]; ?>" alt="" />
	</div>

    <form method="post" id="cropData">
        <input type="hidden" name="x" value="<?=$ini_x; ?>" /> <input type="hidden" name="y" value="<?=$ini_y; ?>" />
        <input type="hidden" name="w" value="<?=$ini_w; ?>" /> <input type="hidden" name="h" value="<?=$ini_h; ?>" />
        <button type="submit" class="btn btn-primary"><i class="icon-ok icon-white"></i> Готово</button>
        <a href="?do=cancel" class="btn btn-inverse"><i class="icon-remove icon-white"></i> Отмена</a>
    </form>
</div>
