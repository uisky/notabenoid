<?php
/**
 * @var CActiveDataProvider $mail_dp
 * @var integer $folder
 * */

/** @var Mail[] $mail */
$mail = $mail_dp->getData();

Yii::app()->getClientScript()->registerScriptFile("/js/mail.js", CClientScript::POS_END);

$this->pageTitle = "Почта: " . Mail::$folders[$folder];
?>
<style type="text/css">
#Mail td {white-space:nowrap;}
#Mail td.s {width:100%; white-space: normal;}
#Mail tr.new td {font-weight:bold;}

#mass-actions {display:none; }
</style>
<h1>Почта: <?=Mail::$folders[$folder]; ?></h1>

<?php
if($mail_dp->getTotalItemCount() == 0):
	echo "<div class='alert alert-info'>";
	echo "У вас нет " . ($_GET["new"] ? "непрочитанных " : "") . "сообщений в папке &laquo;" . Mail::$folders[$folder] . "&raquo;";
	echo "</div>";
	return;
endif
?>

<?php $this->widget('bootstrap.widgets.TbPager', array("pages" => $mail_dp->pagination, "header" => "<div class='pagination pagination-centered' style='margin-bottom:0'>")); ?>

<form method="post" id="form-mass" class="form-inline">
<table id="Mail" class="table table-stripped table-condensed">
<thead>
<tr>
	<th><input type='checkbox' id='cb-check-all'></th>
	<th>Дата</th>
	<th>Собеседник</th>
	<th>Тема</th>
</tr>
</thead>
<?php
	foreach($mail as $msg) {
		$class = "";
		if(!$msg->seen) $class = " class='new'";
		echo "<tr{$class}>";
		echo "<td class='c'><input type='checkbox' name='id[]' value='{$msg->id}' /></td>";
		echo "<td class='d'>" . Yii::app()->dateFormatter->formatDateTime($msg->cdate, "medium", "short") . "</td>";
		echo "<td class='b'>{$msg->buddy->ahref}</td>";
		echo "<td class='s'><a href='{$msg->url}'>" . ($msg->subj == "" ? "Без темы" : $msg->subj) . "</a></td>";
		echo "</tr>";
	}
?>
</table>
<div id="mass-actions">
	Выбранные:
	<select name="act">
		<option value="seen">считать прочитанными</option>
		<option value="rm">удалить</option>
	</select>

	<button type="submit" class="btn"><i class="icon-fire"></i> Ok</button>
</div>
</form>
<?php $this->widget('bootstrap.widgets.TbPager', array("pages" => $mail_dp->pagination, "header" => "<div class='pagination pagination-centered' style='margin-bottom:0'>")); ?>
