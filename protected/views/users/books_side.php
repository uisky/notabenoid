<?php
	/**
	 * @var array[] $orderOptions
	 * @var integer $order
	 * @var array[] $statusOptions;
	 * @var integer $status
	 */
?>
<div class='tools'>
	<h5>Сортировка</h5>
	<form method="get">
	<ul class="nav nav-list"><?php
		foreach($orderOptions as $i => $o) {
			echo "<li" . ($i == $order ? " class='active'" : "") . ">";
			echo "<a href='?order={$i}'>";
			echo $o[1];
			echo "</a>";
			echo "</li>";
		}
		foreach($statusOptions as $k => $v) {
			echo "<li><label class='radio'><input type='radio' name='status' value='{$k}' " . ($status == $k ? "checked" : "") . " onclick='this.form.submit()'/>{$v[1]}</label></li>";
		}
	?>
	</ul>
	</form>
</div>