<style type="text/css">
#recalc-form {text-align:center; margin-top:40px;}
</style>

<h1>Обновить счётчики</h1>

<p>
	Иногда так бывает, что статистика перевода &ndash; количество фрагментов оригинала, версий перевода и вытекающий из этого процент готовности
	подсчитываются неправильно. Если вы считаете, что именно такая неприятность произошла с этим переводом, пожалуйста, нажмите Большую Красную Кнопку
	внизу.
</p>
<form method="post" id="recalc-form">
	<input type="hidden" name="go" value="1" />
	<button type="submit" class="btn btn-large btn-danger"><i class="icon-fire icon-white"></i> Большая Красная Кнопка</button>
	<br /><br />
	<?php if(Yii::app()->user->can("geek")): ?>
    	<input type="checkbox" name="full" value="1" id="cb-full"/>
		<label for="cb-full">
			 Также пересчитать рейтинги, количество комментариев, количество переводов каждого фрагмента
		</label>
	<?php endif; ?>
	<a href='<?=$book->url; ?>' class='btn'>Отмена</a>
</form>