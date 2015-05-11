<?php
class SiteController extends Controller {
	private function getSearchTop() {
		$min_size = 10;
		$max_size = 40;
		$mc_key = "searchTop";

		$html = Yii::app()->cache->get($mc_key);
		if($html != "") return $html;

		$rows = Yii::app()->db->createCommand("
			SELECT lower(request) request, count(distinct ip) as n FROM search_history GROUP BY lower(request) ORDER BY COUNT(DISTINCT ip) DESC LIMIT 50
		")->queryAll();
		if(count($rows) < 5) return "";
		$max_n = 0; $min_n = 100000; $R = array();
		foreach($rows as $row) {
			$row['request'] = strip_tags($row['request']);

			if($row['n'] > $max_n) $max_n = $row['n'];
			if($row['n'] < $min_n) $min_n = $row['n'];

			$R[$row['request']] = $row['n'];
		}

		ksort($R);

		$html = "";
		foreach($R as $request => $n) {
			$size = round($min_size + ($n - $min_n) / ($max_n - $min_n) * ($max_size - $min_size));
			$html .= "<a href='/search/?t=" . urlencode($request) . "&from=stop' style='font-size:{$size}px'>$request</a>\n";
		}

		Yii::app()->cache->set($mc_key, $html, 600);
		return $html;
	}

	public function actionIndex() {
		if(Yii::app()->user->isGuest) {
			if(Yii::app()->request->isPostRequest && isset($_POST["login"])) {
				$user = new User("login");
				$user->setAttributes($_POST["login"]);
				$user->remember = true;
				if($user->login()) {
					$this->redirect("/");
				} else {
					Yii::app()->user->setFlash("error", $user->getError("pass"));
				}
			}
			if(p()['registerType'] == "INVITE") {
				$this->layout = "empty";
				$this->render("index_guest");
				return;
			}
		}

		$this->layout = "column1";
		$hot_key = sprintf("hot.%d.%d.%d", Yii::app()->user->ini["hot.s_lang"], Yii::app()->user->ini["hot.t_lang"], Yii::app()->user->ini["hot.img"]);
		if(!($hot = Yii::app()->cache->get($hot_key))) {
			$C = new CDbCriteria(array(
				"condition" => "t.ac_read = 'a'",
				"order" => "t.last_tr DESC NULLS LAST",
			));
			$C->limit = Yii::app()->user->ini["hot.img"] ? 12 : 36;
			if(Yii::app()->user->ini["hot.s_lang"]) $C->addCondition("t.s_lang = " . Yii::app()->user->ini["hot.s_lang"]);
			if(Yii::app()->user->ini["hot.t_lang"]) $C->addCondition("t.t_lang = " . Yii::app()->user->ini["hot.t_lang"]);

			$hot = Book::model()->findAll($C);
			Yii::app()->cache->set($hot_key, $hot, 60);
		}

		if(!($announces = Yii::app()->cache->get("announces"))) {
			$announces = Announce::model()->with("book.cat", "book.owner", "seen")->findAll(array(
				"condition" => "t.topics BETWEEN 80 AND 89 AND book.ac_read = 'a'",
				"order" => "t.cdate desc",
				"limit" => 5,
			));
			Yii::app()->cache->set("announces", $announces, 90);
		}

		if(!($blog = Yii::app()->cache->get("blog"))) {
			$blog = BlogPost::model()->common()->findAll(["limit" => 10]);
			Yii::app()->cache->set("blog", $blog, 105);
		}

  		$this->render('index', array("hot" => $hot, "searchTop" => $this->getSearchTop(), "announces" => $announces, "blog" => $blog));
	}

	public function actionPoll() {
		$params = [":poll_id" => 1, ":user_id" => Yii::app()->user->id];
		if($_GET["again"] == 1) {
			Yii::app()->db->createCommand("DELETE FROM poll_answers WHERE poll_id = :poll_id AND user_id = :user_id")->execute($params);
		} else {
			$already = Yii::app()->db->createCommand("SELECT cdate FROM poll_answers WHERE poll_id = :poll_id AND user_id = :user_id")->queryScalar($params);

			if($already) {
				Yii::app()->user->ini["poll.done"] = 1;
				Yii::app()->user->ini->save();

				$this->render("poll_already", ["when" => $already]);
				return;
			}
		}

		$Questions = include(Yii::app()->basePath . "/components/polls/1.php");

		if(isset($_POST["answer"]) || isset($_POST["custom"])) {
			$ok = true;
			$sql = "";
			$ip = isset($_SERVER["HTTP_X_REAL_IP"]) ? $_SERVER["HTTP_X_REAL_IP"] : $_SERVER["REMOTE_ADDR"];
			$params = [":user_id" => Yii::app()->user->id, ":poll_id" => 1, ":ip" => $ip];
			foreach($Questions as $q) {
				$id = (int) $q["id"];
				$answer = trim($_POST["custom"][$id]) != "" ? trim($_POST["custom"][$id]) : $_POST["answer"][$id];
				if(trim($answer) == "") {
					Yii::app()->user->setFlash("error", "Так у учёных ничего не получится. Пожалуйста, ответьте на все вопросы.");
					$ok = false;
					break;
				}
				if($sql != "") $sql .= ", ";
				$sql .= "(:poll_id, :q_id{$id}, :ip, :user_id, :answer{$id})";
				$params[":q_id{$id}"] = $id;
				$params[":answer{$id}"] = $answer;
			}
			if($ok) {
				$sql = "INSERT INTO poll_answers (poll_id, q_id, ip, user_id, answer) VALUES " . $sql;
				Yii::app()->db->createCommand($sql)->execute($params);

				Yii::app()->user->ini["poll.done"] = 1;
				Yii::app()->user->ini->save();

				$this->render("poll_thankyou");
				return;
			}
		}

		$this->render("poll", ["Questions" => $Questions]);
	}

	public function actionIni() {
		$area = $_POST["area"]; unset($_POST["area"]);

		if(in_array($area, array("hot"))) {
			foreach($_POST as $k => $v) {
				Yii::app()->user->ini->set($area . "." . $k, $v);
			}
			Yii::app()->user->ini->save();
		}

		$this->redirect("/");
	}

	public function actionDonate() {
		$this->layout = "column1";
		$this->render("donate");
	}

    public function actionHelp() {
		$this->layout = "column1";
        $this->render("help");
    }

	public function actionTOS() {
		$this->layout = "column1";
		$this->render("tos");
	}

	public function actionAd() {
		$this->layout = "column1";
		$this->render("ad");
	}

	public function actionError() {
		if($error=Yii::app()->errorHandler->error) {
			if(Yii::app()->request->isAjaxRequest)
				echo json_encode(array("error" => $error["message"]));
			else
				$this->render('error', $error);
		}
	}

	public function actionMoving() {
		$this->layout = "offline";

		if(isset($_POST["t"])) {
			$t = trim(htmlspecialchars($_POST["t"]));
			if($t == "") $this->redirect("/");
			if(mb_strtolower($t) == "хуй") $t = "А я &ndash; большой оригинал!";

			$ip = $_SERVER["HTTP_X_REAL_IP"] ?: $_SERVER["REMOTE_ADDR"];

			if(Yii::app()->db->createCommand("SELECT 1 FROM moving WHERE ip = :ip AND cdate + INTERVAL '1 minute' > now()")->queryScalar(array(":ip" => $ip))) $this->redirect("/");

			$p = array(
				":ip" => $ip,
				":x" => (int) $_POST["x"],
				":y" => (int) $_POST["y"],
				":color" => '{' . join(",", array(rand(0, 128), rand(0, 128), rand(0, 128))) . '}',
				":t" => $t,
			);

			Yii::app()->db->createCommand("INSERT INTO moving (ip, x, y, color, t) VALUES (:ip, :x, :y, :color, :t)")->execute($p);

			$this->redirect("/");
		};

		$this->render("moving");
	}

	public function actionClosed() {
	    $this->layout = "offline";
	    $this->render("closed");
	}
}