<?php
	/**
	 * @var String $mode "p", "o"
	 */
?>
<div class='tools'>
<h5>Мои обсуждения</h5>

<p>
	Посмотрите направо. Увидев вещь, которой вы не пользовались больше года, выкиньте её. Теперь посмотрите налево, в центр экрана. Здесь вы видите посты в блогах или фрагменты оригинала, к которым вы когда-либо оставляли комментарии или просто решили следить за дискуссиями в них.
</p>

<form method="post" action="/my/comments/ini">
	<label class="checkbox">
		<input type="checkbox" name="new" value="1" <?=Yii::app()->user->ini_get(User::INI_MYTALKS_NEW) ? " checked='checked'" : ""; ?> onclick="this.form.submit()" /> только с новыми комментариями
	</label>
</form>

<form method="post" action="/my/comments/visited" onsubmit="return confirm('Если вы сейчас нажмёте Ok, все ссылки на <?=$mode == "o" ? "переводы" : "посты"; ?> отсюда исчезнут и вы никогда не узнаете, что там было написано до тех пор, пока там не появятся новые комментарии. Идёт?');">
	<input type="hidden" name="mode" value="<?=$mode; ?>" />
	<button type="submit" class="btn btn-small btn-inverse" title="Станет, как будто вы заходили во все эти <?=$mode == "o" ? "переводы" : "посты"; ?>, прочитали новые комментарии и тут же забыли об этом."><i class="icon-eye-open icon-white"></i> Удалить всё отсюда</button>
</form>

<?php if(!Yii::app()->user->isGuest) { ?>( <a href='/blog/edit/' class='act'>написать пост в общем блоге</a> )<?php } ?>
</div>