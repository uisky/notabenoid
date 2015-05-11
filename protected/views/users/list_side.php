<?php
/**
 * @var integer n_users
 * @var integer n_books
 * @var integer n_orig
 * @var integer n_tr
 */

/**
* DEPRECATED - use Yii::t()
*/
function RusEnding($n, $n1, $n2, $n5) {
		if($n >= 11 and $n <= 19) return $n5;
		$n = $n % 10;
		if($n == 1) return $n1;
		if($n >= 2 and $n <= 4) return $n2;
		return $n5;
	}
?>
<style type='text/css'>
	#Stats {line-height:24px; color:#555;}
	#Stats b {font-size:20px; white-space:nowrap; color:#000;}
</style>

<div class='tools' id='Stats'>
	<?php
		$age = time() - mktime(16, 57, 0, 6, 25, 2008);
		$NotabenoidAge = floor($age / (60 * 60 * 24));
	?>
	<h5>Сухие цифры</h5>

	За <b><?=number_format($NotabenoidAge, 0, ',', ' '); ?></b> <?=RusEnding($NotabenoidAge, "день", "дня", "дней"); ?> существования Нотабеноида,
	<b><?=number_format($n_users, 0, ',', ' '); ?></b> его <?=RusEnding($n_users, "пользователь", "пользователя", "пользователей"); ?> создали
	<b><?=number_format($n_books, 0, ',', ' '); ?></b> <?=RusEnding($n_books, "перевод", "перевода", "переводов"); ?>,
	состоящих из <b><?=number_format($n_orig, 0, ',', ' '); ?></b> <?=RusEnding($n_orig, "фрагмента", "фрагментов", "фрагментов"); ?>,
	и предложили <b><?=number_format($n_tr, 0, ',', ' '); ?></b> <?=RusEnding($n_tr, "вариант", "варианта", "вариантов"); ?> их перевода.
</div>

<div class='tools'>
	<h5>Найти человека</h5>
	<form method="get" action="/users/go" class="form-inline">
		Ник: <input type='text' name='login' size='25' class='span2' />
		<input type='submit' value='&raquo;' class='btn' />
	</form>
</div>