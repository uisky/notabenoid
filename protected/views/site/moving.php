<style type="text/css">
	#bell {width:160px; position:absolute; top:56px; left:20px; border:1px solid #777;}
	#board {position:absolute; top:140px; left:200px; right:20px; bottom:0; cursor: text;}
	#board p {position:absolute; max-width:200px; font-weight:bold; cursor:default;}
	#write {display:none; position:absolute;}
</style>
<script type="text/javascript">
	$(function() {
		$("#board").click(function(e) {
			if(e.srcElement.id != "board") return;

			$("#write").css({top: e.offsetY, left: e.offsetX}).show();
			$("#write [name=x]").val(e.offsetX);
			$("#write [name=y]").val(e.offsetY);
			$("#write .t").focus();
		});
		$("p").add("h1").add("img").draggable();
	});
</script>

<img src="/i/moving/bell01.jpg" id="bell" alt="" />

<div id="shield">
	<h1>Переезд</h1>
	<p>
		Нотабеноид переезжает на новый движок. Всё снова заработает не позднее вечера воскресенья, но скорее всего получится запуститься значительно раньше, ещё днём.
	</p>
</div>
<noindex>
<div id="board">
	<?php
		$limit_words = 50;
		$words = Yii::app()->db->createCommand("SELECT * FROM moving ORDER BY cdate DESC LIMIT {$limit_words}")->queryAll();
		foreach($words as $n => $word) {
			$op = ($limit_words - $n) / $limit_words;
			$color = "rgba(" . substr($word["color"], 1, -1) . ", {$op})";
			echo "<p style='top: {$word["y"]}px; left: {$word["x"]}px; color:{$color}'>{$word["t"]}</p>";
		}
	?>
	<form id="write" method="post" class="form-inline">
		<input type="hidden" name="x" /><input type="hidden" name="y" />
		<input type="text" name="t" class="t" maxlength="120" placeholder="Выскажитесь, не держите в себе." />
		<button type="submit" class="btn btn-success">&rarr;</button>
	</form>
</div>
</noindex>