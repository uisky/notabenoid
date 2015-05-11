<?php
	/**
	 * @var Mail $message
	 * @var User[] $buddies
	 */
?>
<div class="tools">
	<h5>Написать письмо</h5>
	<?php
		if(count($buddies) > 0) {
			echo "Вы уже переписывались с:";
			echo "<ul>";
			foreach($buddies as $buddy) {
				echo "<li><a href='/my/mail/write?to={$buddy->login}'>{$buddy->login}</a></li>";
			}
			echo "</ul>";
		}
	?>
</div>