<?php
	/**
	 * @var array[] $data
	 * @var array[] $Questions
	 */

	$this->pageTitle = "Результаты опроса";
?>
<style type="text/css">
.question {
	margin:20px 0 40px;
}
</style>
<h1>Результаты опроса</h1>
<?php
	foreach($Questions as $q) {
		echo "<div class='question'>";
		echo "<p class='q'>" . $q["text"] . "</p>";
		echo "<ul>";
		$n_answers = 0;
		foreach($data[$q["id"]] as $answer) $n_answers += $answer["n"];
		foreach($data[$q["id"]] as $answer) {
			printf("<li>%s: <strong>%.2f%%</strong> (%d чел)", $answer["answer"], $answer["n"] / $n_answers * 100, $answer["n"]);
		}
		echo "</ul>";
		echo "</div>";
	}
?>

<p><a href="/dungeon/pollRawResults">Скачать CSV</a></p>