<div class="tools">
    <h5>Стройплощадка</h5>
	<p>Этот блог доступен:</p>
	<?php
		foreach(WebUser::getRoles("betatest") as $i => $login) {
			if($i) echo ", ";
			echo "<a href='/users/go?login={$login}'>{$login}</a>";
		};
	?>
</div>