<?php
/**
 * @var User $user
 * @var String $img
 * @var int $img_w
 * @var int $img_h
 */

	Yii::app()->clientScript->registerScriptFile("/js/jquery.Jcrop.min.js")
	->registerCssFile("/css/jquery.Jcrop.min.css");

	$Img = $_SESSION["upicEditor"]["img"];
?>
<script type="text/javascript">
$(function() {
    var $form = $("#cropData"),
            x1 = $form.find("[name=x]").val(), y1 = $form.find("[name=y]").val(),
            x2 = x1 + $form.find("[name=w]").val(), y2 = x1 + $form.find("[name=h]").val();

	function showPreview(coords) {
        var rx = 50 / coords.w;
        var ry = 50 / coords.h;

        $('#preview img').css({
            width: Math.round(rx * <?=$Img["w"]; ?>) + 'px',
            height: Math.round(ry * <?=$Img["h"]; ?>) + 'px',
            marginLeft: '-' + Math.round(rx * coords.x) + 'px',
            marginTop: '-' + Math.round(ry * coords.y) + 'px'
        });

		$form.find("[name=x]").val(coords.x);
		$form.find("[name=y]").val(coords.y);
		$form.find("[name=w]").val(coords.w);
		$form.find("[name=h]").val(coords.h);
	}

	$("#ava").Jcrop({
        onChange: showPreview,
        onSelect: showPreview,
		setSelect: [x1, y1, x2, y2],
		minSize: [50, 50],
		aspectRatio: 1
	});
})
</script>

<style type="text/css">
	#preview { width:50px; height:50px; overflow:hidden; border-radius: 5px; margin:0 0 15px;}
	#preview img {max-width:none;}
</style>

<h1>Вырежьте квадратную аватарку из картинки</h1>

<img src="/i/tmp/upiccut/<?=$Img["name"]; ?>" width="<?=$Img["w"]; ?>" height="<?=$Img["h"]; ?>" id="ava" alt="" />
