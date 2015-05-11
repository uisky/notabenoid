<?php
/**
 * @var Announce[] $announces
  */
header("Content-Type: application/rss+xml; charset=utf-8");
echo "<?xml version=\"1.0\"?>";
?>
<rss version="2.0">
	<channel>
		<title>Анонсы <?=Yii::app()->name; ?></title>
		<link>http://<?=Yii::app()->params["domain"]; ?>/announce/</link>
		<description>Анонсы переводов на сайте <?=Yii::app()->name; ?></description>
		<language>ru</language>
		<pubDate><?=$announces[0]->cdate; ?></pubDate>

		<lastBuildDate><?=$announces[0]->cdate; ?></lastBuildDate>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<generator>Freelove Framework Tisla Edition</generator>
		<webMaster><?=Yii::app()->params["adminEmail"]; ?></webMaster>

		<?php
		foreach($announces as $announce) {
			echo "<item>\n";
			echo "<title>{$announce->book->fullTitle}</title>\n";
			echo "<description>" . htmlspecialchars($announce->body) . "</description>\n";
			echo "<link>" . $announce->url . "</link>\n";
			echo "<pubdate>" . gmdate("r", strtotime($announce->cdate)) . "</pubdate>\n";
			echo "<guid>" . "</guid>\n";
			echo "</item>\n\n";
		}
		?>

	</channel>
</rss>