<?php
/**
 * @var CActiveDataProvider $groups_dp
 * @var User $user
 * @var array[] $statusOptions;
 * @var integer $status
 */

	Yii::app()->clientScript
		->registerScriptFile("/js/profile.js")->registerCssFile("/css/profile.css?3")
		->registerScript("profile", "Profile.uid = {$user->id};", CClientScript::POS_HEAD);

	$this->pageTitle = $user->login . ": переводы";

	$this->renderPartial("profile_head", array("user" => $user));
?>

<?php
if($groups_dp->totalItemCount):
	/** @var GroupMember $groups */
	$groups = $groups_dp->getData();
?>
<div class="pagination in-h2">
	<?php $this->widget('bootstrap.widgets.TbPager', array("pages" => $groups_dp->pagination, "maxButtonCount" => 5, "header" => false, "footer" => false)); ?>
</div>
<h2><?php echo "Участвует" . yii::t("app", " в {n} переводе| в {n} переводах", $groups_dp->totalItemCount) . ":"; ?></h2>


<table id="people" class="table table-condensed table-striped">
<thead>
<tr>
	<th></th>
	<?php if($order == 4): ?><th>Дата</th><?php endif; ?>
	<th style='witdh:100%'>Перевод</th>
	<th>Готово</th>
	<th>Версий</th>
	<th>Рейтинг</th>
	<th>Средний рейтинг</th>
</tr>
</thead>
<tbody>

<?php
	foreach($groups as $group) {
		echo "<tr>";
		echo "<td>";
		echo "<i class='ac_read {$group->book->ac_read}'></i> ";
		echo "</td>";

		if($order == 4) {
			echo "<td>" . Yii::app()->dateFormatter->formatDateTime($group->since, "short", "") . "</td>";
		}

		echo "<td>";
		if($group->status == GroupMember::MODERATOR) echo "<i class='icon-briefcase' title='Модератор'></i> ";
		echo "<a href='{$group->book->url}' title='" . Yii::app()->params["book_types"][$group->book->typ] . " " . Yii::app()->langs->from_to($group->book->s_lang, $group->book->t_lang) . "'>{$group->book->fullTitle}</a>";
		if($group->book->owner_id == $user->id) echo " (создатель)";
		echo "</td>";

		echo "<td>{$group->book->ready}</td>";

		echo "<td>";
		if($group->n_trs != 0 && $group->book->ac_read == "a" && $group->book->ac_trread == "a") echo "<a href='" . $user->getUrl("translations/{$group->book->id}") . "'>";
		echo $group->n_trs;
		if($group->book->n_vars != 0) {
			printf("&nbsp;<small>(%d%%)</small> ", $group->n_trs / $group->book->n_vars * 100);
		}
		if($group->n_trs != 0) echo "</a>";
		echo "</td>";

		echo "<td>";
		echo $group->rating;
		echo "</td>";

		echo "<td" . ($group->rating < 0 ? " class='negative'" : "") . ">";
		if($group->n_trs > 0) {
			printf("%.2f", $group->rating / $group->n_trs);
		}
		echo "</td>";

		echo "</tr>";
	}
?>
</tbody>
</table>
<?php
	$this->widget('bootstrap.widgets.TbPager', array("pages" => $groups_dp->pagination));
else:
?>
	<p class="alert alert-block alert-info">
		<?php echo $user->login . " " . $statusOptions[$status][2];	?>.
	</p>
<?php
endif;
?>

<?php
if($user->id == Yii::app()->user->id):
	?>
<p><a href='/book/0/edit' class='btn btn-success'>Создать перевод</a></p>
<?php
endif;
?>