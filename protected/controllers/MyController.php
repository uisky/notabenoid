<?php
class MyController extends Controller {
	public function filters() {
		return array('accessControl');
	}

	public function accessRules() {
		return array(
			array('allow', 'users' => array('@'),
				'actions' => array(
					"info",
					"notices", "notices_rmseen",
				),
			),
			array('deny', 'users' => array('*')),
		);
	}

	public function actionIndex() {
		echo "/my";
	}

	public function actionInfo() {
		if(!Yii::app()->user->isPaid) {
			echo "{}";
			return;
		}

		echo json_encode(array(
			"c" => Yii::app()->user->newComments,
			"n" => Yii::app()->user->newNotices,
			"m" => Yii::app()->user->newMail
		));
	}



	public function actionNotices() {
		$ajax = false;

		if(isset($_POST["rm"])) {
			$id = (int) $_POST["rm"];

			$n = Yii::app()->db->createCommand("DELETE FROM notices WHERE user_id = :user_id AND id = :id")->execute(array(":user_id" => Yii::app()->user->id, ":id" => $id));
			$ajax = true;
		}

		$crit = new CDbCriteria();
		if($_GET["new"] == 1) {
			$crit->addCondition("not seen");
		}
		$notices_dp = new CActiveDataProvider(Notice::model()->user(Yii::app()->user->id, false), array(
			"criteria" => $crit,
			"pagination" => array("pageSize" => 20),
		));

		$new_ids = array();
		foreach($notices_dp->data as $notice) {
			if(!$notice->seen) $new_ids[] = $notice->id;
		}

		$this->side_view = array("notices_side" => array("new_ids" => $new_ids));
		$p = array("notices_dp" => $notices_dp);
		if($ajax) {
			$p["ajax"] = true;
			$this->renderPartial("notices", $p);
		} else {
			$this->render("notices", $p);
		}

		// Отмечаем показанные, как прочитанные
		if(count($new_ids) > 0) {
			Yii::app()->db->createCommand("UPDATE notices SET seen = true WHERE user_id = :user_id AND id IN('" . join("','", $new_ids) . "')")->execute(array(":user_id" => Yii::app()->user->id));
		}
	}

	public function actionNotices_rmseen() {
		$exclude = array();
		if(is_array($_POST["x"])) {
			foreach($_POST["x"] as $id) {
				$exclude[] = (int) $id;
			}
		}
		$n = Yii::app()->db->createCommand("DELETE FROM notices WHERE user_id = :user_id AND seen" . (count($exclude) > 0 ? " AND id NOT IN('" . join("','", $exclude) . "')" : ""))
			->execute(array(":user_id" => Yii::app()->user->id));

		$this->redirect("/my/notices");
	}



	/** далее - OBSOLETE; если будешь удалять - сотри соответствующие вьюхи //my/bookmarks и //my/bookmarks_side */
	public function actionBookmarks() {
		$typ = $_GET["typ"];
		if(!isset(Yii::app()->params["bookmark_types"][$typ])) $typ = "b";

		$bookmarks = Bookmark::model()->user(Yii::app()->user->id)->typ($typ)->findAll();

		$this->side_view = array(
			"bookmarks_side" => array(),
		);

		$this->render("bookmarks", array("bookmarks" => $bookmarks, "typ" => $typ));
	}

	public function actionBookmarks_edit() {
		if(!isset($_POST["Bookmark"])) $this->redirect("/my/bookmarks");
		$id = (int) $_GET["id"];

		$bookmark = Bookmark::model()->user(Yii::app()->user->id)->findByPk($id);
		if(!$bookmark) $this->redirect("/my/bookmarks");

		$bookmark->setAttributes($_POST["Bookmark"]);

		if(!$bookmark->save()) {
			Yii::app()->user->setFlash("error", $bookmark->getErrorsString());
		}
		$this->redirect("/my/bookmarks");
	}

	public function actionBookmarks_remove() {
		if(!isset($_POST["id"])) $this->redirect("/my/bookmarks");

		Yii::app()->db->createCommand("DELETE FROM bookmarks WHERE user_id = :user_id AND id = :id")
			->execute(array(":user_id" => Yii::app()->user->id, ":id" => (int) $_POST["id"]));

		$this->redirect("/my/bookmarks");
	}

	public function actionBookmarks_ord() {
		if(!is_array($_POST["b"])) {
			echo "Неверный запрос";
			return;
		}

		$sql = "BEGIN;\n";
		foreach($_POST["b"] as $ord => $id) {
			$sql .= sprintf("UPDATE bookmarks SET ord = %d WHERE user_id = %d AND id = %d;\n", $ord, Yii::app()->user->id, $id);
		}
		$sql .= "COMMIT;\n";

		Yii::app()->db->createCommand($sql)->execute();

		echo "ok";
	}

	public function actionBookmark_set() {
		if(!is_array($_POST["B"])) throw new CHttpException(500, "Неверный запрос.");

		// Есть ли уже такая закладка?
		$bookmark = Bookmark::model()->findByAttributes(array(
			"user_id" => Yii::app()->user->id,
			"typ" => substr($_POST["B"]["typ"], 0, 1),
			"obj_id" => (int) $_POST["B"]["obj_id"]
		));
		if(!$bookmark) {
			$bookmark = new Bookmark();
			$bookmark->user_id = Yii::app()->user->id;
		}

		$bookmark->setAttributes($_POST["B"]);
		if($bookmark->typ == "o") {
			$orig = Orig::model()->with("chap.book")->findByPk($bookmark->obj_id);
			if(!$orig) throw new CHttpException(404, "Фрагмент оригинала удалён.");
			$bookmark->url = $orig->url;
		} elseif($bookmark->typ == "c") {
			$chap = Chapter::model()->with("book")->findByPk($bookmark->obj_id);
			if(!$chap) throw new CHttpException(404, "Глава удалена");
			$bookmark->url = $chap->url;
		} elseif($bookmark->typ == "b") {
			$book = Book::model()->findByPk($bookmark->obj_id);
			if(!$book) throw new CHttpException(404, "Глава удалена");
			$bookmark->url = $book->url;
		} else {
			throw new CHttpException(500, "Неверный запрос. Если вам интересны грязные подробности, то я не знаю, что такое тип закладки '{$bookmark->typ}'.");
		}
		if($bookmark->isNewRecord) {
			$bookmark->ord = Yii::app()->db->createCommand("SELECT MAX(ord) FROM bookmarks WHERE typ = :typ AND user_id = :user_id")
				->queryScalar(array(":typ" => $bookmark->typ, ":user_id" => Yii::app()->user->id)) + 1;
		}

		if(!$bookmark->save()) throw new CHttpException(500, $bookmark->getErrorsString());

		$json = array("id" => $bookmark->id, "title" => $bookmark->title);
		echo json_encode($json);
	}

	public function actionBookmark_rm() {
		if(!isset($_POST["id"])) throw new CHttpException(500, "Неверный запрос. Либо что-то сломалось у нас, либо вы желаете странного.");

		$ajax = isset($_GET["ajax"]) ? intval($_GET["ajax"]) : intval($_POST["ajax"]);

		$c = new CDbCriteria(array(
			"condition" => "user_id = :user_id",
			"params" => array(":user_id" => Yii::app()->user->id),
		));
		if(is_array($_POST["id"])) {
			$c->addInCondition("id", $_POST["id"]);
		} else {
			$c->addCondition("id = :id");
			$c->params[":id"] = (int) $_POST["id"];
		}

		Yii::app()->db->createCommand("DELETE FROM bookmarks WHERE {$c->condition}")
			->execute($c->params);

		if($ajax) echo json_encode(array("bm_id" => $_POST["id"]));
		else $this->redirect("/my/bookmarks");
	}
}
