<?php
class OrigController extends Controller {
	public $siteArea = "books"; // | films | phrases

	/** @property Chapter $chap	*/
	public $chap;

	public function filters() {
		return array('accessControl');
	}

	public function accessRules() {
		return array(
			array('allow',
				'actions'=>array("index", "comments"),
				'users'=>array('*'),
			),
			array('allow',
				'actions'=>array("edit", "remove", "translate", "tr_rm", "comment_reply", "comment_remove", "comment_edit", "comment_rate"),
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
	 * @return Chapter
	 * @throws CHttpException
	 */
	protected function loadChapter($book_id, $chap_id) {
		$book_id = (int) $book_id;
		$chap_id = (int) $chap_id;
		/** @var Chapter $chap */
		$chap = Chapter::model()->with("book.membership")->findByPk($chap_id);

		if(!$chap) throw new CHttpException(404, "Главы не существует. Возможно, она была удалена. <a href='/book/{$book_id}/'>Вернуться к оглавлению перевода</a>.");
		if($chap->book->id != $book_id) $this->redirect($chap->book->getUrl($chap_id));

		// ac_read для всего перевода. Если нельзя в весь перевод, редиректим в оглавление перевода, пусть контроллер Book объясняет, почему да как.
		if(!$chap->book->can("read")) $this->redirect($chap->book->url);

		// ac_read для этой главы
		if(!$chap->can("read")) {
			$msg = $chap->deniedWhy;
			$msg .= "<br /><br /><a href='{$chap->book->url}'>Вернуться к оглавлению</a>.";
			throw new CHttpException(403, $msg);
		}

		return $chap;
	}

	/**
	 * @param integer $book_id
	 * @param integer $chap_id
	 * @param integer $orig_id
	 * @param mixed $with
	 * @return Orig
	 * @throws CHttpException
	 */
	protected function loadOrig($book_id, $chap_id, $orig_id, $with = null) {
		$book_id = (int) $book_id;
		$chap_id = (int) $chap_id;
		$orig_id = (int) $orig_id;

		if($with === null) $with = array("chap.book.membership", "seen");

		$orig = Orig::model()
			->with($with)
			->findByPk(
				(int) $orig_id,
				array(
					"condition" => "t.chap_id = :chap_id AND chap.book_id = :book_id",
					"params" => array(":chap_id" => $chap_id, ":book_id" => $book_id)
				)
		);
		if(!$orig) throw new CHttpException(404, "Фрагмент оригинала, увы, удалён. Вернуться к <a href='/book/{$book_id}/{$chap_id}'>переводу главы</a> или <a href='/book/{$book_id}'>оглавлению перевода</a>.");

		// ac_read для всего перевода. Если нельзя в весь перевод, редиректим в оглавление перевода, пусть контроллер Book объясняет, почему да как.
		if(!$orig->chap->book->can("read")) $this->redirect($orig->chap->book->url);

		// ac_read для этой главы
		if(!$orig->chap->can("read")) {
			$msg = $orig->chap->deniedWhy;
			$msg .= "<br /><br /><a href='{$orig->chap->book->url}'>Вернуться к оглавлению</a>.";
			throw new CHttpException(403, $msg);
		}

		return $orig;
	}

	public function actionIndex($book_id, $chap_id, $orig_id) {
		if($orig_id == 0) $this->redirect("/book/" . intval($book_id) . "/" . intval($chap_id));

		$orig = $this->loadOrig($book_id, $chap_id, $orig_id);

		$OrdFields = array("A" => "ord", "S" => "t1");
		if($orig->chap->book->typ == "S") $ord_val = $orig->t1;
		else $ord_val = $orig->ord;

		$pos = Yii::app()->db->createCommand("SELECT COUNT(*) FROM orig WHERE chap_id = :chap_id AND {$OrdFields[$orig->chap->book->typ]} < :ordval")
			->queryScalar(array(":chap_id" => $orig->chap->id, ":ordval" => $ord_val));

		$page = floor($pos / 50) + 1;

		$this->redirect($orig->chap->url . "?Orig_page={$page}#o{$orig->id}");
	}

	public function actionComments($book_id, $chap_id, $orig_id) {
		$ajax = isset($_POST["ajax"]) ? (int) $_POST["ajax"] : (int) $_GET["ajax"];
		$orig = $this->loadOrig($book_id, $chap_id, $orig_id);

		/** @var $comments Comment[] */
		$comments = Comment::model()->with("author")->orig($orig->id)->newer($orig->seen->seen)->findAll();

		// AUTOFIX
		$n_comments = 0;
		foreach($comments as $c) if(!$c->isDeleted()) $n_comments++;
		if($n_comments != $orig->n_comments) {
			$orig->n_comments = $n_comments;
			$orig->save(false, array("n_comments"));
		}

		$orig->setSeen();

		if($ajax) {
			$this->renderPartial("comments", array("orig" => $orig, "comments" => $comments));
		} else {
			echo "NOT IMPLEMENTED";
		}
	}

	public function actionComment_reply($book_id, $chap_id, $orig_id, $comment_id = 0) {
		$orig = $this->loadOrig($book_id, $chap_id, $orig_id);
		$comment_id = (int) $comment_id;

		if(!$orig->chap->can("comment")) throw new CHttpException(403, "Вы не можете комментировать этот перевод. " . $orig->chap->getWhoCanDoIt("comment", false));
		if(!isset($_POST["Comment"])) $this->redirect($orig->chap->url);

		if($comment_id) {
			$parent = Comment::model()->with("author")->findByPk($comment_id);
			if(!$parent) throw new CHttpException(404, "Вы пытаетесь ответить на несуществующий комментарий.");
		} else {
			$parent = new Comment();
		}
		$parent->orig = $orig;
		$parent->orig_id = $parent->orig->id;

		$comment = new Comment();
		$comment->setAttributes($_POST["Comment"]);

		if($parent->reply($comment)) {
			$parent->orig->afterCommentAdd($comment, $parent);
		} else {
			Yii::app()->user->setFlash("error", $comment->getErrorsString());
		}

		if($_POST["ajax"]) {
			if(Yii::app()->user->hasFlash("error")) {
				echo json_encode(array("error" => Yii::app()->user->getFlash("error")));
			} else {
				$view = Yii::app()->user->ini["t.iface"] == 1 ? "//blog/_comment-1" : "//blog/_comment";
				$comment->is_new = true;
				echo json_encode(array(
					"id" => $comment->id, "pid" => $comment->pid,
					"html" => $this->renderPartial($view, array("comment" => $comment), true),
				));
			}
		} else {
			$this->redirect($orig->chap->url);
		}
	}

	public function actionComment_remove($book_id, $chap_id, $orig_id, $comment_id) {
		$orig = $this->loadOrig($book_id, $chap_id, $orig_id);
		$comment_id = (int) $comment_id;

		if(!Yii::app()->request->isPostRequest) $this->redirect($orig->chap->url);

		$json = array("id" => $comment_id);

		// Загружаем удаляемый комментарий вместе с постом
		$comment = Comment::model()->with("orig.chap.book.membership")->findByPk($comment_id);
		if(!$comment) {
			$json["error"] = "Вы пытаетесь удалить несуществующий комментарий. Бросьте, пустое.";
		} else {
			// Права доступа: свой комментарий, в моём посте, модератор блога
			if(!$comment->can("delete")) {
				$json["error"] = "Вы не можете удалить этот комментарий.";
			} else if($comment->delete()) {
				$comment->orig->afterCommentRm($comment);
			} else {
				$json["error"] = "Не получилось удалить комментарий :(";
			}
		}

		echo json_encode($json);
	}

	public function actionComment_edit($book_id, $chap_id, $orig_id, $comment_id) {
		$orig = $this->loadOrig($book_id, $chap_id, $orig_id);
		$comment_id = (int) $comment_id;

		$json = ["id" => $comment_id];

		$comment = Comment::model()->findByPk($comment_id);
		$comment->orig = $orig;

		if(!$comment) throw new CHttpException(404, "Комментарий, вероятно, удалён.");
		if(!$comment->can("edit")) throw new CHttpException(403, "Свои комментарии можно редактировать только в течение часа после их написания. Вероятно, время вышло.");

		if(isset($_POST["C"])) {
			unset($_POST["pid"]); // на всякий случай
			$comment->setAttributes($_POST["C"]);
			$comment->save();
		}
		$json["body"] = $comment->body;

		echo json_encode($json);
	}

	public function actionComment_rate($book_id, $chap_id, $orig_id, $comment_id) {
		$orig = $this->loadOrig($book_id, $chap_id, $orig_id);
		if(!$orig->chap->can("comment")) throw new CHttpException(403, "Вы не можете оценивать комментарии в этом переводе.");

		if(!Yii::app()->request->isPostRequest) throw new CHttpException(400, "");

		/** @var Comment $comment */
		$comment = Comment::model()->with("orig")->findByPk($comment_id);
		if(!$comment) throw new CHttpException(404, "Комментарий удалён.");
		if($comment->orig_id != $orig_id) throw new CHttpException(400, "");
		if(!$comment->can("rate")) throw new CHttpException(403, "Вы не можете оценивать этот комментарий.");

		$comment->rate((int) $_POST["mark"]);

		echo json_encode([
			"id" => $comment->id,
			"rating" => $comment->rating,
			"n_votes" => $comment->n_votes,
		]);
	}

	public function actionEdit($book_id, $chap_id, $orig_id) {
		$ajax = $_POST["ajax"] || $_GET["ajax"];

		$chap = $this->loadChapter($book_id, $chap_id);
		if(!$chap->book->can("chap_edit")) throw new CHttpException(403, "Вы не можете редактировать оригинал в этом переводе.");

		/** @var Orig $orig */
		if($orig_id == 0) {
			$orig = new Orig();
			$orig->chap_id = $chap->id;
			$orig->chap = $chap;
			if(!isset($_POST["Orig"])) $orig->initNew((int) $_GET["after"]);

		} else {
			$orig = Orig::model()->findByPk((int) $orig_id, array("condition" => "chap_id = :chap_id", "params" => array(":chap_id" => $chap->id)));
			if(!$orig) throw new CHttpException(404, "Фрагмента оригинала не существует. Вероятно, его кто-то удалил.");
			$orig->chap = $chap;
		}
		$orig->setScenario("edit_{$chap->book->typ}");

		if(isset($_POST["Orig"])) {
			$orig->setAttributes($_POST["Orig"]);

			if($orig->save()) {
				$chap->setModified();

				// Проверяем, нет ли фрагмента с таким же ord, и если есть, то смещаем все вниз
				if($orig->ord != "") {
					$p = array(":chap_id" => $orig->chap_id, ":ord" => intval($orig->ord), ":id" => $orig->id);
					if(count(Yii::app()->db->createCommand("SELECT 1 FROM orig WHERE chap_id = :chap_id AND ord = :ord AND id != :id LIMIT 1")->queryAll(false, $p))) {
						Yii::app()->db->createCommand("UPDATE orig SET ord = ord + 1 WHERE chap_id = :chap_id AND ord >= :ord AND id != :id")->execute($p);
					}
				}

				if($ajax) {
					$JSON = clone $orig;
					$JSON->t1 = $JSON->nicetime("t1");
					$JSON->t2 = $JSON->nicetime("t2");
					$JSON->body = $orig->render();
					echo json_encode($JSON);
					Yii::app()->end();
				} else {
					$this->redirect($orig->url);
				}
			} else {
				if($ajax) {
					echo json_encode(array("error" => $orig->errorsString));
					Yii::app()->end();
				} else {
					Yii::app()->user->setFlash("error", $orig->errorsString);
				}
			}
		}

		$p = array("orig" => $orig, "ajax" => $ajax);
		$view = "edit_{$chap->book->typ}-" . intval(Yii::app()->user->ini["t.iface"]);
		if($ajax) $this->renderPartial($view, $p);
		else $this->render($view, $p);
	}

	public function actionRemove($book_id, $chap_id, $orig_id) {
		$ajax = isset($_POST["ajax"]) ? (int) $_POST["ajax"] : (int) $_GET["ajax"];

		$chap = $this->loadChapter($book_id, $chap_id);
		if(!$chap->book->can("chap_edit")) throw new CHttpException(403, "Вы не можете редактировать оригинал в этом переводе.");

		/** @var Orig $orig */
		$orig = Orig::model()->findByPk((int) $orig_id, array("condition" => "chap_id = :chap_id", "params" => array(":chap_id" => $chap->id)));
		if(!$orig) throw new CHttpException(404, "Фрагмента оригинала не существует. Вероятно, его кто-то уже удалил.");
		$orig->chap = $chap;

		/** @var Translation[] $trs все переводы, которые сейчас будут удалены */
		$trs = Translation::model()->chapter($chap->id)->findAllByAttributes(array("orig_id" => $orig->id));
		$n_trs = count($trs);

		if(!$orig->delete()) throw new CHttpException(500, "Не получилось удалить фрагмент оригинала.");

		$chap->setModified();

		// (chapters, books).(n_verses, n_vars, d_vars, last_tr)
		$chap->n_verses--; $chap->book->n_verses--;
		$chap->n_vars -= $n_trs; $chap->book->n_vars -= $n_trs;
		if($n_trs > 0) { $chap->d_vars--; $chap->book->d_vars--; }
		$chap->last_tr = new CDbExpression("now()");
		$chap->book->last_tr = new CDbExpression("now()");

		// users.(n_trs, rate_t), groups.(n_trs, rating)
		$sql = array(); $params = array(":book_id" => $chap->book_id);
		foreach($trs as $i => $tr) {
			if($tr->user_id == 0) continue;
			$sql[] = "UPDATE users  SET n_trs = n_trs - 1, rate_t = rate_t - :rating{$i} WHERE id = :user_id{$i};";
			$sql[] = "UPDATE groups SET n_trs = n_trs - 1, rating = rating - :rating{$i} WHERE book_id = :book_id AND user_id = :user_id{$i};";
			$params[":user_id{$i}"] = $tr->user_id;
			$params[":rating{$i}"] = $tr->rating;
		}

		$chap->save(false, array("n_verses", "n_vars", "d_vars", "last_tr"));
		$chap->book->save(false, array("n_verses", "n_vars", "d_vars", "last_tr"));
		if(count($sql) > 0) {
			$sql = "BEGIN;\n" . join("\n", $sql) . "\nCOMMIT;";
			Yii::app()->db->createCommand($sql)->execute($params);
		}

		echo json_encode(array("status" => "ok", "n_vars" => $chap->n_vars, "d_vars" => $chap->d_vars, "n_verses" => $chap->n_verses));
	}

	public function actionTranslate($book_id, $chap_id, $orig_id) {
		$DEBUG = false;

		$ajax = isset($_POST["ajax"]) ? (int) $_POST["ajax"] : (int) $_GET["ajax"];
		if($DEBUG) $ajax = 1;

		$orig = $this->loadOrig($book_id, $chap_id, $orig_id, array("chap.book.membership"));
		if(!$orig->chap->can("tr")) throw new CHttpException(403, "Вы не можете добавлять свои версии в этом переводе. " . $orig->chap->getWhoCanDoIt("tr"));

		$tr_id = (int) $_GET["tr_id"];

		/* Загружаем или создаём версию перевода */
		if($tr_id == 0) {
			/** @var Translation $tr */
			$tr = new Translation();
			$tr->orig_id = $orig->id;
			$tr->chap_id = $orig->chap->id;
			$tr->book_id = $orig->chap->book->id;
			$tr->user_id = Yii::app()->user->id;
		} else {
			/** @var Translation $tr */
			$tr = Translation::model()->with("user")->findByPk($tr_id, array(
				"condition" => "chap_id = :chap_id AND book_id = :book_id AND orig_id = :orig_id",
				"params" => array(":chap_id" => $orig->chap->id, ":book_id" => $orig->chap->book->id, ":orig_id" => $orig->id,
			)));
			if(!$tr) throw new CHttpException(404, "Версия перевода, которую вы пытаетесь отредактировать, кем-то уже удалена.");

			if($orig->chap->book->membership->status != GroupMember::MODERATOR) {
				if($tr->user_id == Yii::app()->user->id) {
					$tr->rating = 0;
					$tr->n_votes = 0;
					$tr->removeMarks();
				} else {
					throw new CHttpException(403, "Только модераторы могут редактировать чужие версии перевода.");
				}
			}
		}
		$tr->orig = $orig;
		$tr->chap = $orig->chap;
		$tr->book = $orig->chap->book;

		if(isset($_POST["Translation"])) {
			$tr->setAttributes($_POST["Translation"]);
			if($tr->save()) {
				$orig->chap->setModified();

				if($tr_id == 0) {
					// Добавили новый перевод
					if($orig->chap->book->membership === null || $orig->chap->book->membership->status === "") {
						// Встепаем в группу, раз нас там не было
						$orig->chap->book->membership = new GroupMember();
						$orig->chap->book->membership->book_id = $orig->chap->book->id;
						$orig->chap->book->membership->user_id = Yii::app()->user->id;
						$orig->chap->book->membership->status = GroupMember::CONTRIBUTOR;
						$orig->chap->book->membership->n_trs = 1;
						$orig->chap->book->membership->rating = 0;
					} else {
						// Обновляем статистику группы
						$orig->chap->book->membership->n_trs++;
					}
					$orig->chap->book->membership->last_tr = new CDbExpression("now()");
					$orig->chap->book->membership->save(false);
				}

				if($ajax) {
					$orig->trs = Translation::model()->with("user")->orig($orig->id)->findAll();
					$json = array(
						"n_vars"   => $orig->chap->n_vars,
						"d_vars"   => $orig->chap->d_vars,
						"n_verses" => $orig->chap->n_verses,
						"text"     => $orig->renderTranslations(),
					);

                    if($DEBUG) echo "<pre>" . htmlspecialchars(print_r($json, true)) . "</pre>";
					else echo json_encode($json);
				} else {
					$this->redirect($orig->url);
				}
			} else {
				// Сохранить не получилось
				if($ajax) {
                    echo json_encode(array("error" => $tr->errorsString));
				} else {
					Yii::app()->user->setFlash("error", $tr->errorsString);
					$this->redirect($orig->url);
				}
			}
		}
	}

	public function actionTr_rm($book_id, $chap_id, $orig_id) {
		$chap = $this->loadChapter($book_id, $chap_id);

		$user = Yii::app()->user;

		/** @var Translation $tr  */
		$tr = Translation::model()->findByPk((int) $_POST["tr_id"], array("condition" => "chap_id = :chap_id AND book_id = :book_id", "params" => array(":chap_id" => $chap->id, ":book_id" => $chap->book_id)));
		if(!$tr) throw new CHttpException(404, "Этот вариант перевода уже удалили.");
		$tr->chap = $chap;

		if($tr->user_id != $user->id && $chap->book->membership->status != GroupMember::MODERATOR)
			throw new CHttpException(403, "Только модераторы могут удалять чужие переводы.");

		if($tr->delete()) {
			$chap->setModified();
		}

		echo json_encode(array("status" => "ok", "tr_id" => $tr->id, "n_vars" => $chap->n_vars, "d_vars" => $chap->d_vars, "n_verses" => $chap->n_verses));
	}
}
?>