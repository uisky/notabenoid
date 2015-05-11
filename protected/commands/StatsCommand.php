<?php
class StatsCommand extends CConsoleCommand {

	public function actionIndex() {
		echo "Доступные миграции:\n  - " . join("\n  - ", array_keys($this->migrations)) . "\n";
	}


	private $rcDay = "", $rcDayHit = 0, $rcDayMiss = 0, $rcHit = 0, $rcMiss = 0;

	public function actionRclog() {
		$month = date("Y-m");
		echo "Эффективность ready cache за {$month}:\n\n";
		$logfile = Yii::app()->basePath . "/runtime/cache{$month}.log";
		if(!is_readable($logfile)) {
			echo "Файл {$logfile} не открывается.\n";
			return;
		}
		$fh = fopen($logfile, "r");
		$cnt = 0;
		while(!feof($fh)) {
			$line = trim(fgets($fh));
			$cnt++;
			if($line == "") continue;
			if(!preg_match("/^(\\d{4}-\\d{2}-\\d{2}).*\t.*\t(.*)/", $line, $res)) {
				echo "Line {$cnt}: хуйня какая-то\n";
			}
			if($this->rcDay != $res[1]) {
				$this->rcFlushDay();
				$this->rcDay = $res[1];
			}
			if($res[2] == "CACHED") { $this->rcDayHit++; $this->rcHit++; }
			elseif($res[2] == "PUT TO CACHE") { $this->rcDayMiss++; $this->rcMiss++; }
//			echo "Line {$cnt}: $res[1] - $res[2]\n";
		}
		$this->rcFlushDay();
		printf("---------------------------------------\nTOTAL:      HIT: %d MISS: %d (%.01f%%)\n", $this->rcHit, $this->rcMiss, $this->rcHit / ($this->rcMiss+$this->rcHit) * 100);
		fclose($fh);
	}

	private function rcFlushDay() {
		if($this->rcDay == "") return;
		printf("%s: HIT: %d MISS: %d (%.01f%%)\n", $this->rcDay, $this->rcDayHit, $this->rcDayMiss, $this->rcDayHit / ($this->rcDayMiss+$this->rcDayHit) * 100);
		$this->rcDayHit = 0; $this->rcDayMiss = 0;
	}
}
?>
