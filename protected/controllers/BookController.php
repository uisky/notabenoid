<?php
class BookController extends BookBaseController {
	/** @var Book $book */
	public $book = null;

	public function filters() {
		return array('accessControl');
	}

	public function accessRules() {
		return [
			['allow', 'users' => ['*'], 'actions' => array("index", "chapters", "members", "dict")],
			['allow', 'users' => ['@'], 'actions' => [
				"reorder", "members_join", "members_leave", "members_manage", "invite_accept", "invite_decline",
				"dict_edit", "dict_rm", "dict_copy",
				"recalc",
			]],
			["allow", "actions" => ["ban_copyright"], "expression" => '$user->can("admin")'],
			['deny', 'users' => ['*']],
		];
	}


	public function actionIndex($book_id) {
		$this->loadBook($book_id);

		$chapters = $this->book->chapters;
		foreach($chapters as $chap) {
			$chap->book = $this->book;
		}

		$this->side_view = "index_side";
		$this->side_params = array("book" => $this->book);

		$this->render("index", array("book" => $this->book, "chapters" => $chapters));
	}

	public function actionChapters($book_id) {
		$book = $this->loadBook($book_id);

		$json = array();
		foreach($this->book->chapters as $chap) {
			$json[] = array("id" => $chap->id, "title" => $chap->title);
		}

		echo json_encode($json);
	}

	public function actionReorder($book_id) {
		$this->loadBook($book_id);

		if(!$this->book->can("chap_edit")) throw new CHttpException(403, "Вы не можете редактировать оглавление в этом переводе.");

		if(!is_array($_POST["ord"])) $this->redirect($this->book->url);

		$sql = "BEGIN;\n";
		foreach($_POST["ord"] as $ord => $id) {
			$id = (int) $id; $ord = (int) $ord;
			$sql .= "UPDATE chapters SET ord = '{$ord}' WHERE book_id = '{$this->book->id}' AND id = '{$id}';\n";
		}
		$sql .= "COMMIT;\n";

		Yii::app()->db->createCommand($sql)->execute();

		$this->redirect($this->book->url);
	}

	public function actionRecalc($book_id) {
		$book = $this->loadBook($book_id);
		$user = Yii::app()->user;
		$full = $user->can("geek") && $_POST["full"];

		if(!$user->can("geek")) {
			$did = Yii::app()->db
				->createCommand("SELECT 1 FROM recalc_log WHERE book_id = :book_id AND dat + INTERVAL '1 HOUR' > now()")
				->queryScalar(array(":book_id" => $book->id));
			if($did) {
				$this->render("recalc_deny", array("book" => $book));
				return;
			}
		}

		$sql = "BEGIN;\n";

		if($full) {
			$sql .= <<<SQL
-- translate.rating, n_votes. на это есть автофикс
UPDATE translate SET n_votes = 0, rating = 0 WHERE book_id = :book_id;
WITH stats AS (SELECT tr_id, COUNT(*) n_votes, SUM(mark) rating FROM marks WHERE tr_id IN (SELECT id FROM translate WHERE book_id = :book_id) GROUP BY tr_id)
UPDATE translate SET n_votes = stats.n_votes, rating = stats.rating FROM stats WHERE translate.id = stats.tr_id;

-- orig.n_trs. на это тоже есть автофикс, считается долго
UPDATE orig SET n_trs = 0, n_comments = 0 WHERE chap_id IN(SELECT id FROM chapters WHERE book_id = :book_id);
WITH stats AS (SELECT orig_id, COUNT(*) cnt FROM translate WHERE book_id = :book_id GROUP BY orig_id)
UPDATE orig SET n_trs = stats.cnt FROM stats WHERE orig.id = stats.orig_id;

-- orig.n_comments. и здесь автофикс! и тоже долго считается
WITH stats AS (
				SELECT orig_id, COUNT(*) cnt
	FROM comments
	WHERE
		orig_id IN(SELECT o.id FROM orig o LEFT JOIN chapters c ON o.chap_id = c.id WHERE c.book_id = :book_id) AND
		NOT (user_id IS NULL AND body = '')
	GROUP BY orig_id
)
UPDATE orig SET n_comments = stats.cnt FROM stats WHERE orig.id = stats.orig_id;
SQL;
		}

		$sql .= <<<SQL
-- chapters.n_verses
UPDATE chapters SET n_verses = (SELECT COUNT(*) FROM orig WHERE chap_id = chapters.id) WHERE book_id = :book_id;

-- chapters.n_vars, d_vars
UPDATE chapters SET n_vars = 0, d_vars = 0 WHERE book_id = :book_id;
WITH stats AS (
	SELECT chap_id, COUNT(*) AS n, COUNT(DISTINCT orig_id) AS d FROM translate WHERE book_id = :book_id GROUP BY chap_id
)
UPDATE chapters SET n_vars = n, d_vars = d FROM stats WHERE stats.chap_id = chapters.id;


-- books.n_chapters, n_verses, n_vars, d_vars
UPDATE books SET n_chapters = 0, n_verses = 0, n_vars = 0, d_vars = 0 WHERE id = :book_id;
WITH stats AS (SELECT book_id, COUNT(*) cnt, SUM(n_verses) nv, SUM(n_vars) n, SUM(d_vars) d FROM chapters WHERE book_id = :book_id GROUP BY book_id)
UPDATE books SET n_chapters = cnt, n_verses = nv, n_vars = n, d_vars = d FROM stats WHERE stats.book_id = books.id;


-- groups.n_trs, rating
UPDATE groups SET n_trs = 0, rating = 0 WHERE book_id = :book_id;
WITH stats AS (SELECT book_id, user_id, COUNT(*) as cnt, SUM(rating) as sum FROM translate WHERE book_id = :book_id GROUP BY book_id, user_id)
UPDATE groups SET n_trs = cnt, rating = sum FROM stats WHERE stats.book_id = groups.book_id AND stats.user_id = groups.user_id;

-- recalc_log
INSERT INTO recalc_log (book_id, user_id) VALUES (:book_id, :user_id);

COMMIT;
SQL;

		if($_POST["go"] == 1) {
			Yii::app()->db->createCommand($sql)->execute(array(":book_id" => $book->id, ":user_id" => $user->id));

			$flash = "Спасибо, все фрагменты оригинала и версии перевода пересчитаны заново" . ($user->can("geek") ? (" за " . Yii::app()->db->stats[1] . " сек") : "") . ".";
			Yii::app()->user->setFlash("success", $flash);
			$this->redirect($book->url);
		}

		$this->render("recalc", array("book" => $book));
	}




	public function actionMembers($book_id) {
		$book = $this->loadBook($book_id);

		if($book->can("membership")) {
			// Заявки на членство в группе с facecontrol == FC_CONFIRM.
			if($book->facecontrol == Book::FC_CONFIRM and count($_POST["fate"]) > 0) {
				$this->members_requests();
				$this->redirect($this->book->getUrl("members"));
			}

			// Пригласить в перевод с facecontrol != FC_OPEN
			if($book->facecontrol != Book::FC_OPEN and isset($_POST["invite"])) {
				$this->members_invite($_POST["invite"]);
//				$this->redirect($this->book->getUrl("members"));
			}
		}

		// DataProvider: члены группы
		$f = new User();
		$members_dp = new CActiveDataProvider($f->members_of($this->book->id), array(
			"criteria" => array(
				"order" => "membership.n_trs desc, membership.status desc, membership.since desc",
			),
			"pagination" => array("pageSize" => 25),
		));

		if($this->book->can("membership")) {
			// DataProvider: заявки
			$queue_dp = new CActiveDataProvider(User::model(), array(
				"criteria" => array(
					"select" => "t.*, q.cdate as q_cdate, q.message as q_message",
					"join" => "RIGHT JOIN group_queue q ON q.user_id = t.id",
					"condition" => "q.book_id = '{$this->book->id}'",
				),
				"pagination" => false,
			));

			// DataProvider: отправленные приглашения
			$invited_dp = new CActiveDataProvider(User::model(), array(
				"criteria" => array(
					"select" => "t.*, i.cdate, i.from_uid, u2.login as from_login",
					"join" => "RIGHT JOIN invites i ON i.to_uid = t.id LEFT JOIN users u2 ON i.from_uid = u2.id",
					"condition" => "i.book_id = '{$this->book->id}'",
					"order" => "i.cdate desc",
				),
				"pagination" => false
			));
		}

		$this->side_view = "index_side";
		$this->side_params = array("book" => $this->book);

		$this->render("members", array("book" => $this->book, "members_dp" => $members_dp, "queue_dp" => $queue_dp, "invited_dp" => $invited_dp));
	}

	private function members_requests() {
		$Accept = array(); $Decline = array();
		$rAccept = array(); $rDecline = array(); // сюда пишем id из результата запроса

		foreach($_POST["fate"] as $id => $fate) {
			$id = (int) $id;
			if($fate == "accept") $Accept[] = $id;
			elseif($fate == "decline") $Decline[] = $id;
		}

		$c = new CDbCriteria(array());
		$c->addInCondition("id", array_merge($Accept, $Decline));
		$c->join = "RIGHT JOIN group_queue ON group_queue.user_id = t.id";
		$c->addCondition("group_queue.book_id = '{$this->book->id}'");
		$users = User::model()->findAll($c);

		foreach($users as $u) {
			if(in_array($u->id, $Accept)) {
				$rAccept[] = $u->id;
				$u->Notify(Notice::JOIN_ACCEPTED, $this->book);
				Yii::app()->db->createCommand("SELECT group_join(:user_id, :book_id)")->execute(array(":user_id" => $u->id, ":book_id" => $this->book->id));
			} else if(in_array($u->id, $Decline)) {
				$rDecline[] = $u->id;
				$u->Notify(Notice::JOIN_DENIED, $this->book);
			}
		}

		if(count($rDecline) + count($rAccept) > 0) {
			$sql = "DELETE FROM group_queue WHERE book_id = '{$this->book->id}' AND user_id IN('" . join("', '", array_merge($rDecline, $rAccept)) . "')";
			Yii::app()->db->createCommand($sql)->execute();
		}

		return true;
	}

	private function members_invite($user_list) {
		$user = Yii::app()->user;

		if($this->book->n_invites <= 0) {
			$user->setFlash("error", "Сегодня вы больше не можете приглашать людей в этот перевод.");
			return false;
		}

		$U = explode(",", $user_list);
		foreach($U as $k => $v) {
			$v = trim(mb_strtolower($v));
			if($v == "") continue;
			$U[$k] = trim($v);
		}

		// Список юзеров, у которых ещё нет инвайта
		$c = new CDbCriteria(array());
		$c->addInCondition("LOWER(login)", $U);
		$c->join = "LEFT JOIN invites ON invites.book_id = '{$this->book->id}' AND invites.to_uid = t.id";
		$c->addCondition("invites.to_uid IS NULL");
		$users = User::model()->membership($this->book->id)->findAll($c);

		$sql = "INSERT INTO invites (from_uid, to_uid, book_id) VALUES ";
		$invited = "";
		$cnt = 0;
		foreach($users as $u) {
			if( $u->membership->status == GroupMember::MEMBER ||
				$u->membership->status == GroupMember::MODERATOR ||
				$u->membership->status == GroupMember::BANNED) continue;

			if($this->book->n_invites - $cnt <= 0) break;

			if($cnt) {$sql .= ", "; $invited .= ", "; }
			$sql .= "('{$user->id}', '{$u->id}', '{$this->book->id}')";
			$invited .= $u->login;

			// "{$this->book->id}\n{$this->book->fullTitle}\n{$user->id}\n{$user->login}"
			$u->Notify(Notice::INVITE, $this->book);

			$cnt++;
		}

		if($cnt) {
			$n = Yii::app()->db->createCommand($sql)->execute();
			$this->book->n_invites -= $n;
			$this->book->save(array("n_invites"));
			$user->setFlash("success", Yii::t("app", "Отправлено {n} приглашение|Отправлено {n} приглашения|Отправлено {n} приглашений", $n) . ": {$invited}");
		} else {
			$user->setFlash("error", "Ни одного приглашения не отправлено. Возможно, вы неправильно написали ники пользователей, или им уже было отправлено приглашение, или они уже участвуют в переводе.");		}

		return true;
	}

	public function actionMembers_manage($book_id) {
		$this->loadBook($book_id);
		if(!$this->book->can("membership")) throw new CHttpException(403, "Вы не можете управлять группой перевода, это прерогатива " . ($this->book->ac_membership == "m" ? "модераторов" : "создателя перевода"));
		$back = $this->book->getUrl("members") . "?User_page=" . intval($_POST["User_page"]);

		if(count($_POST["id"]) == 0) $this->redirect($back);

		/** var integer $status - какой статус ставим / удаляем */
		$status = (int) $_POST["status"];

		// Нельзя удалять людей из открытого перевода и назначать модераторов, если ты не владелец
		if(
			($status == GroupMember::CONTRIBUTOR and $this->book->facecontrol == Book::FC_OPEN) or
			($status == GroupMember::MODERATOR   and !$this->book->can("owner"))
		) {
			$this->redirect($back);
		}

		// Загружаем всех, кто был в таблице
		/** var array $ids - ID всех пользователей, которые были в таблице на странице */
		/** var GroupMember[] $members - члены */
		$ids = array_filter(array_keys($_POST["id"]), function($n) { return is_numeric($n); });
		if(count($ids) == 0) $this->redirect($back);
		$members = GroupMember::model()->with("user")->book($this->book->id)->findAllByAttributes(array("user_id" => $ids));

		$update = array();
		$delete = array();
		foreach($members as $member) {
			// С создателем перевода никаких действий делать нельзя, а модераторов может мучить только создатель
			if($member->user_id == $this->book->owner_id) continue;
			if($member->status == GroupMember::MODERATOR and !$this->book->can("owner")) continue;

			$set = (int) $_POST["id"][$member->user_id];

			if($status == GroupMember::CONTRIBUTOR) {
				// Выгнать из группы
				// Пустые чекбоксы пропускаем
				if($set == 0) continue;

				// Если это уже удалённый пользователь, то не пинаем труп.
				if($member->status == GroupMember::CONTRIBUTOR) continue;

				// Если у члена группы были переводы, то ставим ему status = 0, если не было - удаляем нахуй
				if($member->n_trs == 0) $delete[] = $member->user_id;
				else $update[GroupMember::CONTRIBUTOR][] = $member->user_id;

				$member->user->Notify(Notice::EXPELLED, $this->book);
			} elseif($status == GroupMember::BANNED or $status == GroupMember::MODERATOR) {
				// Забанить или сделать модератором
				// Нельзя сделать модератором вышедшего члена
// 				   -- А с какого это хуя нельзя-то? При facecontrol == FC_OPEN - можно.
//				if($status == GroupMember::MODERATOR and $member->status == GroupMember::CONTRIBUTOR) continue;

				// Весёлая карусель! Баним незабаненных или коронуем пастухов.
				if($set and $member->status != $status) {
					$update[$status][] = $member->user_id;

					if($status == GroupMember::MODERATOR) $member->user->Notify(Notice::CROWNED, $this->book);
					elseif($status == GroupMember::BANNED) $member->user->Notify(Notice::BANNED, $this->book);
				}

				// Разбаниваем забаненных, а королей отправляем пасти свиней.
				if(!$set and $member->status == $status) {
					$update[$this->book->facecontrol == Book::FC_OPEN ? GroupMember::CONTRIBUTOR : GroupMember::MEMBER][] = $member->user_id;

					if($status == GroupMember::MODERATOR) $member->user->Notify(Notice::DEPOSED, $this->book);
					elseif($status == GroupMember::BANNED) $member->user->Notify(Notice::UNBANNED, $this->book);
				}

			}
		}

		if(count($delete) > 0) {
			Yii::app()->db->createCommand("DELETE FROM groups WHERE book_id = '{$this->book->id}' AND user_id IN(" . join(",", $delete) . ")")->execute();
		}
		if(count($update) > 0) {
			foreach($update as $k => $V) {
				Yii::app()->db->createCommand("UPDATE groups SET status = '{$k}' WHERE book_id = '{$this->book->id}' AND user_id IN(" . join(",", $V) . ")")->execute();
			}
		}

		$this->redirect($back);
	}

	public function actionMembers_join($book_id) {
		$this->book = Book::model()->with("owner")->membership(Yii::app()->user->id)->findByPk(intval($book_id));
		if(!$this->book) throw new CHttpException(404, "Такого перевода не существует. Возможно, он удалён.");

		if($this->book->facecontrol == Book::FC_INVITE) {
			$result = array(
				"msg" => "Участие в этой группе - только по приглашению от " . ($this->book->ac_membership == "m" ? "модераторов" : "создателя перевода") . "."
			);
		} elseif($this->book->facecontrol == Book::FC_OPEN) {
			$result = array(
				"msg" => "В этом переводе нет группы переводчиков."
			);
		} elseif($this->book->facecontrol == Book::FC_CONFIRM) {
			$result = $this->members_enqueue();
		} else {
			$result = array(
				"msg" => "Системная ошибка: bad book.facecontrol ({$this->book->id}:{$this->book->facecontrol})"
			);
		}

		if($_POST["ajax"] == 1) {
			echo json_encode($result);
		} else {
			if($result["status"] == "success") {
				Yii::app()->user->setFlash("success", $result["msg"]);
			} else {
				Yii::app()->user->setFlash("error", $result["msg"]);
			}
			$this->redirect($this->book->getUrl("members"));
		}
	}

	private function members_enqueue() {
		$result = array("status" => "fail", "msg" => "");

		if($this->book->membership->status == GroupMember::MEMBER) {
			$result["msg"] = "Вы уже состоите в этой группе перевода.";
			return $result;
		}

		if($this->book->membership->status == GroupMember::BANNED) {
			$result["msg"] = "Вы забанены в этой группе перевода.";
			return $result;
		}

		$p = array("book_id" => $this->book->id, "user_id" => Yii::app()->user->id);
		$r = Yii::app()->db->createCommand("SELECT 1 FROM group_queue WHERE book_id = :book_id AND user_id = :user_id")->queryScalar($p);
		if($r) {
			$result["msg"] = "Вы уже подавали заявку на участие в этой группе и она ещё не рассмотрена " . ($this->book->ac_membership == "m" ? "модераторами" : "создателем перевода") . ".";
			return $result;
		}

		$p[":message"] = mb_substr(trim(htmlspecialchars($_POST["message"])), 0, 200);
		Yii::app()->db->createCommand("INSERT INTO group_queue (book_id, user_id, message) VALUES (:book_id, :user_id, :message)")->query($p);

		// Добавляем в закладки
		if($_POST["bm"]) {
			Bookmark::set($this->book->id, null, "заявка подана " . date("d.m.Y"));
		}

		// Теперь отправляем оповещение всем модераторам
		$moderators = User::model()->moderators($this->book->id)->findAll();
		foreach($moderators as $moder) {
			$moder->Notify(Notice::JOIN_REQUEST, $this->book);
		}

		$result["status"] = "success";
		$result["msg"] = "Ваша заявка на участие в этой группе отправлена. О решении " . ($this->book->ac_membership == "m" ? "модераторов" : "создателя перевода") .
			" касательно вашего участия в группе вы будете извещены особо. Заглядывайте иногда на страницу <a href='/my/notices'>&laquo;Оповещения&raquo;</a>.";

		return $result;
	}

	public function actionMembers_leave($book_id) {
        if(!Yii::app()->request->isPostRequest) {
            throw new CHttpException(400, "Вы не должны видеть эту страницу. Что бы к этому не привело, пожалуйста, не делайте этого больше.");
        }

		$this->book = Book::model()->with("owner")->membership(Yii::app()->user->id)->findByPk(intval($book_id));
		if(!$this->book) {
			throw new CHttpException(404, "Такого перевода не существует. Возможно, он удалён.");
		}

		$p = array("book_id" => $this->book->id, "user_id" => Yii::app()->user->id);
		if($this->book->membership->n_trs != 0) {
			Yii::app()->db->createCommand("UPDATE groups SET status = 0 WHERE book_id = :book_id AND user_id = :user_id")->execute($p);
		} else {
			Yii::app()->db->createCommand("DELETE FROM groups WHERE book_id = :book_id AND user_id = :user_id")->execute($p);
		}

        Yii::app()->user->setFlash("success", "Вы покинули перевод {$this->book->ahref}.");
        if($_POST["ajax"] == 1) {
            echo "ok";
        } else {
            $this->redirect($this->book->getUrl("members"));
        }
	}

	private function invite_delete() {
		Yii::app()->db->createCommand("DELETE FROM invites WHERE book_id = :book_id AND to_uid = :my_uid")
			->execute(array(":book_id" => $this->book->id, ":my_uid" => Yii::app()->user->id));
	}

	public function actionInvite_accept($book_id) {
		$this->book = Book::model()->with("owner")->membership(Yii::app()->user->id)->findByPk(intval($book_id));
		if(!$this->book) throw new CHttpException(404, "Такого перевода не существует. Возможно, он удалён.");

		if($this->book->facecontrol == Book::FC_OPEN) {
			Yii::app()->user->setFlash("info", "В этом переводе больше не требуется вступать в группу.");
			$this->redirect($this->book->url);
		}

		// если я уже в группе, то я идём нахуй
		if($this->book->membership->status == GroupMember::MEMBER || $this->book->membership->status == GroupMember::MODERATOR) {
			$this->invite_delete();
			Yii::app()->user->setFlash("success", "Вы уже состоите в этой группе перевода.");
			$this->redirect($this->book->url);
		}

		// проверяем, есть ли приглашение вообще
		if(!$this->book->user_invited(Yii::app()->user->id)) {
			throw new CHttpException(403, "Сожалеем, но ваше приглашение в группу устарело или отозвано пригласившим вас пользователем.");
		}

		// вступаем в группу. status до этого - NULL, BANNED или либо CONTRIBUTOR
		// NULL - insert
		// CONTRIBUTOR: update
		Yii::app()->db->createCommand("SELECT group_join(:user_id, :book_id)")->execute(array(":user_id" => Yii::app()->user->id, ":book_id" => $this->book->id));

		// Добавляем в закладки
		Bookmark::set($this->book->id);

		// стираем инвайт и редиректимся на оглавление
		$this->invite_delete();
		Yii::app()->user->setFlash("success", "Добро пожаловать в группу перевода!");
		$this->redirect($this->book->url);
	}

	public function actionInvite_decline($book_id) {
		$this->book = Book::model()->with("owner")->membership(Yii::app()->user->id)->findByPk(intval($book_id));
		if(!$this->book) throw new CHttpException(404, "Такого перевода не существует. Возможно, он удалён.");

		$this->invite_delete();

		$this->render("invite_decline");
	}






	public function actionDict($book_id) {
		$book = $this->loadBook($book_id);
		$ajax = $_GET["ajax"] || $_POST["ajax"];

		$dict = Dict::model()->book($book->id)->findAll(array("order" => "t.term"));

		$p = array("book" => $book, "dict" => $dict, "ajax" => $ajax);
		$view = Yii::app()->user->ini["t.iface"] == 1 ? "dict-1" : "dict";
		if($ajax) $this->renderPartial($view, $p);
		else $this->render($view, $p);
	}

	public function actionDict_edit($book_id) {
		if(!Yii::app()->request->isPostRequest) {
			throw new CHttpException(400, "Вы не должны видеть эту страницу. Что бы к этому не привело, пожалуйста, не делайте этого больше.");
		}

		$book = $this->loadBook($book_id);
		if(!$book->can("dict_edit")) throw new CHttpException(403, "Только модераторы могут редактировать словарь перевода.");

		$id = (int) $_POST["id"];
		$ajax = $_POST["ajax"] || $_GET["ajax"];

		if($id) {
			$dict = Dict::model()->findByPk($id);
			if(!$dict) throw new CHttpException(404, "Слова, которое вы пытаетесь отредактировать, нет в словаре этого перевода.");
		} else {
			$dict = new Dict();
			$dict->book_id = $book->id;
			$dict->user_id = Yii::app()->user->id;
		}

		$dict->setAttributes($_POST);

		if($dict->save()) {
			if($ajax) echo json_encode(array("id" => $dict->id, "term" => $dict->term, "descr" => $dict->descr));
			else $this->redirect($book->getUrl("dict"));
		} else {
			if($ajax) echo json_encode(array("error" => $dict->getErrorsString()));
			else {
				Yii::app()->user->setFlash("error", $dict->getErrorsString());
				$this->redirect($book->getUrl("dict"));
			}
		}
	}

	public function actionDict_rm($book_id) {
		if(!Yii::app()->request->isPostRequest) {
			throw new CHttpException(400, "Вы не должны видеть эту страницу. Что бы к этому не привело, пожалуйста, не делайте этого больше.");
		}

		$book = $this->loadBook($book_id);
		if(!$book->can("dict_edit")) throw new CHttpException(403, "Только модераторы могут редактировать словарь перевода.");



		$id = (int) $_POST["id"];
		$dict = Dict::model()->findByPk($id);
		if(!$dict) throw new CHttpException(404, "Слова, которое вы пытаетесь отредактировать, нет в словаре этого перевода.");

		if($dict->book_id != $book->id) throw new CHttpException(403, "Вы пытаетесь удалить слово из другого перевода. Нехорошо-с.");

		$dict->delete();

		echo json_encode(array("id" => $dict->id));
	}

	public function actionDict_copy($book_id) {
		$book = $this->loadBook($book_id);
		if(!$book->can("dict_edit")) throw new CHttpException(403, "Только модераторы могут редактировать словарь перевода.");

		if($_GET["from"]) {
			$source = Book::model()->findByPk(intval($_GET["from"]));
			if(!$source->can("dict_edit")) throw new CHttpException(403, "Вы можете копировать словарь только из того перевода, где являетесь модератором.");

			$srcDict = Dict::model()->book($source->id)->findAll();

			$dstDict = Dict::model()->book($book->id)->findAll();

			$sql = "";
			$params = array(":book_id" => $book->id, ":user_id" => Yii::app()->user->id);
			$cntAdded = 0;
			foreach($srcDict as $i => $term) {
				$found = false;
				foreach($dstDict as $t) {
					if($t->term == $term->term) $found = true;
				}
				if($found) continue;

				if($sql != "") $sql .= ", ";
				$sql .= "(:book_id, :user_id, :term{$i}, :descr{$i})";
				$params[":term{$i}"] = $term->term;
				$params[":descr{$i}"] = $term->descr;
				$cntAdded++;
			}

			if($sql != "") {
				$sql = "INSERT INTO dict (book_id, user_id, term, descr) VALUES " . $sql;

				Yii::app()->db->createCommand($sql)->execute($params);
				Yii::app()->user->setFlash("success", "В словарь перенесено " . Yii::t("app", "{n} определение|{n} определения|{n} определений", $cntAdded) . " из перевода {$source->ahref}.");
			} else {
				Yii::app()->user->setFlash("warning", "Ни одного нового слова в словаре перевода {$source->ahref} не найдено.");
			}

			$this->redirect($book->url);
		} else {
			$sources = Book::model()->moderated_by(Yii::app()->user->id)->findAll("t.id != :id", array(":id" => $book->id));

			foreach($sources as $k => $v) {
				if($v->dict_cnt == 0) unset($sources[$k]);
			}

			$this->render("dict_copy", array("book" => $book, "sources" => $sources));
		}

	}



	public function actionBan_copyright($book_id) {
		$book = $this->loadBook($book_id);

		$reason = BookBanReason::model()->findByPk($book->id);
		if(!$reason) $reason = new BookBanReason();
		$reason->book_id = $book->id;
		$reason->book = $book;

		if(isset($_POST["BookBanReason"])) {
			$reason->setAttributes($_POST["BookBanReason"]);
			if($reason->save()) {
				$book->opts_set(Book::OPTS_BAN_COPYRIGHT, 1);
				$book->facecontrol = Book::FC_INVITE;
				foreach(Yii::app()->params["ac_areas"] as $ac => $title) {
					if($book->$ac == "a") $book->$ac = "g";
				}
				$book->save(false);
				$this->redirect($book->url);
			}
		}

		$this->render("ban_copyright", array("book" => $book, "reason" => $reason));
	}

}