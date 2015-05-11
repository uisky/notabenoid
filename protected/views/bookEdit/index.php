<?php
	$this->pageTitle = "Создать перевод";
?>
<style type="text/css">
	.t { float:left; margin-top:30px; margin-bottom:30px; text-align: center; }
	.t:hover {background:#f0f0f0; border-radius: 10px;}
	.t big {font-size:30px; margin-top:10px; margin-bottom: 20px; display: block; line-height: 100%;}
	.t p { margin-left: 10px; margin-right: 10px;}
</style>
<h1>Создать перевод</h1>

<p>
	Чтобы создать проект перевода, нужно совершить четыре шага. Сначала давайте выберем формат перевода. Что вы собираетесь переводить?
</p>

<div class="row">
	<div class='span4 t'>
		<big><a href='?typ=A'>Текст</a></big>
		<p>
			Фрагмент оригинала &ndash; это просто небольшой кусочек текста.
			Их можно загрузить из текстового файла, скопировать из буфера обмена или набрать вручную.
		</p>
	</div>
	<div class='span4 t'>
		<big><a href='?typ=S'>Субтитры</a></big>
		<p>
			Всё то же самое, но каждый фрагмент снабжён таймингом &ndash; временем начала и конца.
			Субтитры можно загрузить в формате SRT.
		</p>
	</div>
</div>
