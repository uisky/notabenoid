<?php
class BookBlogController extends BookBaseController {
	/** @var Book $book */
	public $book = null;

	public function filters() {
		return array('accessControl');
	}

	public function accessRules() {
		return array(
			array('allow', 'users' => array('*'),
				'actions' => array("index", "post"),
			),
			array('allow', 'users' => array('@'),
				'actions' => array(
					"comment_reply", "comment_remove", "comment_rate", "edit", "remove",
				),
			),
			array('deny',
				'users'=>array('*'),
			),
		);
	}

	public function actionIndex($book_id) {
		$this->loadBook($book_id);

		if(!$this->book->can("blog_r")) {
			throw new CHttpException(403, "У вас нет доступа в блог этого перевода. " . $this->book->getWhoCanDoIt("blog_r"));
		}

		$criteria = new CDbCriteria();
		if(isset($_GET["topic"])) {
			$topic = (int) $_GET["topic"];
			if(!isset(Yii::app()->params["blog_topics"]["book"][$topic])) $this->redirect($this->book->getUrl("blog"));
			$criteria->addCondition("t.topics = '{$topic}'");
		} else {
			$criteria->addCondition("t.topics BETWEEN 1 AND 19");
			$topic = 0;
		}

		$lenta = new CActiveDataProvider(BlogPost::model()->with("author", "seen")->book($this->book->id), array(
			"criteria" => $criteria,
			"pagination" => array("pageSize" => 20),
		));

		$this->side_view = array(
			"blog_side" => array("book" => $this->book, "topic" => $topic),
			"//book/index_side"=> array("book" => $this->book),
		);

		$this->render("index", array("book" => $this->book, "lenta" => $lenta, "topic" => $topic));
	}

	public function actionPost($book_id, $post_id) {
		$this->loadBook($book_id);
		$post_id = (int) $post_id;

		if(!$this->book->can("blog_r")) {
			throw new CHttpException(403, "У вас нет доступа в блог этого перевода. " . $this->book->getWhoCanDoIt("blog_r"));
		}

		$post = BlogPost::model()->with("author", "seen")->findByPk($post_id);
		if(!$post) throw new CHttpException(404, "Поста не существует. Возможно, он удалён. Почитайте лучше <a href='" . $this->book->getUrl('blog') . "'>другие посты</a> в блоге этого перевода.");
		$post->book = $this->book;

		if($post->book_id == 0) $this->redirect("/blog/{$post_id}");
		if($post->book_id != $this->book->id) $this->redirect($this->book->url);
		if($post->isAnnounce) $this->redirect($post->url);


		$comments = Comment::model()->with("author")->post($post_id)->newer($post->seen->seen)->findAll();

		$post->setSeen();

		$this->side_view = array("blog_side" => array("book" => $this->book, "topic" => $post->topics));

		$this->render("post", array("book" => $this->book, "post" => $post, "comments" => $comments));
	}

	public function actionComment_reply($book_id, $post_id, $comment_id = 0) {
		if(!isset($_POST["Comment"])) $this->redirect("/book/{$book_id}/blog/{$post_id}");
		$post_id = (int) $post_id;
		$comment_id = (int) $comment_id;

		$this->loadBook($book_id);
		if(!$this->book->can("blog_r")) throw new CHttpException(403, "У вас нет доступа в блог этого перевода. " . $this->book->getWhoCanDoIt("blog_r"));
		if(!$this->book->can("blog_c")) throw new CHttpException(403, "Вы не можете оставлять комментарии в блоге этого перевода. " . $this->book->getWhoCanDoIt("blog_c"));

		if($comment_id) {
			$parent = Comment::model()->with("post", "author")->findByPk($comment_id);
			if(!$parent) throw new CHttpException(404, "Вы пытаетесь ответить на несуществующий комментарий.");
		} else {
			$parent = new Comment();
			$parent->post = BlogPost::model()->with("author", "seen")->findByPk($post_id);
			$parent->post_id = $parent->post->id;
		}

		if($parent->post->book_id != $this->book->id) {
			$this->redirect($this->book->getUrl("blog/{$post_id}/c{$comment_id}/reply"));
		}

		$comment = new Comment();
		$comment->setAttributes($_POST["Comment"]);

		if($parent->reply($comment)) {
			$parent->post->afterCommentAdd($comment, $parent);
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
			$this->redirect($parent->post->url . "#cmt_" . $comment->id);
		}
	}

	public function actionComment_remove($book_id, $post_id, $comment_id) {
		$this->loadBook($book_id);
		$post_id = (int) $post_id;
		$comment_id = (int) $comment_id;

		if(!$this->book->can("blog_r")) {
			throw new CHttpException(403, "У вас нет доступа в блог этого перевода. " . $this->book->getWhoCanDoIt("blog_r"));
		}
		if(!$this->book->can("blog_c")) {
			throw new CHttpException(403, "Вы не можете удалять комментарии в блоге этого перевода. " . $this->book->getWhoCanDoIt("blog_c"));
		}

		if(!Yii::app()->request->isPostRequest) $this->redirect("/book/{$book_id}/blog/{$post_id}");

		$json = array("id" => $comment_id);
		$user = Yii::app()->user;

		// Загружаем удаляемый комментарий вместе с постом
		$comment = Comment::model()->with("post")->findByPk($comment_id);
		if(!$comment) {
			$json["error"] = "Вы пытаетесь удалить несуществующий комментарий. Бросьте, пустое.";
		} else {
			$comment->post->book = $this->book;

			// Права доступа: свой комментарий, в моём посте, модератор блога
			if(!$comment->can("delete")) {
				$json["error"] = "Вы не можете удалить этот комментарий.";
			}

			// Удаляем комментарий
			else if($comment->delete()) {
				$comment->post->afterCommentRm($comment);
			} else {
				$json["error"] = "Не получилось удалить комментарий :(";
			}
		}

		echo json_encode($json);
	}

	public function actionComment_rate($book_id, $post_id, $comment_id) {
		$book = $this->loadBook($book_id);
		$post_id = (int) $post_id;
		$comment_id = (int) $comment_id;

		if(!$book->can("blog_r")) {
			throw new CHttpException(403, "У вас нет доступа в блог этого перевода. " . $book->getWhoCanDoIt("blog_r"));
		}
		if(!$book->can("blog_c")) {
			throw new CHttpException(403, "Вы не можете оценивать комментарии в блоге этого перевода. " . $book->getWhoCanDoIt("blog_c"));
		}

		if(!Yii::app()->request->isPostRequest) throw new CHttpException(400, "");

		/** @var Comment $comment */
		$comment = Comment::model()->with("post")->findByPk($comment_id);
		if(!$comment) throw new CHttpException(404, "Комментарий удалён.");
		if($comment->post_id != $post_id) throw new CHttpException(400, "");
		if(!$comment->can("rate")) throw new CHttpException(403, "Вы не можете оценивать этот комментарий.");

		$comment->rate((int) $_POST["mark"]);

		echo json_encode([
			"id" => $comment->id,
			"rating" => $comment->rating,
			"n_votes" => $comment->n_votes,
		]);
	}

	public function actionEdit($book_id, $post_id = 0) {
		$this->loadBook($book_id);
		$post_id = (int) $post_id;

		if(!$this->book->can("blog_r") || !$this->book->can("blog_w")) {
			throw new CHttpException(403, "Вы не можете писать и редактировать посты в блоге этого перевода. " . $this->book->getWhoCanDoIt("blog_w"));
		}

		if($post_id != 0) {
			$post = BlogPost::model()->findByPk($post_id);
			if(!$post) {
				throw new CHttpException(404, "Поста не существует. Возможно, его удалили.");
			}
			if($post->user_id != Yii::app()->user->id and !Yii::app()->user->can("blog_moderate")) {
				throw new CHttpException(403, "Вы можете редактировать только собственные посты.");
			}
			if($post->book_id == 0) $this->redirect("/blog/{$post->id}/edit");
			if($post->book_id != $this->book->id) $this->redirect("/book/{$post->book_id}/blog/{$post->id}/edit");
			if($post->isAnnounce) throw new CHttpException(404, "Поста не существует. Возможно, его удалили.");
		} else {
			$post = new BlogPost();
			$post->user_id = Yii::app()->user->id;
			$post->book_id = $this->book->id;
			$post->topics = isset($_GET["topic"]) ? (int) $_GET["topic"] : 3;
		}
		$post->book = $this->book;

		if(isset($_POST["BlogPost"])) {
			$post->attributes = $_POST["BlogPost"];

			if($post->save()) {
				// Добавляем пост в мои обсуждения
				$post->setTrack();

				$this->redirect($post->url);
			}
		}

		$this->render("//blog/edit", array("post" => $post));
	}

	public function actionRemove($book_id, $post_id) {
		$this->loadBook($book_id);
		$post_id = (int) $post_id;

		if(!$this->book->can("blog_r") || !$this->book->can("blog_w")) {
			throw new CHttpException(403, "Вы не можете удалять посты в блоге этого перевода. " . $this->book->getWhoCanDoIt("blog_w"));
		}

		if(!$_POST["really"]) $this->redirect("/blog");

		$post = BlogPost::model()->findByPk($post_id);
		if(!$post) {
			throw new CHttpException(404, "Поста не существует. Возможно, его уже удалили.");
		}
		if($post->user_id != Yii::app()->user->id and !Yii::app()->user->can("blog_moderate")) {
			throw new CHttpException(403, "Вы можете удалять только собственные посты.");
		}
		if($post->isAnnounce) throw new CHttpException(404, "Поста не существует. Возможно, его уже удалили.");
		$post->book_id = $this->book->id;
		$post->book = $this->book;

		$post->delete();

		$this->redirect($this->book->getUrl("blog"));
	}

}