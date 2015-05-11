<?php
class DungeonController extends Controller {
	public function filters() {
		return array(
			'accessControl',
		);
	}

	public function accessRules() {
		return array(
			array('allow',  // allow all users
				'actions'=>array("index", "higgsInfo"),
				'users'=>array('@'),
			),
			[
				"allow", "users" => ["*"], "actions" => ["pollResults"]
			],
			[
				"allow", "users" => ["notabenoid"], "actions" => ["pollRawResults"]
			],
			['deny', 'users' => ['*']]
		);
	}

	public function actionHiggsInfo() {
/*
		$fh = fopen(Yii::app()->basePath . "/runtime/higgs.log", "r");
		$startHour = null;
		$data = [];
		$users = [];
		$stay = 0;
		$left = 0;
		$prevDay = null;
		while(!feof($fh)) {
			$t = trim(fgets($fh));
			if($t == "") continue;
			list($timestamp, $login, $state) = explode("\t", $t);
			$ts = strtotime($timestamp);

			$day = intval(date("z", $ts));
			$hour = $day * 24 + date("H", $ts);
			if($startHour === null) $startHour = $hour;
			$hour -= $startHour;

			if(!isset($users[$login])) {
				if($state == 1) {
					$stay++;
				} else {
					$left++;
				}
			} elseif($users[$login] == 0 && $state == 1) {
				$left--; $stay++;
			} elseif($users[$login] == 1 && $state == 0) {
				$stay--; $left++;
			}

			$data[$hour]["stay"] = $stay;
			$data[$hour]["left"] = $left;

			if($day != $prevDay) {
				$data[$hour]["label"] = date("d.m.Y", $ts);
				$prevDay = $day;
			}

			$users[$login] = $state;
		}
*/
		$this->render("higgsInfo");
	}

	public function actionPollResults() {
		$Questions = include(Yii::app()->basePath . "/components/polls/1.php");

		$res = Yii::app()->db
			->createCommand("select count(*) n, q_id, answer FROM poll_answers GROUP BY q_id, answer ORDER BY q_id, n desc")
			->queryAll();
		$data = [];
		foreach($res as $row) {
			$data[$row["q_id"]][] = ["n" => $row["n"], "answer" => $row["answer"]];
		}

		$this->render("pollresults", ["data" => $data, "Questions" => $Questions]);
	}

	function actionPollRawResults() {
		$res = Yii::app()->db
			->createCommand("select u.id # 24772 as user_id, u.cdate as user_cdate, u.n_trs, u.rate_t, u.rate_u, u.n_comments, a.q_id, a.cdate, a.ip, a.answer from poll_answers a left join users u on a.user_id = u.id")
			->queryAll();

		$header = ["ID пользователя", "Дата регистрации", "Количество переводов пользователя", "Рейтинг переводов пользователя", "Карма пользователя", "Количество комментариев пользователя", "Номер вопроса", "Время ответа", "IP", "Ответ"];
		$data = join(";", $header) . "\r\n";

		foreach($res as $row) {
			$data .= '' . join(';', $row) . "\r\n";
		}

		$data = iconv("utf-8", "CP1251", $data);

		Yii::app()->request->sendFile("poll-1.csv", $data, "application/octet-stream", false);

		exit;
	}
}