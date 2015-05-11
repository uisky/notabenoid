<?php
	/**
	 * @var Book[] $hot
	 * @var String $searchTop
	 * @var Announce[] $announces
	 * @var BlogPost[] $blog
	 */

	Yii::app()->clientScript->registerCssFile("/css/face.css?1");

	$this->pageTitle = "Система коллективных переводов";
?>
<div class="row">
	<div class="span7" id="f-hot">
		<h2>
			Переводится прямо сейчас
			<a href="#" data-toggle="modal" data-target="#f-hot-ini" class="cog" title="Настроить внешний вид этого блока"><i class='icon-cog'></i></a>
		</h2>
		<?php
			function humanTime($t) {
				if($t < 60) return "{$t} сек.";
				if($t < 3600) return sprintf("%d мин. %d сек.", $t / 60, $t % 60);
				else return sprintf("%d час. %d мин.", $t / 3660, ($t / 60) % 60);
			}
			$ini = Yii::app()->user->ini;
			if(count($hot) == 0) {
				echo "<p class='alert alert-box alert-warning'>В данный момент никто ничего не переводит ";
				echo Yii::app()->langs->from_to($ini["hot.s_lang"], $ini["hot.t_lang"]);
				echo ". Хотите посмотреть, что переводят <a href='#' data-toggle='modal' data-target='#f-hot-ini'>на других языках</a>?</p>";
			} else {
				echo "<ul" . ($ini["hot.img"] ? " class='imged'" : "") . ">";
				foreach($hot as $book) {
					echo "<li>";
					$t = humanTime(time() - strtotime($book->last_tr));

					if($ini["hot.img"]) {
						echo "<div class='bimg'";
						if($book->img->exists) echo " style=\"background-image:url('" . $book->img->getUrl("5050") . "')\"";
						echo "></div>";

						echo "<p class='title'>";
						echo "<a href='{$book->url}' title='{$t}'>{$book->fullTitle}</a>";
						echo "</p><p class='info'>";
						echo Yii::app()->params["book_types"][$book->typ] . " ";
						echo Yii::app()->langs->from_to($book->s_lang, $book->t_lang) . " ";
						echo "<span class='r'>{$book->ready}</span>";
						echo "</p>";
					} else {
						echo Yii::app()->langs->from_to($book->s_lang, $book->t_lang, Langs::FORMAT_ABBR) . " ";
						echo "<a href='{$book->url}' title='{$t}'>{$book->fullTitle}</a> ";
					}

					echo "</li>";
				}
				echo "</ul>";
			}
		?>

		<?php if(0 && !Yii::app()->user->isGuest && Yii::app()->user->ini["poll.done"] < 1): ?>
		<div class='banner-poll'>
			<i>Не проходите мимо!</i> &mdash; <b>Старинная забава!</b><br />
			<a href='site/poll'>Социологический Опрос!</a>
		</div>
		<?php endif; ?>

		<div id="f-hot-ini" class="modal hide">
			<form method="post" class="form-horizontal" action="/site/ini" style="margin:0">
			<input type="hidden" name="area" value="hot" />
			<div class="modal-header" style='padding-bottom:0;'>
				<a class="close" data-dismiss="modal">×</a>
				<h3>Настройки блока</h3>
			</div>
			<div class="modal-body" style="max-height:350px">
				<div class="control-group">
					<label class="control-label">Язык оригинала:</label>
					<div class="controls"><select name="s_lang">
						<option value="0">Любой</option>
						<?=Yii::app()->langs->options(Langs::FORM_INF, Yii::app()->user->ini["hot.s_lang"]); ?>
					</select></div>
				</div>

				<div class="control-group">
					<label class="control-label">Язык перевода:</label>
					<div class="controls"><select name="t_lang">
						<option value="0">Любой</option>
						<?=Yii::app()->langs->options(Langs::FORM_INF, Yii::app()->user->ini["hot.t_lang"]); ?>
					</select></div>
				</div>

				<div class="control-group">
					<div class="controls">
						<label class="checkbox"><input type="hidden" name="img" value="0" /><input type="checkbox" name="img" value="1" <?php if(Yii::app()->user->ini["hot.img"]) echo "checked"; ?>/> с картинками</label>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="subit" class="btn btn-success"><i class="icon-ok icon-white"></i> Сохранить</button>
				<button type="button" class="btn" data-dismiss="modal"><i class="icon-ban-circle"></i> Отмена</button>
			</div>
			</form>
		</div>

	</div>
	<div class="span5" id="f-about">
		<div class='hero'>
			<h3>Что это тут у нас такое?</h3>
			<p>
				Привет. Добро пожаловать в систему управления коллективным разумом &laquo;Нотабеноид&raquo;. Этот сайт предназначен для коллективных переводов любых текстов и субтитров на разные языки.
			</p>
			<h3>Как это работает?</h3>
			<p>
				Текст разбивается на множество мелких кусочков (предложений, абзацев, титров). Каждый участник перевода читает фрагмент на языке оригинала, думает, и предлагает
				свой вариант перевода. Если именно этот вариант нравится другим посетителям, они ставят ему &laquo;плюс&raquo;. Все оценки суммируются,
				и, таким образом, видно, какой вариант перевода приятней для слуха. Из лучших вариантов собирается готовый перевод. Получается либо очень быстро, либо очень хорошо, либо и так и сяк.
				Также к вашим услугам - <a href="/site/help">система разделения прав доступа</a>, коллективный блог перевода, комментирование фрагментов, разнообразная статистика.
				Попробуйте сами.
			</p>
			<h3>А ещё:</h3>
			<ol>
				<li>Переводить что-нибудь интересное в компании единомышленников - интересное и увлекательное занятие.</li>
				<li>Чтение и обсуждение чужих вариантов перевода здорово помогает изучать иностранный язык.</li>
				<li>Смотреть кино с оригинальной озвучкой и русскими субтитрами гораздо интереснее. Попробуйте!</li>
			</ol>
		</div>

		<div style="margin:10px auto; width:320px;">

		</div>

	</div>
</div>

<div id="f-search-top">
	<h2>
		<span rel='popover' data-content='Чем крупнее название, тем чаще его ищут на этом сайте.' data-title="Что это такое?">
		Популярные переводы
		</span>
		<script type='text/javascript'>$("span[rel=popover]").popover();</script>
	</h2>
	<p class="cloud"><?php echo $searchTop; ?></p>
	<p class="links">
		&rarr; <a href="/search">поиск переводов</a>
	</p>
</div>

<div class="row">
	<div id="f-announces" class="span7">
		<h2>
			Новости переводов
			<span class="links">&rarr; <a href="/announces">все новости</a></span>
		</h2>
		<?php
			foreach($announces as $announce) {
				$this->renderPartial("/announces/_announce", array("announce" => $announce));
			}
		?>
		<p class="links">
			&rarr; <a href="/announces">остальные новости</a>
		</p>
	</div>
	<div id="f-blog" class="span5">
		<h2>
			Обсуждается в блоге
			<span class="links">&rarr; <a href="/blog">весь блог</a></span>
		</h2>
		<ul>
		<?php
			foreach($blog as $post) {
				echo "<li>";
				echo "<a href='{$post->url}'>{$post->title}";
				if($post->n_comments > 0) echo " <span class='c'><i class='icon-nb-comment'></i> {$post->n_comments}</span>";
				echo "</a>";
				$b = strip_tags($post->body);
				echo "<p>" . (mb_strlen($b) > 120 ? mb_substr($b, 0, 120) . "..." : $b) . "</p>";
				echo "</li>";
			}
		?>
		</ul>
		<p class="links">
			&rarr; <a href="/blog">остальные посты</a>
		</p>
	</div>
</div>