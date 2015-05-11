<?php
/**
 * @var array $data
	$last = end($data);
	$maxUsers = $last["stay"] + $last["left"];
 */

	$this->pageTitle = "Статистика использования интерфейсов";
?>
<style type="text/css">
#graph { width:800px; height:400px; }

.bars {position:relative; height:400px; border-bottom:3px solid black; margin-bottom:20px;}
.bars > div {
	position:absolute;
	bottom:0px;
	margin:0 10px;
	text-align: center; width:250px;
	border-top-left-radius: 10px; border-top-right-radius: 10px;
	box-shadow: 0 5px 10px rgba(0,0,0,.5) inset;
}
.bars .t1 {background: #A9CC1C; left:0px;}
.bars .t0 {background: #BD667E; left:260px;}

.bars b {
	display:block;
	width:100%;
	height:100%;
	opacity:.2;
	background-position: center center;
    border-top-left-radius: 10px; border-top-right-radius: 10px;
}

.bars strong {display:block; width:100%; position:absolute; bottom:50px; font-size:20px;}
.bars p {display:block; width:100%; position:absolute; bottom:10px; font-size:10px; margin:0;}
</style>

<h1>Статистика использования того или иного интерфейса</h1>

<div class="bars">
<?php
	$higgsStat = Yii::app()->cache->get("higgsStat");
	$s = [0 => 0, 1 => 0];
	foreach($higgsStat as $uid => $iface) $s[$iface]++;
	$n = $s[0] + $s[1];
	$titles = [1 => "Пользуются новым интерфейсом", 0 => "Пользуются старым интерфейсом"];
	$bgs = [
		1 => "http://s0.tchkcdn.com/g2-YZRz499x09ztrOPemU-Mng/news/640x0/w/1/1-9-7-9-25979/8f1844ac309a05520b968908bab3be88_2012_07_04t073720z_400716851_gm1e87417br01_rtrmadp_3_science_higgs.jpg",
		0 => "http://cds.cern.ch/record/1277689/files/dirac-at-cern-by-sandra-hoogeboom_06(1)_image.jpg?subformat=icon"
	];

	for($i = 1; $i >= 0; $i--) {
		$p = round($s[$i] / $n * 100);
		echo "<div class='t{$i}' style='height: " . ($p * 3 + 100) . "px;'>";
		echo "<b style=\"background-image: url('{$bgs[$i]}')\"></b>";
		printf("<strong>%d%% <small>(%d чел)</small></strong>", $p, $s[$i]);
		echo "<p>" . $titles[$i] . "</p>";
		echo "</div>";
	}
?>
</div>

<?php if(0): ?>
<h2>Статистика использования переключателя:</h2>

<svg xmlns="http://www.w3.org/2000/svg" version="1.1" id="graph">
    <line x1="40" y1="0" x2="40" y2="360" stroke-width="2" stroke="rgb(0,0,0)" />
    <line x1="40" y1="360" x2="800" y2="360" stroke-width="2" stroke="rgb(0,0,0)" />

	<?php
		$coordsStay = [];
		$coordsLeft = [];
		foreach($data as $k => $d) {
			$n = $d["stay"] + $d["left"];
			$pStay = round($d["stay"] / $n * 100);
			$pLeft = round($d["left"] / $n * 100);

			$x = $k + 41;
			$yStay = 360 - 340 * ($d["stay"] / $n);
			$yLeft = 360 - 340 * ($d["left"] / $n);

			$coordsStay[] = "{$x}, {$yStay}";
			$coordsLeft[] = "{$x}, {$yLeft}";
		}

		echo "<polyline stroke='rgb(0,200,0)' stroke-width='2' fill='none' points='" . join(" ", $coordsStay) . "' />\n\n";
		echo "<polyline stroke='rgb(0,0,200)' stroke-width='2' fill='none' points='" . join(" ", $coordsLeft) . "' />\n\n";
	?>
</svg>

<p style="color:rgb(0,200,0)">Попробовали и остались: <b><?php printf("%d (%d%%)", $last["stay"], round($last["stay"] / $maxUsers * 100)); ?></b></p>
<p style="color:rgb(0,0,200)">Попробовали и вернулись: <b><?php printf("%d (%d%%)", $last["left"], round($last["left"] / $maxUsers * 100)); ?></b></p>

<?php endif; ?>