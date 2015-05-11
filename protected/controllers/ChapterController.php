<?php
Yii::import("application.components.OrigReader");
Yii::import("application.components.ReadyGenerator");

class OrigCountFixer {
	private $sql = array();
	public $max_updates = 20;
	public function add($id, $n_trs) {
		if(count($this->sql) >= $this->max_updates) return false;

		$this->sql[] = "UPDATE orig SET n_trs = " . intval($n_trs) . " WHERE id = " . intval($id) . ";";

		return true;
	}

	public function fix() {
		if(count($this->sql) == 0) return 0;
		$sql = "BEGIN;\n" . join("\n", $this->sql) . "\nCOMMIT;";
		Yii::app()->db->createCommand($sql)->execute();
		return count($this->sql);
	}
}

class ChapterController extends Controller {
	public $siteArea = "books"; // | films | phrases

	public function filters() {
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}

	public function accessRules() {
		return array(
			array('allow',
				'actions'=>array("index", "dict", "rating_describe", "rating_explain", "ready", "download", "orig", "orig_download", "go", "switchiface"),
				'users'=>array('*'),
			),
			array('allow',
				'actions'=>array("edit", "remove", "import", "import_subs", "import_text", "import_text_save", "rate_tr", "timeshift", "renum"),
				'users'=>array('@'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	/**
	 * @param integer $book_id
	 * @param integer $chap_id
	 * @param bool $check_can_read
	 * @throws CHttpException
	 * @internal param string $class
	 * @return Chapter
	 */
	protected function loadChapter($book_id, $chap_id, $check_can_read = true) {
		$book_id = (int) $book_id;
		$chap_id = (int) $chap_id;
		$chap = Chapter::model()->with("book.membership")->findByPk((int) $chap_id);

		if(!$chap) throw new CHttpException(404, "Главы не существует. Возможно, она была удалена. <a href='/book/{$book_id}/'>Вернуться к оглавлению перевода</a>.");
		if($chap->book->id != $book_id) $this->redirect($chap->book->getUrl($chap_id));

		// ac_read для всего перевода. Если нельзя в весь перевод, редиректим в оглавление перевода, пусть контроллер Book объясняет, почему да как.
		if(!$chap->book->can("read")) $this->redirect($chap->book->url);

		if($chap->book->opts_get(Book::OPTS_BAN_COPYRIGHT)) {
			$chap->book->facecontrol = Book::FC_INVITE;
			foreach(Yii::app()->params["ac_areas"] as $ac => $title) {
				if($chap->book->$ac == "a") $chap->book->$ac = "g";
			}

			$reason = BookBanReason::model()->findByPk($chap->book->id);
			if(!$reason) $reason = new BookBanReason();

			if(!$chap->book->can("read")) throw new CHttpException(403, "Сожалеем, но этот перевод заблокирован по заявке правообладателей.");
		}

		// ac_read для этой главы
		if($check_can_read && !$chap->can("read")) {
			$msg = $chap->deniedWhy;
			$msg .= "<br /><br /><a href='{$chap->book->url}'>Вернуться к оглавлению</a>.";
			throw new CHttpException(403, $msg);
		}

		return $chap;
	}

	/**
	 * @param $book_id
	 * @param $chap_id
	 * @param int $show
	 * @param null $show_user
	 * @param null $tt
	 * @param null $to
	 * @throws CHttpException
	 */
	public function actionIndex($book_id, $chap_id, $show = 0, $show_user = null, $tt = null, $to = null) {
		$this->layout = "column1";

		$chap = $this->loadChapter($book_id, $chap_id);
		$filter = new TrFilter();
		$filter->setAttributes($_GET);
		$filter->validate();

		$f = new Orig();
		$f->with("seen", "bookmark", "trs.user")->chapter($chap->id);
		$crit = new CDbCriteria(array(
			"order" => ($chap->book->typ == "S" ? "t.t1" : "t.ord")
		));
		// $crit->mergeWith($filter->getCriteria()) или $filter->modifyCriteria($crit)
		if($filter->show == 1) {
			// Непереведённое
			$crit->addCondition("t.n_trs = 0");
		} elseif($filter->show == 7) {
			// >1 варианта
			$crit->addCondition("t.n_trs > 1");
		} elseif($filter->show == 2) {
			// От пользователя
			$u = User::model()->findByAttributes(array("login" => $filter->show_user));
			if(!$u) {
				$filter->show = 0;
				Yii::app()->user->setFlash("error", "Пользователя " . CHtml::encode($filter->show_user) . " не существует.");
			} else {
				$crit->addCondition("trs.user_id = {$u->id}");
				$f->together();
			}
		} elseif($filter->show == 3) {
			// С комментариями
			$crit->mergeWith(array("condition" => "t.n_comments > 0"));
		} elseif($filter->show == 4) {
			// С новыми комментариями
			$crit->mergeWith(array("condition" => "COALESCE(seen.n_comments, 0) < t.n_comments"));
		} elseif($filter->show == 5) {
			// Оригинал содержит
			$to = trim(strip_tags($to));
			if($to == "") {
				$filter->show = 0;
			} else {
				$crit->mergeWith(array(
					"condition" => "t.body ILIKE :like",
					"params" => array(":like" => "%" . addcslashes($to, "%_") . "%"),
				));
			}
		} elseif($filter->show == 6) {
			// Перевод содержит
			$tt = trim(strip_tags($tt));
			if($tt == "") {
				$filter->show = 0;
			} else {
				$crit->mergeWith(array(
					"condition" => "trs.body ILIKE :like",
					"params" => array(":like" => "%" . addcslashes($tt, "%_") . "%"),
				));
				$f->together();
			}
		}

		$orig_dp = new CActiveDataProvider($f, array(
			"criteria" => $crit,
			"pagination" => array("pageSize" => 50)
		));
		if($filter->show == 0) $orig_dp->totalItemCount = $chap->n_verses;

		$chap->book->registerJS();
		$chap->registerJS();
		$view = "index-" . intval(Yii::app()->user->ini["t.iface"]);
		$this->render($view, array(
			"chap" => $chap, "orig_dp" => $orig_dp, "filter" => $filter,
			"show" => $show, "show_user" => $show_user, "to" => $to, "tt" => $tt
		));
	}

	public function actionSwitchiface($book_id, $chap_id) {
		$book_id = (int) $book_id; $chap_id = (int) $chap_id;
		$user = Yii::app()->user;

		$user->ini->set("t.iface", $user->ini["t.iface"] == 1 ? 0 : 1);
		$user->ini->save();

		file_put_contents(
			Yii::app()->basePath . "/runtime/higgs.log",
			date("Y-m-d H:i:s") . "\t" . ($user->isGuest ? "<guest>" : $user->login) . "\t" . $user->ini["t.iface"] . "\n",
			FILE_APPEND
		);


		$this->redirect("/book/{$book_id}/{$chap_id}");
	}

	public function actionGo($book_id, $chap_id, $nach, $ord) {
		$ord = (int) $ord;
		$book_id = (int) $book_id;
		$chap_id = (int) $chap_id;

		if($nach == "prev") $sql = "SELECT id FROM chapters WHERE book_id = :book_id AND ord < :ord ORDER BY ord desc LIMIT 1";
		else $sql = "SELECT id FROM chapters WHERE book_id = :book_id AND ord > :ord ORDER BY ord LIMIT 1";

		$id = Yii::app()->db->createCommand($sql)->queryScalar(array(":book_id" => $book_id, ":ord" => $ord));
		if($id) {
			$get = $_GET;
			unset($get["book_id"]);
			unset($get["chap_id"]);
			unset($get["nach"]);
			unset($get["ord"]);
			$this->redirect("/book/{$book_id}/{$id}" . (count($get) ? "?" . http_build_query($get) : ""));
		}
		else $this->redirect("/book/{$book_id}");
	}

	public function actionTimeshift($book_id, $chap_id) {
		$chap = $this->loadChapter($book_id, $chap_id);
		if(!$chap->book->can("chap_edit")) throw new CHttpException(403, "Вы не можете изменять оригинал в этом переводе.");
		if($chap->book->typ != "S") throw new CHttpException(404, "Изменять тайминг можно только у субтитров.");

		foreach(array("from", "to", "value") as $k) {
			if($_POST[$k] != "" && !preg_match('/^-?\d+:\d+:\d+\.\d+$/', $_POST[$k], $res)) {
				Yii::app()->user->setFlash("error", "Вы указали время в неправильном формате (" . CHtml::encode($_POST[$k]) . "). Хотелось бы видеть что-то вроде 01:23:45.678 (что означает 1 час 23 минуты 45 целых 678 сотых секунды).");
				$this->redirect($chap->url);
			}
		}

		$value = trim(strip_tags($_POST["value"]));

		$sql = "UPDATE orig SET t1 = t1 + interval :shift, t2 = t2 + interval :shift WHERE chap_id = :chap_id";
		$params = array(":shift" => $value, ":chap_id" => $chap->id);

		if(isset($_POST["from"]) && isset($_POST["to"]) && ($_POST["from"] != "00:00:00.000" || $_POST["to"] != "23:59:59.999")) {
			$sql .= " AND t1 >= :from AND t2 <= :to";
			$params[":from"] = $_POST["from"];
			$params[":to"] = $_POST["to"];
		}

		Yii::app()->db->createCommand($sql)->execute($params);

		$this->redirect($chap->url);
	}

	public function actionRenum($book_id, $chap_id) {
		$chap = $this->loadChapter($book_id, $chap_id);
		if(!$chap->book->can("chap_edit")) throw new CHttpException(403, "Вы не можете изменять оригинал в этом переводе.");
		if($chap->book->typ != "S") throw new CHttpException(404, "Изменять тайминг можно только у субтитров.");

		$mode = (int) $_POST["mode"];
		if($mode == 1) {
			Yii::app()->db->createCommand("
				WITH o AS (SELECT row_number() OVER (order by t1) as ord, id FROM orig WHERE chap_id = :chap_id ORDER BY t1)
				UPDATE orig SET ord = o.ord FROM o WHERE orig.id = o.id
			")->execute(array(":chap_id" => $chap->id));
		}

		$this->redirect($chap->url);
	}

	public function actionReady($book_id, $chap_id) {
		$chap = $this->loadChapter($book_id, $chap_id);
		if(!$chap->can("gen")) throw new CHttpException(403, "Вы не можете скачивать готовый перевод. " . $chap->getWhoCanDoIt("gen"));

		$options = new GenOptions();
		$options->chap = $chap;
		$options->loadOptions();
		$get = $_GET; unset($get["chap_id"]); unset($get["book_id"]);
		$options->setAttributes($get);
		$options->validate();
		$options->saveOptions();

		$authors = Yii::app()->db
			->createCommand("SELECT u.id, u.login FROM translate t LEFT JOIN users u ON t.user_id = u.id WHERE t.chap_id = :chap_id GROUP BY u.id ORDER BY u.login")
			->queryAll(true, [":chap_id" => $chap->id]);

		if($chap->book->typ == "A") {
			$options->format = "h";
			if(!$chap->can("gen_untr")) $options->untr = "s";
			$orig = $this->prepareReadyOrig($chap, $options);
			$G = ReadyGenerator::factory($options, $chap, $orig);
		}

		if($chap->status != Chapter::STATUS_NONE && $chap->status != Chapter::STATUS_READY) {
			Yii::app()->user->setFlash("info", "Внимание! Этот перевод, возможно, ещё не готов, так как модераторы установили для него статус &laquo;" . Yii::app()->params["translation_statuses"][$chap->status] . "&raquo;");
		}

		if($chap->n_vars == 0) {
			$this->render("ready_empty", array("chap" => $chap));
		} elseif($chap->book->typ == "S") {
			$this->render("ready", array("chap" => $chap, "options" => $options, "authors" => $authors));
		} elseif($chap->book->typ == "A") {
			$this->side_view = array("ready_read_side" => array("chap" => $chap, "options" => $options, "authors" => $authors));
			$this->render("ready_read", array("chap" => $chap, "generator" => $G));
		} else {
			throw new CHttpException(404, "Эта страница должна быть, но её, тем не менее, нет.");
		}
	}

	/** @return Orig[] */
	private function prepareReadyOrig($chap, $options) {
		$f = Orig::model()->with("trs.user")->chapter($chap->id);

		$crit = new CDbCriteria([
			"order" => ($chap->book->typ == "S" ? "t.t1" : "t.ord") . ", " . ($options->algorithm == 1 ? "trs.cdate desc" : "trs.rating desc, trs.cdate desc")
		]);

		if($options->author_id != 0) {
			$crit->addCondition("trs.user_id = :user_id");
			$crit->params[":user_id"] = $options->author_id;
		}

		/**
		 * по идее, хорошо бы тут
		 * if($options->skip_neg) LEFT JOIN transtate trs ON trs.orig_id = t.id AND trs.rating >= 0
		 * но хуй его знает, как это сделать в Yii
		 */

		$orig = $f->findAll($crit);

		return $orig;
	}

	private function rcLog($txt, $chap, $options) {
		file_put_contents(
			$_SERVER["DOCUMENT_ROOT"] . "/../protected/runtime/cache" . date("Y-m") . ".log",
			date("Y-m-d H:i:s") . "\t{$chap->id}({$options->rcKey})\t{$txt}\n",
			FILE_APPEND
		);
	}

	public function actionDownload($book_id, $chap_id) {
		$chap = $this->loadChapter($book_id, $chap_id);
		if(!$chap->can("gen")) throw new CHttpException(403, "Вы не можете скачивать готовый перевод. " . $chap->getWhoCanDoIt("gen"));

		$options = new GenOptions();
		$options->chap = $chap;
		$get = $_GET; unset($get["chap_id"]); unset($get["book_id"]);
		$options->setAttributes($get);
		if(!$options->validate()) {
			$this->redirect($chap->getUrl("ready?" . $_SERVER["QUERY_STRING"]));
		}
		$options->saveOptions();
		if(!$chap->can("gen_untr")) $options->untr = "s";

		$modified = $chap->getModified();
		$rcKey = $options->rcKey;
		$data = Yii::app()->readycache->get($options->rcKey);
		$rcMTime = $data === false ? false : Yii::app()->readycache->getCacheFileMTime($rcKey);

//		$this->rcLog(
//			sprintf(
//				"chap.modified=%s, cache file mtime=%s, data=%s",
//				date("Y-m-d H:i:s", $modified),
//				date("Y-m-d H:i:s", $rcMTime),
//				$data === false ? "false" : strlen($data) . " bytes"
//			), $chap, $options);

		// Перегенерируем, если в readycache ничего нет, либо если результат некешируем, либо если глава изменилась с момента последнего скачивания
		if($rcKey === false || $data === false || $modified >= $rcMTime) {
			// Generate
			$orig = $this->prepareReadyOrig($chap, $options);
			$G = ReadyGenerator::factory($options, $chap, $orig);
			$data = $G->generate(true);
			if($options->enc != "UTF-8") $data = iconv("UTF-8", $options->enc . "//TRANSLIT", $data);
			else $data = sprintf("%c%c%c", 0xEF, 0xBB, 0xBF) . $data;

			// Пишем в readycache, если результат кешируем
			if($rcKey !== false) {
				$this->rcLog("PUT TO CACHE", $chap, $options);
				Yii::app()->readycache->set($rcKey, $data);
				if($modified == 0) $chap->setModified();
			} else {
				$this->rcLog("UNCACHEABLE (author_id={$options->author_id})", $chap, $options);
			}
		} else {
			$this->rcLog("CACHED", $chap, $options);
		}

		if(1) {
			$logRouter = Yii::app()->getComponent("log");
			if(isset($logRouter)) {
				$routes = $logRouter->getRoutes();
				foreach ($routes as $route) {
					if ($route instanceof CWebLogRoute) { $route->enabled = false; }
				}
			}

			$fname = str_replace("\"", "", $chap->title);
			$fname = str_replace(" ", "_", $fname) . "." . GenOptions::$extensions[$options->format];
			Yii::app()->request->sendFile($fname, $data, "application/octet-stream", false);

			// Счётчик скачиваний
			$ip = $_SERVER["HTTP_X_REAL_IP"] ? $_SERVER["HTTP_X_REAL_IP"] : $_SERVER["REMOTE_ADDR"];
			$p = array(":book_id" => $chap->book->id, ":chap_id" => $chap->id, ":ip" => $ip);
			$sql = "SELECT downloaded_book(:book_id, :chap_id, :ip, NULL)";
			if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
				$via = $ip;
				$a = explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);
				$ip = trim($a[0]);

				$sql = "SELECT downloaded_book(:book_id, :chap_id, :ip, :via)";
				$p[":via"] = $via;
			}
			try {
				$new_dl = Yii::app()->db->createCommand($sql)->queryScalar($p);
			} catch(CDbException $e) {
				file_put_contents(
					$_SERVER["DOCUMENT_ROOT"] . "/../protected/runtime/downloaded_book.err.log",
					"Error in downloaded_book({$chap->book->id}, {$chap->id}, {$ip}, {$via}): " . $e->getMessage() . "\n\n", FILE_APPEND
				);
				$new_dl = false;
			}

			if($new_dl) {
				$chap->n_dl++;
				$chap->n_dl_today++;
				$chap->book->n_dl++;
				$chap->book->n_dl_today++;
			}

		} else {
			echo "<h3>Result (" . strlen($data) . ") bytes</h3><pre>";
			echo htmlspecialchars($data);
			echo "</pre>";
		}
	}

	public function actionImport($book_id, $chap_id) {
		$chap = $this->loadChapter($book_id, $chap_id);

		if($chap->book->typ == "A") {
			$options = new TextSource();

			if(isset($_POST["TextSource"])) {
				$options->setAttributes($_POST["TextSource"]);
				if($options->validate()) {
					$this->render("import_A_2", array("chap" => $chap, "text" => $options->prepareText($chap)));
					return;
				} else {
					Yii::app()->user->setFlash("error", $options->getErrorsString());
				}
			}

			$this->render("import_A_1", array("chap" => $chap, "options" => $options));
		} elseif($chap->book->typ == "S") {
			$options = new ImportOptionsSubs();

			$this->render("import_S", array("chap" => $chap, "options" => $options));
		}
	}

	public function actionImport_text_save($book_id, $chap_id) {
		$chap = $this->loadChapter($book_id, $chap_id);

		if(!isset($_POST["t"])) $this->redirect($chap->getUrl("import"));

		$chap->clean();
		$n_verses = 0;
		$writer = new OrigWriter();

		foreach($_POST["t"]["txt"] as $ord => $body) {
			if($body == "") continue;

			$orig = new Orig();
			$orig->chap = $chap;
			$orig->chap_id = $chap->id;
			$orig->ord = (int) $ord + 1;

			$orig->setAttributes(array("body" => trim($body)));
			if($orig->body == "") continue;

			$n_verses++;
			$writer->push($orig);
		}
		$writer->flush();

		$chap->n_verses += $n_verses;
		$chap->book->n_verses += $n_verses;
		$chap->save(false, array("n_verses"));
		$chap->book->save(false, array("n_verses"));

		$this->redirect($chap->url);
	}

	public function actionImport_subs($book_id, $chap_id) {
		$chap = $this->loadChapter($book_id, $chap_id);

		if(!isset($_POST["ImportOptionsSubs"])) $this->redirect($chap->getUrl("import"));

		$options = new ImportOptionsSubs();
		$options->setAttributes($_POST["ImportOptionsSubs"]);
		$options->src = CUploadedFile::getInstance($options, "src");

		if($options->validate()) {
			$chap->clean();

			$reader = OrigReader::factory($options, $chap);
			$writer = new OrigWriter();

			try {
//				echo "<pre>";
				$reader->init();
				$n_verses = 0;
				while(!$reader->is_eod()) {
					$orig = $reader->getNextVerse();
					if($orig === false) break;

					$n_verses++;
					$writer->push($orig);
				}
				$writer->flush();
//				echo "</pre>";
//				exit;
			} catch (CDbException $e) {
				// Произошла ошибка записи в базу данных, скорее всего - хуёвая кодировка.
				Yii::app()->user->setFlash("error", "Произошла ошибка базы данных. Скорее всего, ваш файл сохранён в неправильной кодировке. Или что-то сломалось у нас, но без паники, мы уже пытаемся это починить.");
				$this->redirect($chap->getUrl("import"));
			} catch (OrigReaderException $e) {
				// Ошибка Reader'а
				Yii::app()->user->setFlash("error", $e->getMessage());
				$this->redirect($chap->getUrl("import"));
			}

			if($reader->hasError()) {
				Yii::app()->user->setFlash("error", $reader->getError());
				$this->redirect($chap->getUrl("import"));
			}

			$chap->n_verses += $n_verses;
			$chap->book->n_verses += $n_verses;
			$chap->save(false, array("n_verses"));
			$chap->book->save(false, array("n_verses"));

			$this->redirect($chap->url);
		} else {
            $this->redirect($chap->getUrl("import"));
        }
	}

	public function actionOrig($book_id, $chap_id) {
		$chap = $this->loadChapter($book_id, $chap_id);
		if($chap->book->typ != "S") $this->redirect($chap->book->url);

		$options = new GenOptions();
		$options->chap = $chap;
		$options->loadOptions();
		$options->setAttributes($_GET);
		$options->validate();
		$options->saveOptions();

		if($chap->book->typ == "S") {
			$this->render("orig", array("chap" => $chap, "options" => $options));
		}
	}

	public function actionOrig_download($book_id, $chap_id) {
		$chap = $this->loadChapter($book_id, $chap_id);

		$options = new GenOptions();
		$options->chap = $chap;
		$options->setAttributes($_GET);
		if(!$options->validate()) {
			$this->redirect($chap->getUrl("orig?" . $_SERVER["QUERY_STRING"]));
		}
		$options->saveOptions();

		$crit = new CDbCriteria(array(
			"order" => $chap->book->typ == "S" ? "t.t1" : "t.ord"
		));
		$orig = Orig::model()->chapter($chap->id)->findAll($crit);

		$G = ReadyGenerator::factory($options, $chap, $orig);
		$data = $G->generateOrig(true);

		if($options->enc != "UTF-8") {
			$data = iconv("UTF-8", $options->enc . "//IGNORE", $data);
		}

		if(1) {
			$logRouter = Yii::app()->getComponent("log");
			if(isset($logRouter)) {
				$routes = $logRouter->getRoutes();
				foreach ($routes as $route) {
					if ($route instanceof CWebLogRoute) { $route->enabled = false; }
				}
			}

			$fname = str_replace(" ", "_", $chap->title . "-orig." . GenOptions::$extensions[$options->format]);
			Yii::app()->request->sendFile($fname, $data);
		} else {
			echo "<h3>Result (" . strlen($data) . ") bytes</h3><pre>";
			echo htmlspecialchars($data);
			echo "</pre>";
		}
	}

	public function actionEdit($book_id, $chap_id) {
		$chap_id = (int) $chap_id;
		$book_id = (int) $book_id;
		$ajax = $_GET["ajax"] == 1;

		if($chap_id != 0) {
			$chap = $this->loadChapter($book_id, $chap_id, false);
			if(!$chap->book->can("read")) throw new CHttpException(403, "Доступ в перевод закрыт его владельцем.");
		} else {
			$chap = new Chapter();

			$chap->book = Book::model()->with("membership")->findByPk($book_id);
			if(!$chap->book) throw new CHttpException(404, "Вы пытаетесь создать новую главу в несуществующем переводе.");

			$chap->book_id = $book_id;
		}

		if(!$chap->book->can("chap_edit")) {
			throw new CHttpException(403, "Вы не можете редактировать оглавление в этом переводе.");
		}

		// $overridedId - ID главы с особыми правами в этом переводе
		if(($chap->isNewRecord && $chap->book->n_chapters == 0) || (!$chap->isNewRecord && $chap->book->n_chapters <= 1)) {
			$overridedId = -1;
		} elseif($chap->hasOverride) {
			$overridedId = $chap->id;
		} else {
			$overridedId = Yii::app()->db
				->createCommand("SELECT id FROM chapters WHERE book_id = :book_id AND (ac_read != '' OR ac_trread != '' OR ac_gen != '' OR ac_rate != '' OR ac_comment != '' OR ac_tr != '')")
				->queryScalar(array(":book_id" => $chap->book_id));
		}

		if(count($_POST["Chapter"]) > 0) {
			if($chap_id == 0) {
				// Будущий ord
				if($_GET["placement"] == -1) {
					$chap->ord = Yii::app()->db->createCommand("SELECT MIN(ord) FROM chapters WHERE book_id = :book_id")->queryScalar(array(":book_id" => $book_id)) - 1;
				} else {
					$chap->ord = Yii::app()->db->createCommand("SELECT MAX(ord) FROM chapters WHERE book_id = :book_id")->queryScalar(array(":book_id" => $book_id)) + 1;
				}
			}
			$old_status = $chap->status;
			$chap->setAttributes($_POST["Chapter"]);
			if($chap->save()) {
				$chap->setModified();

				if($chap_id == 0 || $old_status != $chap->status) {
					$notify = User::model()->watchers($chap->book_id)->findAll();

					foreach($notify as $u) {
						if($chap_id == 0) {
							$u->Notify(Notice::CHAPTER_ADDED, $chap->book, $chap);
						} elseif($old_status != $chap->status) {
							$u->Notify(Notice::CHAPTER_STATUS, $chap->book, $chap, Yii::app()->params["translation_statuses"][$chap->status]);
						}
					}
				}

				$this->redirect($chap->book->url);
			} else {
				Yii::app()->user->setFlash("error", $chap->getErrorsString());
				$this->redirect($chap->book->url . "#ed={$chap->id}");
			}
		}

		$p = array("chap" => $chap, "ajax" => $ajax, "overridedId" => $overridedId);
		if($ajax) $this->renderPartial("edit", $p);
		else $this->render("edit", $p);
	}

	public function actionRemove($book_id, $chap_id) {
		$chap = $this->loadChapter($book_id, $chap_id);

		if(!$chap->book->can("chap_edit")) {
			throw new CHttpException(403, "Вы не можете удалять главы в этом переводе.");
		}

		if($_POST["really"] == 1) {
			$chap->setModified();
			$chap->delete();
			$this->redirect($chap->book->url);
		}
	}

	public function actionRate_tr($book_id, $chap_id) {
		$chap = $this->loadChapter($book_id, $chap_id);
		if(!$chap->can("rate") || !$chap->can("trread")) throw new CHttpException(403, "Вы не можете оценивать версии в этом переводе.");

		$user = Yii::app()->user->model;

		// Запрет на голосование людям с отрицательной кармой снят, так как минусы могут лепить только модераторы
//		if($user->rate_u < 0 && $chap->book->membership->status != GroupMember::MODERATOR)
//			throw new CHttpException(403, "Вы не можете ставить оценки переводам потому что у вас отрицательная карма.");

		$id = (int) $_POST["id"];

		/** @var Translation $tr */
		$tr = Translation::model()->with("mark", "user")->findByPk($id, array("condition" => "chap_id = :chap_id", "params" => array(":chap_id" => $chap->id)));
		if(!$tr) throw new CHttpException(404, "Версия перевода удалена.");

		if($tr->user_id == $user->id) throw new CHttpException(403, "Нельзя ставить оценки собственным переводам.");

		$mark = (int) $_POST["mark"];
		if($mark < -1) $mark = -1;
		elseif($mark > 1) $mark = 1;

		if($mark < 0 && $chap->book->membership->status != GroupMember::MODERATOR)
			throw new CHttpException(403, "Только модераторы могут ставить минусы.");

		$JSON = array("id" => $id);

		$sql = array();
		$sql_params = array(":user_id" => $user->id, ":id" => $tr->id);
		$d_rating = $d_n_votes = 0;

		if($tr->mark) {
			// Я уже оценивал этот перевод
			$d_rating = $mark - $tr->mark->mark;
			if($mark == 0) {
				$sql[] = "DELETE FROM marks WHERE user_id = :user_id AND tr_id = :id;";
				$d_n_votes = -1;
				$tr->n_votes--;
			} else {
				if($d_rating != 0) $sql[] = "UPDATE marks SET mark = :mark WHERE user_id = :user_id AND tr_id = :id;";
				$sql_params[":mark"] = $mark;
			}
		} else {
			// Новая оценка
			$d_rating = $mark;
			$d_n_votes = 1;
			$sql[] = "INSERT INTO marks (user_id, tr_id, mark) VALUES (:user_id, :id, :mark);";
			$sql_params[":mark"] = $mark;
			$tr->n_votes++;
		}

		if($d_rating != 0) {
			$tr->rating += $d_rating;

			// Рейтинг перевода
			$sql[] = "UPDATE translate SET rating = rating + :d_rating, n_votes = n_votes + :d_n_votes WHERE id = :id;";
			$sql_params[":d_rating"] = $d_rating;
			$sql_params[":d_n_votes"] = $d_n_votes;

			// Рейтинг автора перевода
			$sql[] = "UPDATE users SET rate_t = rate_t + :d_rating WHERE id = :author_id;";
			$sql_params[":author_id"] = $tr->user_id;

			// Рейтинг автора в группе
			$sql[] = "UPDATE groups SET rating = rating + :d_rating WHERE book_id = :book_id AND user_id = :author_id;";
			$sql_params[":book_id"] = $chap->book_id;
		}

		if(count($sql)) {
			$sql_all = "BEGIN;\n" . join("\n", $sql) . "\nCOMMIT;";
			// echo strtr($sql_all, $sql_params) . "\n";
			Yii::app()->db->createCommand($sql_all)->execute($sql_params);
			$chap->setModified();
		}

		$JSON["rating"] = $tr->rating;

		echo json_encode($JSON);
	}

	/**
	 * OBSOLETE: Отдаёт список проголосовавших за вариант перевода $_GET["id"] в HTML
	 * @param $book_id
	 * @param $chap_id
	 * @throws CHttpException
	 */
	public function actionRating_describe($book_id, $chap_id) {
		$chap = $this->loadChapter($book_id, $chap_id);

		/** @var Translation $tr */
		$tr = Translation::model()->with("marks.user")->findByPk((int) $_GET["id"]);
		if(!$tr) throw new CHttpException(404, "Версия перевода удалена.");

		// Проверяем, верны ли показания в translate.rating и translate.n_votes, раз уж мы всё равно загрузили все оценки
		$n_votes = count($tr->marks);
		$rating = 0;
		foreach($tr->marks as $mark) {
			$rating += $mark->mark;
		}

		// autofix translate.rating, translate.n_votes
		if($n_votes != $tr->n_votes || $rating != $tr->rating) {
			$tr->n_votes = $n_votes;
			$tr->rating = $rating;
			$tr->save(false, array("rating", "n_votes"));
		}

		$this->renderPartial("rating_describe", array("tr" => $tr, "chap" => $chap));
	}

	/**
	 * Отдаёт список проголосовавших за вариант перевода $_GET["id"] в JSON
	 * @param $book_id
	 * @param $chap_id
	 * @throws CHttpException
	 */
	public function actionRating_explain($book_id, $chap_id) {
		$chap = $this->loadChapter($book_id, $chap_id);

		/** @var Translation $tr */
		$tr = Translation::model()->with("marks.user")->findByPk((int) $_GET["id"]);
		if(!$tr) throw new CHttpException(404, "Версия перевода удалена.");

		// Проверяем, верны ли показания в translate.rating и translate.n_votes, раз уж мы всё равно загрузили все оценки
		$n_votes = count($tr->marks);
		$rating = 0;
		foreach($tr->marks as $mark) {
			$rating += $mark->mark;
		}

		// Autofix: translate.rating, translate.n_votes
		if($n_votes != $tr->n_votes || $rating != $tr->rating) {
			$tr->n_votes = $n_votes;
			$tr->rating = $rating;
			$tr->save(false, array("rating", "n_votes"));
		}

		$json = array();
		foreach(array_reverse($tr->marks) as $mark) {
			$json[] = array(
				"id" => $mark->user->id,
				"login" => $mark->user->login,
				"mark" => $mark->mark
			);
		}
		echo json_encode($json);
	}
}
