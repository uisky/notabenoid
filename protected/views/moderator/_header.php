<ul class="nav nav-tabs">
<?php
	foreach($this->areas as $k => $v) {
		echo "<li" . ($k == $this->area ? " class='active'" : "") . "><a href='/moderator/{$k}'>{$v}</a></li>";
	}
?>
</ul>