<div class='tools'>
<h5>Резюме</h5>
<dl class='info'>
	<dt>Пол:</dt>
	<dd><?=Yii::app()->params["sex"][$user->sex]; ?></dd>

	<dt>Родной язык:</dt>
	<dd><?=Yii::app()->langs->inf($user->lang); ?></dd>

	<dt>С нами:</dt>
	<dd><?php
		echo "с " . Yii::app()->dateFormatter->formatDateTime($user->cdate, "long", "");
		$d1 = new DateTime();
		$siteAge = $d1->diff(date_create($user->cdate));
		if($siteAge->days > 0) {
			echo " (" . Yii::t("app", "{n}&nbsp;день|{n}&nbsp;дня|{n}&nbsp;дней", $siteAge->days) . ")";
		} else {
			echo " (" . $user->sexy("зарегистрировался", "зарегистрировалась", "зарегистрировалось") . " сегодня)";
		}
		if(!$user->can(User::CAN_LOGIN)) {
			echo "<br>Не является членом клуба. <a href='" . Yii::app()->user->getUrl("invites") . "?who=" . urlencode($user->login) . "'>Пригласить</a>.";
		}
		if($user->invited_by) {
			echo " по приглашению " . $user->invitedBy->ahref;
		}
		?></dd>

	<?php
		$A = array();
		if($user->n_trs > 0) {
			$A[] =
				"<a href='" . $user->getUrl("books") . "'>" .
				"<strong>" . Yii::t("app", "{n} версия перевода|{n} версии перевода|{n} версий перевода", $user->n_trs) . "</strong>" .
				"</a>" .
				" с&nbsp;общим&nbsp;рейтингом&nbsp;<strong>{$user->rate_tFormatted}</strong>";
		}
		if($user->n_comments > 0) {
			$A[] = "<a href='" . $user->getUrl("comments") . "'><strong>" . Yii::t("app", "{n} комментарий|{n} комментария|{n} комментариев", $user->n_comments) . "</strong></a>";
		}
		if($user->n_karma > 0) {
			$A[] = "<a href='" . $user->getUrl("karma") . "'>Карма: {$user->rate_u}</a> (оценок: {$user->n_karma})";
		}

		if(count($A)) {
			echo "<dt>Деятельность:</dt>";
			echo "<dd>" . join("<br />", $A) . "</dd>";
		}
	?>

</dl>
<?php if($user->id == Yii::app()->user->id) { ?>
	<div><i class="icon-pencil"></i> <a href='<?=$user->getUrl("edit"); ?>' class='act'>Редактировать</a></div>
<?php } ?>

<div><i class="icon-envelope"></i> <a href="/my/mail/write?to=<?=$user->login; ?>" class="act">Написать <?=$user->login; ?> личное сообщение</a></div>

</div>