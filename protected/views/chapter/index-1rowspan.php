<?php
	/**
	 * @var CActiveDataProvider $orig_dp
	 * @var Chapter $chap
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

	Yii::app()->clientScript
		->registerCssFile("/css/translate-1rowspan.css?11")
		->registerScriptFile("/js/translate.js?17")
		->registerScriptFile("/js/jquery.scrollTo.js")
		->registerScriptFile("/js/jquery.elastic.mod.js")
		->registerScriptFile("/js/ff_comments.js?1");

	Yii::app()->bootstrap->registerModal();

	$this->pageTitle = "Перевод " . $chap->book->fullTitle . ": " . $chap->title;

	/** @var Orig[] $orig */
	$orig =  $orig_dp->getData();
?>

<h1><?=$chap->book->ahref; ?>: <?=$chap->title; ?></h1>

<div class="btn-toolbar" id='toolbar-main'>
    <div class="btn-group">
        <a href="<?=$chap->getUrl("go?to=prev&ord={$chap->ord}"); ?>" class="btn btn-small" title="Предыдущая глава"><i class="icon-arrow-left"></i></a>
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
        <a href="<?=$chap->getUrl("go?to=next&ord={$chap->ord}"); ?>" class="btn btn-small" title="Следующая глава"><i class="icon-arrow-right"></i></a>
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
        <script type="text/javascript">
            T.setStats(<?php echo "{$chap->n_vars}, {$chap->d_vars}, {$chap->n_verses}"; ?>);
        </script>
	</div>
</div>

<?php $this->widget('bootstrap.widgets.TbPager', array("pages" => $orig_dp->pagination, "header" => "<div class='pagination pagination-centered'>")); ?>

<?php
if($orig_dp->totalItemCount == 0):
	if($show == 0 || $chap->n_verses == 0) {
		echo "<p class='alert alert-info alert-block'>В эту часть перевода ещё не загрузили оригинальный текст.";
		if($chap->book->can("chap_edit")) echo " Не желаете ли <a href='" . $chap->getUrl("import") . "'>сделать это сейчас</a>?";
		echo "</p>";
	} else {
		echo "<p class='alert alert-info alert-block'>Ничего не найдено. <a href='{$chap->url}'>Показать весь перевод.</a></p>";
	}
else:
	if(!$chap->book->can("trread")) {
		echo "<div class='alert alert-danger'>Владелец перевода установил такие права доступа, что вы не можете просматривать чужие версии перевода здесь.</div>";
	}
?>

<table id="Tr" class="translator translator-orig-editing translator-tr-editing">
    <thead>
    <tr>
        <?php if(!Yii::app()->user->isGuest): ?><th></th><?php endif; ?>
        <th><?=(Yii::app()->langs->Langs[$chap->book->s_lang][Langs::FORM_INF]); ?> оригинал</th>
		<?php if($chap->book->can(orig_edit)): ?><th><a href="#" onclick="$('#Tr').toggleClass('translator-orig-editing'); return false;">...</a></th><?php endif; ?>
		<?php if($chap->can("tr")): ?><th></th><?php endif; ?>
        <th>Перевод  на <?=Yii::app()->langs->Langs[$chap->book->t_lang][Langs::FORM_INF]; ?></th>
        <th></th>
        <th><a href="#" onclick="$('#Tr').toggleClass('translator-tr-editing'); return false;">...</a></th>
		<th></th>
    </tr>
    </thead>
    <tbody>
	<?php
		/**
		 * @param Translation $tr
		 * @param Chapter $chap
		 */
		function renderTrRow($tr, $chap) {
			echo "<td class='t'>";

			echo "<div class='tb'>";
			echo nl2br(htmlspecialchars($tr->body));
			echo "</div>";

			echo "<div class='ti'>";
			if($tr->user_id == 0) echo "(анонимно) ";
			else echo "{$tr->user->ahref} ";
			echo Yii::app()->dateFormatter->format("d.MM.yy в H:m", $tr->cdate);
			echo "</div>";

			echo "</td>";

			echo "<td class='tr'>";
			if($chap->can("rate")) echo "<div class='rater'>";
			if($chap->can("rate") && $chap->book->membership->status == GroupMember::MODERATOR) echo "<a href='#' class='m'>&minus;</a> ";
			echo "<span class='rate'>{$tr->rating}</span>";
			if($chap->can("rate")) echo " <a href='#' class='p'>+</a></div>";
			echo "</td>";

			echo "<td class='te'>";
			if($tr->user_id == Yii::app()->user->id || $chap->book->membership->status == GroupMember::MODERATOR) {
				echo "<a href='#' class='e'><i class='i icon-edit'></i></a> ";
				echo "<a href='#' class='x'><i class='i icon-remove'></i></a>";
			}
			echo "</td>";
		}

		$user = Yii::app()->user;
		$fixer = new OrigCountFixer();
		$can_tr_read = $chap->can("trread");
		$to_esc = preg_quote($to);

		foreach($orig as $o) {
			$o->chap = $chap;

			// AUTOFIX. Если есть какой-нибудь фильтр на переводы, то не делаем пересчёт orig.n_trs!
			if($show != 0 && $o->n_trs != count($o->trs)) {
				$fixer->add($o->id, count($o->trs)) && Yii::app()->user->can("geek");
			}

			if(count($o->trs) > 1) $rowspan = " rowspan='" . count($o->trs) . "'";
			else $rowspan = "";

			if(count($o->trs) <= 1) $class = " class='last'";
			else $class = "";
			echo "<tr {$class}>";

			if(!Yii::app()->user->isGuest) {
				echo "<td class='b' {$rowspan}>";
				if($o->bookmark->id) {
					$title = "Закладка" . ($o->bookmark->note != "" ? (": &quot;" . CHtml::encode($o->bookmark->note) . "&quot;") : "");
					echo "<i class='i icon-star' title='{$title}'></i>";
				} else {
					echo "<i class='i icon-star-empty'></i>";
				}
				echo "</td>";
			}



			echo "<td class='o' {$rowspan} id='o{$o->id}'>";
			$html = $o->body;
			if($show == 5) $html = preg_replace("/({$to_esc})/i", "<span class='shl'>\\1</span>", $html);

			echo "<div class='ob'>{$html}</div>";

			echo "<div class='oi'>";
			if($chap->book->typ == "S") {
				if($show == 0) echo "<a href='#' class='ord'>#{$o->ord}</a> &middot; ";
				echo "<span class='t1'>" . $o->nicetime("t1") . "</span> &rarr; <span class='t2'>" . $o->nicetime("t2") . "</span>";
			} else {
				echo "<a href='#' class='ord'>{$o->ord}</a> {$bm}";
			}
			if($show != 0) echo " <a href='{$o->url}' class='ctx'>в контексте</a>";
			echo "</div>";

			echo "</td>";




			if($chap->book->can("chap_edit")) {
				echo "<td class='oe' {$rowspan}>";
				echo "<i class='i icon-edit'></i>";
				echo "<i class='i icon-plus'></i>";
				echo "<i class='i icon-remove'></i>";
				echo "</td>";
			}



			if($chap->can("tr")) {
				echo "<td class='u' {$rowspan}>";
				echo "<i class='i icon-arrow-right'></i>";
				echo "</td>";
			}



			if(count($o->trs) > 0) {
				renderTrRow($o->trs[0], $chap);
			} else {
				echo "<td class='t'></td><td class='tr'></td><td class='te'></td>";
			}

			echo "<td class='c' {$rowspan}>";
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
			echo "</td>";

			echo "</tr>\n";

			if(count($o->trs) > 1) {
				for($i = 1; $i < count($o->trs); $i++) {
					echo "<tr" . (($i == count($o->trs) - 1) ? " class='last'" : "") . ">";
					renderTrRow($o->trs[$i], $chap);
					echo "</tr>";
				}
			}
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
    <form method="get" class="form-inline">
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

<div id="oadd-modal" class="modal hide">

</div>

<div id="dict-dialog" title="Словарь" style="display:none;">
    <p class="loading">Минуточку...</p>
</div>

