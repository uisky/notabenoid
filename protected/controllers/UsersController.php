<?php
class UsersController extends Controller {
	public $siteArea = "users";

	public $submenu = array();

	public function filters() {
		return array(
			"usersOnly + edit, upic, delete",
		);
	}

	public function init() {
		parent::init();

		$this->submenu = array(
			"books" => "Переводы",
			"karma" => "Карма",
			"comments" => "Комментарии",
			"posts" => "Посты",
			"profile" => "Контакты",
		);
		$user = Yii::app()->user;
		if(p()["registerType"] == "INVITE" && !$user->isGuest && $user->id == $_GET["id"]) {
			$this->submenu["invites"] = "Приглашения";
			if($user->model->n_invites > 0) $this->submenu["invites"] .= " (" . $user->model->n_invites . ")";
		}
		if($user->can("admin")) {
			$this->submenu["admin"] = "<i class='icon-pencil'></i>";
		}
	}

	private function loadUser($id) {
		$user = User::model()->findByPk((int) $id);
		if(!$user) throw new CHttpException(404, "Такого пользователя не существует.");
		if($user->isDeleted) throw new CHttpException(404, "Пользователь удалён.");

		return $user;
	}

	public function actionGo($login) {
		$user = User::model()->find("LOWER(login) = :login", array(":login" => mb_strtolower($login)));
		if(!$user) {
			throw new CHttpException(404, "Пользователя с таким именем просто не существует. Впрочем, за существование остальной части Вселенной мы тоже не можем поручиться.");
		}

		$this->redirect($user->url);
	}

	public function actionIndex() {
		$users_dp = new CActiveDataProvider(User::model()->cache(60 * 15, null, 2), array(
			"criteria" => array(
			),
			"pagination" => array(
				"pageSize" => 50,
			),
			"sort" => array(
				"attributes" => array("login", "rate_u", "rate_t", "n_trs"),
				"defaultOrder" => array(
					"rate_t" => true,
				),
			)
		));

		$this->side_view = "list_side";

		$global_stats = unserialize(file_get_contents(YiiBase::getPathOfAlias("application.runtime") . "/global_stat.ser"));
		$this->side_params = $global_stats;

		$this->render("list", array("users_dp" => $users_dp));
	}

	public function actionProfile($id) {
		$user = $this->loadUser($id);

		$this->side_view = array(
			"profile_side" => array("user" => $user, "userinfo" => $user->userinfo),
		);

		$invited = User::model()->findAll([
			"condition" => "invited_by = :user_id",
			"params" => ["user_id" => $user->id],
		]);

		$this->render("profile", ["user" => $user, "invited" => $invited]);
	}

	public function actionKarma($id) {
		$user = $this->loadUser($id);

		if(!Yii::app()->user->isGuest and Yii::app()->user->id != $user->id) {
			$my_mark = KarmaMark::model()->from_user(Yii::app()->user->id)->to_user($user->id)->find();
			if(!$my_mark) {
				$my_mark = new KarmaMark();
				$my_mark->from_uid = Yii::app()->user->id;
				$my_mark->to_uid = $user->id;
			}

			if(isset($_POST["KarmaMark"])) {
				if(!Yii::app()->user->can("karma")) {
					Yii::app()->user->setFlash("error", "Ставить оценки в карму могут только пользователи, зарегистрировавшиеся не позднее, чем 180 дней тому назад.");
				}
				$my_mark->setAttributes($_POST["KarmaMark"]);
				if(!$my_mark->isNewRecord or $my_mark->mark != 0) {
					if(!$my_mark->save()) {
						Yii::app()->user->setFlash("error", "Не удалось добавить вашу оценку.");
					} else {
						$this->redirect($user->getUrl("karma"));
					}
				} else {
					$this->redirect($user->getUrl("karma"));
				}
			}
		} else {
			$my_mark = null;
		}

		$dir = $_GET["dir"] == "out" ? "out" : "in";

		$f = new KarmaMark();
		if($dir == "out") {
			$f->from_user($user->id)->with("to");
		} else {
			$f->to_user($user->id)->with("from");
		}
		$marks = new CActiveDataProvider($f, array(
			"criteria" => array("order" => "t.dat desc"),
			"pagination" => array("pageSize" => 500),
		));
		$marks->totalItemCount = Yii::app()->db->createCommand("SELECT COUNT(*) FROM karma_rates WHERE to_uid = :user_id")->queryScalar(array(":user_id" => $user->id));

		$this->side_view = array(
			"profile_side" => array("user" => $user, "userinfo" => $user->userinfo),
			"karma_side" => array("user" => $user, "dir" => $dir),
		);

		$this->render("karma", array("user" => $user, "dir" => $dir, "marks" => $marks, "my_mark" => $my_mark));
	}

	public function actionBooks($id) {
		$user = $this->loadUser($id);

		$orderOptions = array(
			1 => array("t.last_tr desc NULLS LAST", "По дате последнего перевода от {$user->login}"),
			2 => array("t.n_trs desc NULLS LAST", "По количеству версий от {$user->login}"),
			3 => array("CASE WHEN book.n_verses <> 0 THEN book.d_vars::float / book.n_verses::float ELSE null END DESC NULLS LAST", "По готовности перевода"),
			4 => array("t.since DESC", "По дате вступления в перевод"),
		);
		$statusOptions = array(
			0 => array("", "все", "не участвует ни в одном переводе"),
			1 => array("t.status = 2", "там, где {$user->login} &ndash; модератор", "не модерирует ни один перевод"),
			2 => array("book.owner_id = {$user->id}", "там, где {$user->login} &ndash; создатель", "не создал" . $user->sexy() . " ни одного проекта перевода"),
		);

		$order = (int) $_GET["order"];
		if(!isset($orderOptions[$order])) $order = 1;
		$status = (int) $_GET["status"];
		if(!isset($statusOptions[$status])) $status = 0;

		$f = new GroupMember();
		$f->user($user->id)->with("book");
		$c = new CDbCriteria();
		if($order) $c->order = $orderOptions[$order][0];
		if($status) $c->addCondition($statusOptions[$status][0]);
		$groups_dp = new CActiveDataProvider($f, array(
			"criteria" => $c,
			"pagination" => array("pageSize" => 30),
		));
		// $groups_dp->totalItemCount = Yii::app()->db->createCommand("SELECT COUNT(*) FROM groups WHERE user_id = :user_id")->queryScalar(array(":user_id" => $user->id));

		$this->side_view = array(
			"profile_side" => array("user" => $user, "userinfo" => $user->userinfo),
			"books_side" => array("orderOptions" => $orderOptions, "order" => $order, "statusOptions" => $statusOptions, "status" => $status),
		);

		$this->render("books", array(
			"user" => $user,
			"groups_dp" => $groups_dp,
			"order" => $order,
			"statusOptions" => $statusOptions, "status" => $status,
		));
	}

	public function actionTranslations($id, $book_id) {
		$user = $this->loadUser($id);

		$book = Book::model()->with("membership")->findByPk((int) $book_id);
		if(!$book) throw new CHttpException(404, "Перевода не существует. Вероятно, он удалён.");
		if(!$book->can("read") ) throw new CHttpException(403, "Вы не можете просматривать версии перевода в этом проекте. " . $book->getWhoCanDoIt("read", false));
		if(!$book->can("trread")) throw new CHttpException(403, "Вы не можете просматривать версии перевода в этом проекте. " . $book->getWhoCanDoIt("trread", false));

		$translations = new CActiveDataProvider(
            Translation::model()->userbook($user->id, $book->id)->with("orig.chap"),
            array(
                "criteria" => array(
	                "order" => "t.cdate desc"
                ),
                "pagination" => array("pageSize" => 20)
		    )
        );

		$this->side_view = array(
			"profile_side" => array("user" => $user, "userinfo" => $user->userinfo),
		);

		$this->render("translations", array("user" => $user, "book" => $book, "translations" => $translations));
	}

	public function actionComments($id) {
		$user = $this->loadUser($id);

		$mode = $_GET["mode"];
		if($mode != "tblog" and $mode != "tr") $mode = "blog";

		$c = new CDbCriteria(array("order" => "t.cdate desc"));
		if($mode == "blog")      $c->addCondition("t.post_id IS NOT NULL AND post.book_id IS NULL");     // с post_id почему-то слегка быстрее :-/
		elseif($mode == "tblog") $c->addCondition("t.orig_id IS NULL     AND post.book_id IS NOT NULL");
		elseif($mode == "tr")    $c->addCondition("t.orig_id IS NOT NULL");

		$f = new Comment();
		$f->user($user->id)->with("author");
		if($mode == "blog") {
			$f->with("post");
		} elseif($mode == "tblog") {
			$c->addCondition("book.ac_read = 'a' AND book.ac_blog_r = 'a'");
			$f->with("post.book");
		} elseif($mode == "tr") {
			$c->addCondition("chap.ac_read IS NULL AND book.ac_read = 'a'");
			$f->with("orig.chap.book");
		}

		$comments = new CActiveDataProvider($f, array(
			"criteria" => $c,
			"pagination" => array("pageSize" => 20)
		));

		$this->side_view = array(
			"profile_side" => array("user" => $user, "userinfo" => $user->userinfo),
		);

		$this->render("comments", array("user" => $user, "comments" => $comments, "mode" => $mode));
	}

	public function actionPosts($id) {
		$user = $this->loadUser($id);

		$posts = new CActiveDataProvider(BlogPost::model()->user($user->id)->with("book.membership", "seen"), array(
			"criteria" => array(
//				"condition" => "t.book_id IS NULL OR (book.ac_read = 'a' AND book.ac_blog_r = 'a')",
				"order" => "t.cdate desc"
			),
			"pagination" => array("pageSize" => 10)
		));

		$this->render("posts", array("user" => $user, "posts" => $posts));
	}

	public function actionInvites($id) {
		if(p()["registerType"] != "INVITE") {
			$this->redirect("/users/{$id}");
		}

		$user = $this->loadUser($id);
		if($user->id !== Yii::app()->user->id) throw new CHttpException(404);

		if(Yii::app()->request->isPostRequest) {
			if(isset($_POST["revoke"])) {
				$invite = RegInvite::model()->findByAttributes(["id" => (int) $_POST["revoke"], "from_id" => $user->id]);
				if($invite) {
					$invite->delete();
					$user->n_invites++;
					$user->save(false, ["n_invites"]);
					Yii::app()->user->setFlash("success", "Приглашение отозвано.");
				} else {
					Yii::app()->user->setFlash("error", "Приглашение не найдено.");
				}
				$this->redirect($user->getUrl("invites"));
			} elseif(isset($_POST["resend"])) {
				$invite = RegInvite::model()->findByAttributes(["id" => (int) $_POST["resend"], "from_id" => $user->id]);
				$invite->to_email = $invite->buddy->email;
				$invite->save(false, ["to_email"]);
				if($invite) {
					$invite->sendMail();
					Yii::app()->user->setFlash("success", "Приглашение отправлено.");
				} else {
					Yii::app()->user->setFlash("error", "Приглашение не найдено.");
				}
				$this->redirect($user->getUrl("invites"));
			} elseif(isset($_POST["invite"]) && $user->n_invites > 0) {
				$invite = RegInvite::gen($user);
				$invite->setAttributes($_POST["invite"]);
				if($invite->validate()) {
					// @todo: обернуть в транзацкию
					$invite->save();

					$user->n_invites--;
					$user->save(false, ["n_invites"]);

					$invite->sendMail();

					Yii::app()->user->setFlash("success", "Приглашение отправлено!");
					$this->redirect($user->getUrl("invites"));
				}
			}
		} else {
			$invite = new RegInvite();
			if($_GET["who"]) {
				$invite->type = "user";
				$invite->clue = htmlspecialchars($_GET["who"]);
			}
		}

		$sent = RegInvite::model()->findAll([
			"condition" => "t.from_id = :me",
			"params" => ["me" => $user->id],
			"order" => "t.cdate desc",
			"with" => ["buddy"],
		]);

		$this->side_view = [
			"profile_side" => ["user" => $user, "userinfo" => $user->userinfo],
		];
		$this->render("invites", ["user" => $user, "invite" => $invite, "sent" => $sent]);
	}

	public function actionInviteCode($id, $iid) {
		$user = $this->loadUser($id);
		if($user->id !== Yii::app()->user->id) throw new CHttpException(404);

		$invite = RegInvite::model()->findByPk((int) $iid, "from_id = :me", ["me" => $user->id]);
		if(!$invite) throw new CHttpException(404, "Инвайт не найден");

		echo $invite->getUrlAccept();
	}

	public function actionEdit() {
		$form = UserEditor::model()->findByPk(Yii::app()->user->id);

		if(isset($_POST["UserEditor"])) {
			$form->attributes = $_POST["UserEditor"];
			if($form->save()) {
				$this->redirect(Yii::app()->user->url);
			}
		}

		$this->render("edit", array("model" => $form));
	}

	public function actionDelete($id) {
		$user = Yii::app()->user->model;
		if($id != $user->id) $this->redirect($user->getUrl("delete"));
		if($_POST["really"]) {
			$i = new UserIdentity($user->login, $_POST["pass"]);
			if($i->authenticate()) {
				$user->sex = "-";
				$user->save(false, ["sex"]);
				Yii::app()->user->logout();
				$this->render("delete_done");
				return;
			} else {
				Yii::app()->user->setFlash("error", "Пароль неверный. <a href='/register/remind'>Напомнить?</a>");
			}
		}

		$this->render("delete");
	}

	public function actionUpic() {
		/** @var User $user */
		$user = User::model()->findByPk(Yii::app()->user->id);

		if($_GET["do"] == "cancel" && is_array($_SESSION["upicEditor"])) {
			$tmp_path = $_SERVER["DOCUMENT_ROOT"] . "/i/tmp/upiccut/" . $_SESSION["upicEditor"]["img"]["name"];
			if(is_file($tmp_path)) unlink($tmp_path);

			unset($_SESSION["upicEditor"]);

			$this->redirect($user->url);
		}

		if(isset($_POST["x"]) && isset($_POST["y"]) && isset($_POST["w"]) && isset($_POST["h"])) {
			$tmp_path = $_SERVER["DOCUMENT_ROOT"] . "/i/tmp/upiccut/" . $_SESSION["upicEditor"]["img"]["name"];

			$user->upicUnlink();
			$user->upicCheckDir();
			$user->upic[0] = rand(1, 32000);

			list($user->upic[1], $user->upic[2], $otyp) = getimagesize($tmp_path);

			// resize
			$src = null;
			if($otyp == 1) $src = @ImageCreateFromGIF($tmp_path);
			else if($otyp == 2) $src = @ImageCreateFromJPEG($tmp_path);
			else if($otyp == 3) $src = @ImageCreateFromPNG($tmp_path);
			$dst = ImageCreateTrueColor(50, 50);

			if(!$src || !$dst) {
				Yii::app()->user->setFlash("error", "При обработке изображения произошла ошибка. Попробуйте другую картинку.");
				$this->redirect($user->getUrl("upic"));
			}

			$srcx = (int) $_POST["x"];
			$srcy = (int) $_POST["y"];
			$srcw = (int) $_POST["w"];
			$srch = (int) $_POST["h"];
			ImageCopyResampled($dst, $src, 0,0, $srcx,$srcy, 50, 50, $srcw,$srch);

			ImageJPEG($dst, $user->upicPath);

			// store big image
			rename($tmp_path, $user->upicPathBig);

			$user->save(false);

			unset($_SESSION["upicEditor"]);

			$this->redirect($user->url);
		}

		if($_FILES["img"]["size"] == 0 && !isset($_SESSION["upicEditor"])) $this->redirect($user->url);

		if($_FILES["img"]["size"] > 0) {
			list($w, $h) = getimagesize($_FILES["img"]["tmp_name"]);

			if($w <= 0 || $h <= 0) {
				Yii::app()->user->setFlash("error", "Не удалось загрузить файл. Возможно, он сохранён в неправильном формате. Попробуйте открыть его любым графическим редактором и пересохранить в JPG.");
				$this->redirect($user->url);
			}

			$tmp_name = time() . "-" . $user->id . "-" . rand(1000, 9999);
			$tmp_path = $_SERVER["DOCUMENT_ROOT"] . "/i/tmp/upiccut/" . $tmp_name;

			if($w >= 600 || $h > 800) {
				$R = new ImgResizer();
				list($w, $h) = $R->resize($_FILES["img"]["tmp_name"], $tmp_path, 600, 800);
			} else {
				move_uploaded_file($_FILES["img"]["tmp_name"], $tmp_path);
			}
			$_SESSION["upicEditor"]["img"] = ["name" => $tmp_name, "w" => $w, "h" => $h];
		}

		$p = ["user" => $user];
		$this->side_view = ["upic_side" => $p];
		$this->render("upic", $p);
	}

	public function actionAdmin($id) {
		if(!Yii::app()->user->can("admin")) throw new CHttpException(404);

		$user = $this->loadUser($id);
		$user->scenario = "edit-admin";

		if(Yii::app()->request->isPostRequest) {
			$user->setAttributes($_POST["User"]);
			if(is_array($_POST["can"])) {
				foreach($_POST["can"] as $bit) {
					$user->can_set($bit, 1);
				}
			}
			if($user->save()) {
				Yii::app()->user->setFlash("success", "Сохранено.");
			}
		}

		$remindToken = RemindToken::model()->findByAttributes(["user_id" => $user->id]);

		$sentInvites = RegInvite::model()->with("sender")->findAllByAttributes(["to_id" => $user->id]);

		$this->side_view = [
			"profile_side" => ["user" => $user, "userinfo" => $user->userinfo],
		];
		$this->render("admin", ["user" => $user, "remindToken" => $remindToken, "sentInvites" => $sentInvites]);
	}

	public function actionAdminRemindToken($id) {
		if(!Yii::app()->user->can("admin")) throw new CHttpException(404);

		$user = $this->loadUser($id);

		$token = RemindToken::gen($user);

		$this->redirect("/users/{$user->id}/admin");
	}
}