<?php
class BookmarksController extends Controller {
	public function filters() {
		return array('accessControl');
	}

	public function accessRules() {
		return array(
			array('allow', 'users' => array('@'),
				'actions' => array(
					"data", "rm", "edit", "set", "remove", "reorder"
				),
			),
			array('deny', 'users' => array('*')),
		);
	}

	/**
	 * @return Bookmark
	 */
	private function loadBookmark() {
		$book_id = (int) $_POST["book_id"];
		$orig_id = (int) $_POST["orig_id"];

		$sql = "user_id = :user_id AND book_id = :book_id";
		$params = array(":user_id" => Yii::app()->user->id, ":book_id" => $book_id);
		if($orig_id) {
			$sql .= " AND orig_id = :orig_id";
			$params[":orig_id"] = $orig_id;
		} else {
			$sql .= " AND orig_id IS NULL";
		}

		$bm = Bookmark::model()->find($sql, $params);

		return $bm;
	}

	public function actionData($book_id = 0) {
		$book_id = (int) $book_id;
		if($book_id == 0) {
			$bookmarks = Bookmark::model()->bookList(Yii::app()->user->id)->findAll();
		} else {
			$bookmarks = Bookmark::model()->origList(Yii::app()->user->id, $book_id)->findAll();
		}

		$json = array();
		foreach($bookmarks as $bm) {
			$json[] = $bm->JSON;
		}

		echo json_encode($json);
	}

	public function actionEdit() {
		$bm = $this->loadBookmark();
		if(!$bm) throw new CHttpException(404, "Закладки не существует. Возможно, она уже удалена.");

		$bm->setAttributes($_POST);
		if(!$bm->save()) throw new CHttpException(500, $bm->getErrorsString());

		echo json_encode($bm->JSON);
	}

	public function actionSet() {
		$book_id = (int) $_POST["book_id"];
		$orig_id = (int) $_POST["orig_id"];

		$pk = array("user_id" => Yii::app()->user->id, "book_id" => $book_id);
		if($orig_id) $pk["orig_id"] = $orig_id;
		$bm = Bookmark::model()->findByAttributes($pk);
		if(!$bm) {
			$bm = new Bookmark();
			$bm->user_id = Yii::app()->user->id;
			$bm->book_id = $book_id;
			if($orig_id) $bm->orig_id = $orig_id;
		}

		if(isset($_POST["note"])) {
			$post = $_POST;
			unset($post["book_id"]); unset($post["orig_id"]);
			$bm->setAttributes($post);
			$bm->watch = (int) $_POST["watch"];
			$new_ord = Yii::app()->db->createCommand("SELECT MAX(ord) FROM bookmarks WHERE user_id = :user_id AND orig_id IS NULL")->queryScalar(array(":user_id" => Yii::app()->user->id)) + 1;
			if($orig_id) {
				// А есть ли закладка на перевод?
				if(!Yii::app()->db->createCommand("SELECT 1 FROM bookmarks WHERE user_id = :user_id AND book_id = :book_id AND orig_id IS NULL")->queryScalar(array(":user_id" => Yii::app()->user->id, ":book_id" => $book_id))) {
					Yii::app()->db->createCommand("INSERT INTO bookmarks (user_id, book_id, ord) VALUES (:user_id, :book_id, :ord)")->execute(array(":user_id" => Yii::app()->user->id, ":book_id" => $book_id, ":ord" => $new_ord));
				}
			} else {
				$bm->ord = $new_ord;
			}
			if(!$bm->save()) throw new CHttpException(500, $bm->getErrorsString());
			echo json_encode(array("book_id" => (int) $bm->book_id, "orig_id" => (int) $bm->orig_id, "note" => $bm->note, "status" => "set"));
		} else {
			$this->renderPartial("set", array("bm" => $bm));
		}
	}

	public function actionRemove() {
		$bm = $this->loadBookmark();
		if(!$bm) throw new CHttpException(404, "Закладки не существует. Вероятно, она уже удалена.");

		if(!$bm->delete()) throw new CHttpException(500, "Не получилось удалить закладку. Попробуйте попозже.");

		echo json_encode(array("book_id" => (int) $bm->book_id, "orig_id" => (int) $bm->orig_id, "status" => "rm"));
	}

	public function actionReorder() {
		$sql = "";
		foreach($_POST as $book_id => $ord) {
			$ord = (int) $ord;
			$book_id = (int) $book_id;
			$sql .= "UPDATE bookmarks SET ord = $ord WHERE user_id = :user_id AND book_id = $book_id AND orig_id IS NULL;\n";
		}
		if($sql != "") {
			$sql = "BEGIN;\n" . $sql . "COMMIT;\n";
			Yii::app()->db->createCommand($sql)->execute(array(":user_id" => Yii::app()->user->id));
		}

		echo 1;
	}
}