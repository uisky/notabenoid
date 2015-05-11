<?php
	/**
	 * @var CActiveDataProvider $orig_dp
	 * @var Chapter $chap
	 * @var TrFilter $filter
	 * @var integer $show
	 * @var string $show_user
	 * @var string $to
	 * @var string $tt
	 */

	$filters = array(
		0 => "Всё",
		1 => "Непереведённое",
		7 => "С 2 и более версиями перевода",
		3 => "С комментариями",
		4 => "С новыми комментариями",
		2 => "От переводчика",
		5 => "Оригинал содержит",
		6 => "Перевод содержит",
	);

	Yii::app()->getClientScript()
		->registerCssFile("/css/translate.css?13")
		->registerScriptFile("/js/translate.js?23")
		->registerScriptFile("/js/jquery.scrollTo.js")
		->registerScriptFile("/js/jquery.elastic.mod.js")
		->registerScriptFile("/js/ff_comments.js?3");

	Yii::app()->bootstrap->registerModal();

	$this->pageTitle = "Перевод " . $chap->book->fullTitle . ": " . $chap->title;

	/** @var Orig[] $orig */
	$orig =  $orig_dp->getData();
?>

<h1><?=$chap->book->ahref; ?>: <?=$chap->title; ?></h1>

<div class="btn-toolbar" id='toolbar-main'>
	<div class="btn-group">
		<a href="<?=$chap->getUrl("go?nach=prev&ord={$chap->ord}"); ?>" class="btn btn-small" title="Предыдущая глава"><i class="icon-arrow-left"></i></a>
	</div>
	<div class="btn-group">
		<a href="<?=$chap->book->url; ?>" class="btn btn-small"><i class="icon-list"></i> Оглавление</a>
		<a class="btn btn-small dropdown-toggle" data-toggle="dropdown" href="#" onclick="T.loadChapters()"><span class="caret"></span></a>
		<ul class="dropdown-menu" id="chapter-list">
			<li><a href="<?=$chap->book->getUrl("members"); ?>">Переводчики</a></li>
			<li><a href="<?=$chap->book->getUrl("blog"); ?>">Блог</a></li>
		</ul>
	</div>
    <div class="btn-group">
        <a href="<?=$chap->getUrl("go?nach=next&ord={$chap->ord}"); ?>" class="btn btn-small" title="Следующая глава"><i class="icon-arrow-right"></i></a>
    </div>

	<div class="btn-group">
		<a href="#" onclick="return T.dict.show()" class="btn btn-small" accesskey="V"><i class="icon-book"></i> Словарь</a>
		<a href="#filter-modal" data-toggle="modal" class="btn btn-small">
			<i class="icon-filter"></i> Фильтр:
			<?php
				if($show == 2) echo "от переводчика {$show_user}";
				else echo mb_strtolower($filters[$show]);
			?>
		</a>
		<?php if($chap->book->can("chap_edit")): ?>
			<a href="<?=$chap->getUrl("0/edit"); ?>" class='btn btn-small'><i class='icon-plus-sign'></i> Добавить фрагмент</a>
        	<?php if($chap->book->typ == "S"): ?>
				<a href="#timeshift-modal" data-toggle="modal" class='btn btn-small'><i class='icon-time'></i> Тайминг</a>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<div class="btn-group switchiface">
		<a href="<?=$chap->getUrl("switchiface"); ?>" class="btn btn-small"><i class="icon-thumbs-up"></i></a>
	</div>

	<div class="btn-group pull-right" style='vertical-align: top'>
		<div id='progress-info'>
		<?php
			if($chap->n_verses == 0) $procent = 0;
			else $procent = floor($chap->d_vars / $chap->n_verses * 1000) / 10;

			echo "<div class='progress progress-striped progress-success'>";
			printf("<div class='bar' style='width: %d%%;'></div>", $procent);
			printf(
				"<div class='text'><a href='%s' title='Скачать результат.\nФрагментов: %d, вариантов: %d, разных: %d'>Готово: %0.01f%%, скачать</a></div>",
				$chap->getUrl("ready"), $chap->n_verses, $chap->n_vars, $chap->d_vars, $procent
			);

			echo "</div>";
		?>
		</div>
	</div>
</div>
<script type="text/javascript">
	T.setStats(<?php echo "{$chap->n_vars}, {$chap->d_vars}, {$chap->n_verses}"; ?>);
</script>

<?php $this->widget('bootstrap.widgets.TbPager', array("pages" => $orig_dp->pagination, "header" => "<div class='pagination pagination-centered'>")); ?>

<?php
	if($orig_dp->totalItemCount == 0):
		if($show == 0 || $chap->n_verses == 0) {
			echo "<p class='alert alert-block'>В эту часть перевода ещё не загрузили оригинальный текст.";
			if($chap->book->can("chap_edit")) echo " Не желаете ли <a href='" . $chap->getUrl("import") . "'>сделать это сейчас</a>?";
			echo "</p>";
		} else {
			echo "<p class='alert alert-info'>Ничего не найдено. <a href='{$chap->url}'>Показать весь перевод.</a></p>";
		}
	else:
		if(!$chap->book->can("trread")) {
			echo "<div class='alert alert-danger'>Владелец перевода установил такие права доступа, что вы не можете просматривать чужие версии перевода здесь.</div>";
		}
?>

<table id="Tr" class="translator">
	<thead>
	<tr>
		<th style='border-top-left-radius: 10px;'>#</th>
		<th>Оригинал (<?=Yii::app()->langs->Langs[$chap->book->s_lang][Langs::FORM_INF]; ?>)</th>
		<th></th>
		<th>Перевод (<?=Yii::app()->langs->Langs[$chap->book->t_lang][Langs::FORM_INF]; ?>)</th>
		<th style='border-top-right-radius: 10px;'></th>
	</tr>
	</thead>
	<tbody>
	<?php
		$user = Yii::app()->user;
		$pos = $orig_dp->pagination->currentPage * $orig_dp->pagination->pageSize;
		// Опции Translate::render() для автора версии перевода
		$tr_opts_owner = array(
			"edit" => true, // $chap->book->membership->status == GroupMember::MODERATOR,
			"rm" => true,
			"rate" => false,
		);
		// Опции Translate::render() для всех остальных версий
		$tr_opts = array(
			"edit" => $chap->book->membership->status == GroupMember::MODERATOR,
			"rm" => $chap->book->membership->status == GroupMember::MODERATOR,
			"rate" => $chap->can("rate"),
			"rate-" => $chap->book->membership->status == GroupMember::MODERATOR,
		);
		$fixer = new OrigCountFixer();
		$to_esc = preg_quote($to);

		$can_tr_read = $chap->can("trread");

		foreach($orig as $o) {
			$pos++;
			echo "<tr id='o{$o->id}'>";

			echo "<td class='n'>";

			if(!Yii::app()->user->isGuest) {
				if($o->bookmark->id) {
					$title = "Закладка" . ($o->bookmark->note != "" ? (": &quot;" . CHtml::encode($o->bookmark->note) . "&quot;") : "");
					$html = "<i class='icon-star'></i>";
					$bm = "<a href='#' onclick=\"return T.bm.set({$o->id})\" class='b set' title='{$title}'>{$html}</a>";
				} else {
					$bm = "<a href='#' onclick=\"return T.bm.set({$o->id})\" class='b' title='Поставить закладку'><i class='icon-star-empty'></i></a>";
				}
			} else {
				$bm = "";
			}

			if($chap->book->typ == "S") {
				if($show == 0) echo "<a href='#' class='ord'>{$pos}</a> ";
				echo "{$bm}<br />";
				echo "<span class='t1'>" . $o->nicetime("t1") . "</span><br /><span class='t2'>" . $o->nicetime("t2") . "</span>";
			} else {
				echo "<a href='#' class='ord'>{$o->ord}</a> {$bm}";
			}


			// AUTOFIX. Если есть какой-нибудь фильтр на переводы, то не делаем пересчёт orig.n_trs!
			if($o->n_trs != count($o->trs) and $show == 0) {
				if($fixer->add($o->id, count($o->trs))) if(Yii::app()->user->can("geek")) echo "<i class='icon-bell'></i>";
			}
			echo "</td>";


			if($user->isGuest) {
				if($pos == 1) {
					echo "<td class='o' rowspan='100'>";
					echo "<p style='margin: 30px 0; text-align: center;'>";
					echo "Сожалеем, но текст оригинала доступен только <a href='/register'>зарегистрированным</a> пользователям.";
					echo "</p>";
					echo "</td>";
				}
			} else {
				echo "<td class='o'>";
				$html = $o->render($filter);
				echo $html;
				if($chap->book->can("chap_edit")) echo " <a href='#' class='e'><i class='icon-edit'></i></a>";
				if($show != 0) echo " <a href='{$o->url}' class='ctx'>в контексте</a>";
				echo "</td>";
			}

			echo "<td class='u'>";
			if($o->n_comments > 0) {
				if($o->n_comments > $o->seen->n_comments) {
					$n_new = $o->n_comments - $o->seen->n_comments;
					echo "<a href='#' class='c' title='Комментариев: {$o->n_comments}, новых: {$n_new}'>{$o->seen->n_comments}+{$n_new} <i class='icon-nb-comment new'></i></a> ";
				} else {
					echo "<a href='#' class='c' title='Комментариев: {$o->n_comments}'>{$o->n_comments} <i class='icon-nb-comment'></i></a> ";
				}
			} else {
				if($chap->can("comment")) echo "<a href='#' class='c add' title='Написать комментарий'><i class='icon-nb-comment'></i></a> ";
			}
			if($chap->can("tr")) echo "<a href='#' class='t'>&raquo;&raquo;&raquo;</a> ";
			echo "</td>";

			echo "<td class='t'>";
				/**
				 * @todo echo $o->renderTranslations()
				 * и то же самое в OrigController::actionTranslate
				 **/
				$trs = $o->trs;
				usort($trs, array("Translation", "trcmp"));

				$max_id = null; $max_rating = null; $max_cdate = null;
				foreach($trs as $tr) {
					if($max_id === null || $tr->rating >= $max_rating) {
						$max_id = $tr->id;
						$max_rating = $tr->rating;
						$max_cdate = strtotime($tr->cdate);
					}
				}
				foreach($trs as $tr) {
					if(!$can_tr_read && $tr->user_id != $user->id) continue;
					$tr->chap = $chap;
					if(Yii::app()->user->ini["t.hlr"] == 1) $tr_opts["best"] = $tr_opts_owner["best"] = $tr->id == $max_id;
					$html = $tr->render((!$user->isGuest && $tr->user_id == $user->id) ? $tr_opts_owner : $tr_opts, $filter);
					echo $html;
				}
			echo "</td>";

			echo "</tr>\n";
		}
	?>
	</tbody>
</table>

<?php
	$fixer->fix();

	$this->widget('bootstrap.widgets.TbPager', array("pages" => $orig_dp->pagination, "header" => "<div class='pagination pagination-centered'>"));
?>

<div id="rating-descr" class="modal hide"></div>

<?php endif; ?>

<div id="filter-modal" class="modal hide">
	<form method="get" class="form-inline" action="<?=$chap->url; ?>">
	<div class="modal-header">
		<a class="close" data-dismiss="modal">×</a>
		<h3>Фильтр</h3>
	</div>
	<div class="modal-body">
		<ul class='options'>
		<?php
			foreach ($filters as $k => $v) {
				echo "<li><label class='radio'><input type='radio' name='show' value='{$k}' " . ($k == $show ? " checked" : "") . "/>{$v}</label>";
				if($k == 2) {
					echo " <input type='text' name='show_user' class='span3' value='" . (!empty($show_user) ? $show_user : (Yii::app()->user->isGuest ? "" : Yii::app()->user->login)) . "' />";
				} elseif($k == 5) {
					echo " <input type='text' name='to' class='span3' value='" . CHtml::encode($to) . "' />";
				} elseif($k == 6) {
					echo " <input type='text' name='tt' class='span3' value='" . CHtml::encode($tt) . "' />";
				}
				echo "</li>";
			}
		?>
		</ul>
	</div>
	<div class="modal-footer">
		<button type="submit" class="btn btn-primary">Показать</button>
		<a href="#" class="btn" data-dismiss="modal">Отмена</a>
	</div>
	</form>
</div>

<?php if($chap->book->typ == "S"): ?>
<div id="timeshift-modal" class="modal hide">
	<form method="post" class="form-inline" action="<?=$chap->getUrl("timeshift"); ?>">
		<div class="modal-header">
            <a class="close" data-dismiss="modal">×</a>
            <h3>Сдвинуть тайминг</h3>
		</div>
        <div class="modal-body">
            <div class="control-group advanced">
                <label class="control-label">Сдвинуть субтитры во временном промежутке</label>
                <div class="controls">
                    <input type="text" name="from" placeholder="ЧЧ:ММ:СС.ммм" value="00:00:00.000" /> &mdash;
                    <input type="text" name="to" placeholder="ЧЧ:ММ:СС.ммм" value="23:59:59.999" />
                </div>
            </div>
			<div class="control-group">
                <label class="control-label">Сдвинуть <a href="#" class="ajax advanced">все субтитры</a> на время:</label>
				<div class="controls">
					<input type="text" name="value" placeholder="ЧЧ:ММ:СС.ммм" value="00:00:00.000" autofocus />
				</div>
			</div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Поехали</button>
            <a href="#" class="btn" data-dismiss="modal">Отмена</a>
        </div>
	</form>
</div>
<?php endif; ?>

<div id="dict-dialog" title="Словарь" style="display:none;">
	<p class="loading">Минуточку...</p>
</div>

