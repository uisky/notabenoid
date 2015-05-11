<style type="text/css">
.debug-model-dump li {
	font-size:11px;
	color:#777;
}
.debug-model-dump li.changed {
	font-weight:bold;
	color:#000;
}
</style>
<ul class='debug-model-dump'>
<?php
	$default = new Book();
	foreach($book->attributes as $k => $v) {
		echo "<li" . ($book->$k != $default->$k ? " class='changed'" : "") . ">";
		echo "{$k} = ";
		if(is_array($v)) echo "[" . join(",", $v) . "]";
		else echo "'{$v}'";
		echo "</li>";
	};
?>
</ul>
IMG:
<?php
//	echo $_SESSION["book_for_edit"];
?>