<?php
	/**
	 * @var Mail $message
	 */

	$this->pageTitle = "Письмо " . ($message->folder == Mail::INBOX ? "от" : "к") . " {$message->buddy->login}";
?>
<script type="text/javascript">
var P = {
	rm: function() {
		if(!confirm("Вы уверены, что хотите удалить это письмо?")) return false;
		$("#form-rm").submit();
	},
	unseen: function() {
		$("#form-unseen").submit();
	},
	re: function() {

	}
}
</script>

<?php
	echo "<h1>" . ($message->subj == "" ? "Письмо" : $message->subj) . "</h1>";
	echo "<div class='body'>";
	echo Yii::app()->parser->parse($message->body);
	echo "</div>";
?>

<form id="form-rm" method="post" action="/my/mail/?folder=<?=$message->folder; ?>">
	<input type="hidden" name="act" value="rm"><input type="hidden" name="id[]" value="<?=$message->id; ?>" />
</form>

<form id="form-unseen" method="post" action="/my/mail/?folder=<?=$message->folder; ?>">
	<input type="hidden" name="act" value="unseen"><input type="hidden" name="id[]" value="<?=$message->id; ?>" />
</form>