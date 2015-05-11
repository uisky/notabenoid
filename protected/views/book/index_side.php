<?php
	if(Yii::app()->user->isGuest || !Yii::app()->user->ini_get(User::INI_ADDTHIS_OFF)) {
		Yii::app()->clientScript
			->registerScriptFile("http://userapi.com/js/api/openapi.js?49")
			->registerScript("VKLIKE", "VK.init({apiId: 3013223, onlyWidgets: true});", CClientScript::POS_HEAD);

		echo <<<FB
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/ru_RU/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
FB;

	}
?>
<div class='tools'>
	<h5>Перевод</h5>

	<dl class='info'>
		<dt>Перевод:</dt>
		<dd><?=Yii::app()->params["book_types"][$book->typ] . " " . Yii::app()->langs->from_to($book->s_lang, $book->t_lang); ?></dd>

		<dt>Создан:</dt>
		<dd><?php echo Yii::app()->dateFormatter->formatDateTime($book->cdate, "medium", "") . ', владелец: ' . $book->owner->ahref; ?></dd>

		<?php if($book->n_dl > 0): ?>
		<dt>Скачали:</dt>
		<dd>
			<span rel='popover' data-content='Учитываются только загрузки с уникальных IP-адресов. Иногда компьютеры из одной домашней или корпоративной сети имеют один и тот же IP-адрес, поэтому относитесь к этим цифрам как к примерной заниженной оценке.' data-title="Откуда берутся эти цифры?">
			<?php
				echo "{$book->n_dl} чел.";
				if($book->n_dl_today > 0) {
					echo " (сегодня &ndash; {$book->n_dl_today})";
				}
			?>
			</span>
			<script type='text/javascript'>$("span[rel=popover]").popover();</script>
		</dd>
		<?php endif; ?>

		<dt>Права доступа:</dt>
		<dd>
			<div id="ac_icons">
			<?php
				$ac_important = array("ac_read", "ac_trread", "ac_gen", "ac_rate", "ac_comment", "ac_tr");
				foreach($ac_important as $ac) {
					echo "<i class='{$ac} {$book->$ac}'></i> ";
				}
			?>
			<a href="#" class='more_btn' onclick="$('#side_ac_more').show(); $('#ac_icons').hide(); $(this.parentNode).hide(); return false;">подробнее...</a>
			</div>
			<div class="more" id="side_ac_more" style="display:none;">
				<table class="t">
				<?php
					foreach(Yii::app()->params["ac_areas"] as $ac => $title) {
						echo "<tr><td>";
						if(in_array($ac, $ac_important)) echo "<i class='{$ac} {$book->$ac}'></i> ";
						echo $title;
						echo "</td><td class='d'>" . Yii::app()->params["ac_roles"][$book->$ac] . "</td></tr>";
					}
				?>
				<tr>
					<td>Участие в группе</td>
					<td class='d'><?php
						if($book->ac_membership == "m") {
							$A = array(Book::FC_OPEN => "нет группы", Book::FC_CONFIRM => "после подтверждения модераторами", Book::FC_INVITE => "по приглашению от модераторов");
						} else {
							$A = array(Book::FC_OPEN => "нет группы", Book::FC_CONFIRM => "после подтверждения создателем", Book::FC_INVITE => "по приглашению от создателя");
						}
						echo $A[$book->facecontrol];
					?></td>
				</tr>
				</table>
				<?php if($book->can("owner")) echo "<div style='text-align:right; margin-top:5px;'><a href='" . $book->getUrl("edit/access") . "' class='act'>Редактировать</a></div>"; ?>
			</div>
		</dd>

		<dt>Готово:</dt>
		<dd><?php
			if($book->n_vars == 0 || $book->n_verses == 0) echo "&mdash;";
			else {
				$procent = floor($book->d_vars / $book->n_verses * 10000) / 100;
				$classes = array(100 => "progress-danger", 80 => "progress-warning", 60 => "progress-success", 40 => "", 20 => "progress-info");
				foreach($classes as $p => $class) {
					if($procent >= $p) {
						break;
					}
				}
				$class = "progress-success";

				echo "<div class='progress progress-striped {$class}' style='margin-bottom:2px;'>";

				printf("<div class='bar' style='width: %d%%;'></div>", $procent);

				echo "<div class='text'>";
				printf("<span title='глав: %d, фрагментов: %d, переведено: %d'>%0.02f%%</span>", $book->n_chapters, $book->n_verses, $book->d_vars, $procent);
				if($book->d_vars > 0 and $book->n_vars > 0) printf(" <abbr title='Коэффициент Плюрализма: среднее количество вариантов перевода каждого фрагмента'>КП</abbr> = %.01f", $book->n_vars / $book->d_vars);
				echo "</div>";

				echo "</div>";
			}
		?></dd>

		<?php
			if(!Yii::app()->user->isGuest) {
				$myStatus = "";
				if($book->membership->status == GroupMember::BANNED) {
					$myStatus = "Вы забанены в этом переводе.";
				} elseif($book->owner_id == Yii::app()->user->id) {
					$myStatus = "Вы &ndash; создатель этого перевода.";
				} elseif($book->membership->status == GroupMember::MODERATOR) {
					$myStatus = "Вы &ndash; модератор этой группы. <a href='" . $book->getUrl("members") . "#leave'>Выйти</a>.";
				} elseif($book->membership->status == GroupMember::MEMBER) {
					$myStatus = "Вы состоите в группе перевода. <a href='" . $book->getUrl("members") . "#leave'>Выйти</a>.";
				} elseif($book->facecontrol != Book::FC_OPEN) {
					$myStatus = "Вы не состоите в группе перевода. ";
					if($book->facecontrol == Book::FC_CONFIRM) {
						$myStatus .= "<a href='" . $book->getUrl("members") . "' class='act' title='Вашу заявку сначала рассмотрят модераторы'>Вступить</a>.";
					} elseif($book->facecontrol == Book::FC_INVITE) {
						$myStatus .= "Членство в этой группе &ndash; только по приглашению от " . ($book->ac_membership == "m" ? "модераторов" : "владельца перевода") . ".";
					}
				}
				if($myStatus != "") echo "<dt>Ваш статус:</dt><dd>{$myStatus}</dd>";
			}
		?>
    </dl>
</div>


<?php
	$Tools = array();
	$Tools[] = "<a href='http://www.imdb.com/find?q=" . urlencode($book->s_title) . "&s=tt' target='_blank' rel='nofollow'>Искать на IMDb</a>";
	$Tools[] = "<a href='http://www.kinopoisk.ru/index.php?first=no&what=&kp_query=" . urlencode($book->s_title) . "&s=tt' target='_blank' rel='nofollow'>Искать на kinopoisk.ru</a>";
	if(!Yii::app()->user->isGuest) $Tools[] = "<a href='" . $book->getUrl("recalc") . "'>Пересчитать статистику перевода</a>";
?>
<div class='tools'>
	<h5>Инструментарий</h5>
	<?php if(!Yii::app()->user->isGuest):
		echo "<button class='btn btn-small' id='btn-bookmark' onclick='Book.bookmark({$book->id})'>";
		if($book->bookmark->book_id) {
			echo "<i class='icon-star'></i> Изменить закладку";
		} else {
			echo "<i class='icon-star-empty'></i> Поставить закладку";
		}
		echo "</button>";
	?>
	<?php endif; ?>

	<ul style="margin-top:10px"><li><?=join("</li><li>", $Tools); ?></li></ul>


<?php if(Yii::app()->user->isGuest || !Yii::app()->user->ini_get(User::INI_ADDTHIS_OFF)): ?>
<script type="text/javascript">(function() {
  if (window.pluso)if (typeof window.pluso.start == "function") return;
  if (window.ifpluso==undefined) { window.ifpluso = 1;
    var d = document, s = d.createElement('script'), g = 'getElementsByTagName';
    s.type = 'text/javascript'; s.charset='UTF-8'; s.async = true;
    s.src = ('https:' == window.location.protocol ? 'https' : 'http')  + '://share.pluso.ru/pluso-like.js';
    var h=d[g]('body')[0];
    h.appendChild(s);
  }})();</script>
<div class="pluso" data-background="transparent" data-options="medium,round,line,horizontal,counter,theme=04" data-services="vkontakte,facebook,twitter,pinterest,livejournal"></div>
<?php endif ?>

</div>
