<?php
	/**
	 * @var array $Questions
	 */

	$this->pageTitle = "Опрос";
?>
<style type="text/css">
#poll .control-group {margin-bottom: 30px; }
#poll .control-label {font-weight: bold;}
</style>
<script type="text/javascript">
$(function() {
	var $poll = $("#poll");
	$poll.find("input.custom").keyup(function(e) {
		var $this = $(this);
		if($.trim($this.val()) != "") {
			$this.parents(".control-group").find(":radio").attr("checked", false);
		}
	});
	$poll.find(":radio").click(function(e) {
		$(this).parents(".control-group").find("input.custom").val("");
	});
});
</script>
<h1>Опрос для науки</h1>
<p>
	Дорогие друзья! Настал тот день, когда мы все можем лично поучаствовать в развитии научной мысли человечества и помочь учёным
	совершить массу удивительных открытий. Для этого им срочно нужны некоторые статистические данные. Пожалуйста, честно ответьте на
	девять несложных вопросов.
</p>

<form method="post" id="poll" action="/site/poll">
<?php
	foreach($Questions as $q) {
		$id = $q["id"];
		$error = (isset($_POST["answer"]) || isset($_POST["custom"])) && ($_POST["answer"][$id] == "" && $_POST["custom"][$id] == "");

		echo "<div class='control-group" . ($error ? " error" : "") . "'>";
		echo "<label class='control-label'>{$q["text"]}</label>";
		if($q["options"] === "bool") $options = ["Да", "Нет"];
		else $options = $q["options"];
		foreach($options as $o) {
			echo "<label class='radio'>";
			echo CHtml::radioButton("answer[{$id}]", $_POST["answer"][$id] == $o, ["value" => $o]);
			echo $o;
			echo "</label>";
		}
		if($q["allowCustom"]) {
			$opts = ["class" => "span4 custom", "placeholder" => "Свой вариант"];
			if($q["customMaxLength"] > 0) $opts["maxlength"] = intval($q["customMaxLength"]);
			echo CHtml::textField("custom[{$id}]", $_POST["custom"][$id], $opts);
		}
		echo "</div>";
	}
?>
<div class="form-actions">
	<button type="submit" class="btn btn-primary">Готово!</button>
</div>
</form>