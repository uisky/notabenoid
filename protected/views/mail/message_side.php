<?php
	/**
	 * @var Mail $message
	 */
?>
<div class="tools">
	<h5>Письмо</h5>
	<?php
		echo "<p>";
		echo $message->folder == Mail::INBOX ? "От: " : "Кому: ";
		echo $message->buddy->ahref . ", " . Yii::app()->dateFormatter->formatDateTime($message->cdate, "medium", "short");
		echo "</p>";
	?>

	<p><a href="/my/mail?folder=<?=$message->folder; ?>">К списку сообщений</a></p>

	<button type="button" class="btn btn-danger btn-small" onclick="P.rm()"><i class="icon-remove-circle icon-white"></i> Удалить</button>
	<button type="button" class="btn btn-primary btn-small" onclick="P.unseen()" title="Сообщение будет помечено, как непрочитанное"><i class="icon-eye-close icon-white"></i> Я это не читал!</button>
	<?php if($message->folder == Mail::INBOX): ?>
		<a href="/my/mail/write?reply=<?=$message->id; ?>" class="btn btn-success btn-small" onclick="P.re()"><i class="icon-bullhorn icon-white"></i> Ответить</a>
	<?php endif; ?>
</div>