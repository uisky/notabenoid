<?php
class BookEditController extends BookBaseController {
	public $book;

	public function filters() {
		return array('accessControl');
	}

	public function accessRules() {
		return array(
			array('allow', 'users'=>array('@'),
				'actions' => array("index", "cat", "info", "access", "remove"),
			),
			array('deny', 'users'=>array('*'))
		);
	}

	/** @return Book */
	protected function loadBook($book_id, $scenario = null) {
		$book_id = (int) $book_id;

		if($book_id == 0) {
			if(!isset($_SESSION["book_for_edit"])) {
				$this->redirect("/book/0/edit?typ=" . substr($_GET["typ"], 0, 1));
			}
			$book = unserialize($_SESSION["book_for_edit"]);
		} else {
			$book = parent::loadBook($book_id, "Book");

			$can = $book->can("book_edit");
			if($this->action->id == "cat") $can |= Yii::app()->user->can("cat_moderate");
			if(!$can) {
				throw new CHttpException(403, "Вы не можете редактировать этот перевод. " . $book->getWhoCanDoIt("book_edit"));
			}
		}

		if($scenario) $book->setScenario($scenario);
		return $book;
	}

	/** @param Book $book */
	protected function saveBook($book) {
		if($book->isNewRecord) {
			$r = $book->validate();
			$_SESSION["book_for_edit"] = serialize($book);
			if(!$r) return false;
			return true;
		} else {
			return $book->save();
		}
	}

	public function actionIndex($book_id) {
		$book_id = (int) $book_id;
		if($book_id != 0) $this->redirect("/book/{$book_id}/edit/info");

		if(!isset($_GET["typ"]) || !isset(Yii::app()->params["book_types"][$_GET["typ"]])) {
			$this->render("index");
			Yii::app()->end();
		}

		$book = new Book();
		$me = Yii::app()->user->getModel();
		$book->owner = $me;
		$book->owner_id = $me->id;
		$book->t_lang = $me->lang;
		$book->typ = $_GET["typ"];

		$_SESSION["book_for_edit"] = serialize($book);

		$this->redirect($book->getUrl("edit/cat"));
	}

	public function actionCat($book_id) {
		$book = $this->loadBook($book_id, "cat");

		if(isset($_POST["cat_id"])) {
			$old_cat_id = $book->cat_id;
			$book->cat_id = (int) $_POST["cat_id"];
			if($book->cat_id == 0) $book->cat_id = null;

			if($this->saveBook($book)) {
				if(!$book->isNewRecord) {
					if($book->cat_id && $old_cat_id != $book->cat_id && !Yii::app()->user->can("cat_moderate")) {
						Yii::app()->db->createCommand("SELECT moder_book_cat_put(:book_id)")->execute(array(":book_id" => $book->id));
					} else {
						Yii::app()->db->createCommand("DELETE FROM moder_book_cat WHERE book_id = :book_id")->execute(array(":book_id" => $book->id));
					}
				}
				$this->redirect($book->isNewRecord ? $book->getUrl("edit/info") : $book->url);
			} else {
				Yii::app()->user->setFlash("error", $book->errorsString);
			}
		}

		$cats = Category::model()->tree()->findAll();

		$this->render("cat", array("book" => $book, "cats" => $cats));
	}

	public function actionInfo($book_id) {
		$book = $this->loadBook($book_id, "info");

		if(isset($_POST["Book"])) {
			$book->setAttributes($_POST["Book"]);
			$book->new_img = CUploadedFile::getInstance($book, "new_img");
			if($this->saveBook($book)) {
				// Помечаем все главы, как изменившиеся
				foreach($book->chapters as $chap) $chap->setModified();

				$this->redirect($book->isNewRecord ? $book->getUrl("edit/access") : $book->url);
			}
		}

		$this->render("info", array("book" => $book));
	}

	public function actionAccess($book_id) {
		$book = $this->loadBook($book_id, "access");

		if(!$book->can("owner")) {
			throw new CHttpException(403, "Права доступа в перевод может определять только создатель перевода.");
		}

		if(isset($_POST["Book"])) {
			$prev_facecontrol = $book->facecontrol;

			$book->setAttributes($_POST["Book"]);
			if($book->save()) {
				if($book_id == 0) {
					// Вступаем в группу сразу модератором
					Yii::app()->db->createCommand("INSERT INTO groups (book_id, user_id, status) VALUES (:book_id, :user_id, 2)")
						->query(array("book_id" => $book->id, "user_id" => Yii::app()->user->id));

					// Добавляем в закладки
					Yii::app()->db->createCommand("
						INSERT INTO bookmarks (user_id, book_id, ord)
						VALUES
						(:user_id, :book_id, (SELECT COALESCE(MAX(ord),0) + 1 FROM bookmarks WHERE user_id = :user_id AND orig_id IS NULL))
					")->execute(array(
						":user_id" => Yii::app()->user->id,
						":book_id" => $book->id,
					));

					// Отправляем на очередь модерации, если выбран раздел каталога
					if($book->cat_id && !Yii::app()->user->can("cat_moderate")) {
						Yii::app()->db->createCommand("SELECT moder_book_cat_put(:book_id)")->execute(array(":book_id" => $book->id));
					}
				} else {
					// Если фейсконтроль понизился до FC_OPEN
					if($prev_facecontrol != Book::FC_OPEN && $book->facecontrol == Book::FC_OPEN) {
						// удаляем всех, кто ничего не сделал в группе
						Yii::app()->db->createCommand("DELETE FROM groups WHERE book_id = :book_id AND status = :member AND n_trs = 0")
							->execute(array(":member" => GroupMember::MEMBER, ":book_id" => $book->id));

						// всем MEMBER присваиваем статус CONTRIBUTOR
						Yii::app()->db->createCommand("UPDATE groups SET status = :contributor WHERE book_id = :book_id AND status = :member AND n_trs > 0")
							->execute(array(":contributor" => GroupMember::CONTRIBUTOR, ":member" => GroupMember::MEMBER, ":book_id" => $book->id));

						// @todo: все действия, назначенные для группы назначать роли "все"; поместить этот код до $book->save() (Book::beforeSave()?)
						/*
							foreach(array_keys(Yii::app()->params["ac_areas"]) as $ac) {
								if($book->$ac == "g") $book->$ac = "a";
							}
						*/
					}
				}

				$this->redirect($book->url);
			}
		}

		$this->render("access", array("book" => $book));
	}

	public function actionRemove($book_id) {
		$book = $this->loadBook($book_id);

		if(!$book->can("owner")) {
			throw new CHttpException(403, "Удалить перевод может только его создатель.");
		}

		if($_POST["really"] == 1) {
			$book->delete();
			$this->redirect(Yii::app()->user->url);
		}
	}
}
