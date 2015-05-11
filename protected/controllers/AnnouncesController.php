<?php
class AnnouncesController extends BookBaseController {
	/** @var Book $book */
	public $book = null;

	public function filters() {
		return array('accessControl');
	}

	public function accessRules() {
		return array(
			array('allow', 'users' => array('*'),
				'actions' => array("index", "rss", "book", "post"),
			),
			array('allow', 'users' => array('@'),
				'actions' => array("comment_reply", "comment_remove", "edit", "remove"),
			),
			array('deny', 'users'=>array('*')),
		);
	}

	public function actionIndex() {
		$C = new CDbCriteria(array(
			"condition" => "t.topics BETWEEN 80 AND 89 AND book.ac_read = 'a'",
			"order" => "t.cdate desc"
		));

		$filter = new SearchFilter("announces");
		$filter->setAttributes($_GET, true);
		if($filter->validate()) {
			if($filter->cat) {
				$n = count($filter->category->mp);
				$C->addCondition("cat.mp[1:{$n}] = '{" . join(",", $filter->category->mp) . "}'");
			}

			if($filter->s_lang != 0) $C->addCondition("book.s_lang = {$filter->s_lang}");
			if($filter->t_lang != 0) $C->addCondition("book.t_lang = {$filter->t_lang}");

			if($filter->ready == 1) $C->addCondition("book.n_verses != 0 AND book.d_vars >= book.n_verses");
			elseif($filter->ready == 2) $C->addCondition("book.n_verses != 0 AND book.d_vars < book.n_verses");
			elseif($filter->ready == 3) $C->addCondition("book.n_vars != 0 AND book.d_vars < book.n_verses");

			if($filter->gen) $C->addCondition("book.ac_read = 'a' AND book.ac_gen = 'a'");
			if($filter->tr) $C->addCondition("book.ac_tr = 'a'");

			if($filter->topic) $C->addCondition("t.topics = '{$filter->topic}'");
		}

		$dp = new CActiveDataProvider(Announce::model()->with("book.cat", "book.owner", "seen"), array(
			"criteria" => $C,
			"pagination" => array("pageSize" => 20),
		));

		$this->side_view = array("index_side" => array("filter" => $filter));
		$this->render("index", array("dp" => $dp));
	}

	public function actionRss() {
		$topic = (int) $_GET["topic"];

		$announces = Announce::model()->with("book")->findAll(array(
			"condition" => isset(Yii::app()->params["blog_topics"]["announce"][$topic]) ? "t.topics = '{$topic}'" : "t.topics BETWEEN 80 AND 89 AND book.ac_read = 'a'",
			"order" => "t.cdate desc",
			"limit" => 20,
		));

		$this->renderPartial("rss", array("announces" => $announces));
	}

	public function actionBook($book_id) {
		$book = $this->loadBook($book_id);

		$lenta = new CActiveDataProvider(BlogPost::model()->with("author", "seen")->book($book->id), array(
			"criteria" => array("condition" => "t.topics BETWEEN 80 AND 89", "order" => "t.cdate desc"),
			"pagination" => array("pageSize" => 20),
		));

		$this->side_view = array("book_side" => array("book" => $book), "//book/index_side" => array("book" => $book));

		$this->render("book", array("book" => $book, "lenta" => $lenta));
	}

	public function actionPost($book_id, $post_id) {
		$post_id = (int) $post_id;
		$book = $this->loadBook($book_id);

		$post = BlogPost::model()->with("author", "seen")->findByPk($post_id, "t.topics BETWEEN 80 AND 89 AND book_id = :book_id", array(":book_id" => $book->id));
		$post->book = $this->book;

		if(!$post || !$post->isAnnounce) throw new CHttpException(404, "Анонса не существует. Возможно, он удалён.");

		$comments = Comment::model()->with("author")->post($post->id)->newer($post->seen->seen)->findAll();

		$post->setSeen();

		$this->side_view = array("book_side" => array("book" => $book), "//book/index_side" => array("book" => $book));

		$this->render("post", array("book" => $this->book, "post" => $post, "comments" => $comments));
	}

	public function actionComment_reply($book_id, $post_id, $comment_id = 0) {
		$post_id = (int) $post_id;
		$comment_id = (int) $comment_id;
		if(!isset($_POST["Comment"])) $this->redirect("/book/{$book_id}/announces/{$post_id}");

		$this->loadBook($book_id);
		// анонсы у нас могут все неанонимы комментировать, я так понял?
//		if(!$this->book->can("blog_c")) throw new CHttpException(403, "Вы не можете оставлять комментарии в блоге этого перевода. " . $this->book->getWhoCanDoIt("blog_c"));

		if($comment_id) {
			$parent = Comment::model()->with("post", "author")->findByPk($comment_id, "post_id = :post_id", array(":post_id" => $post_id));
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
				$comment->is_new = true;
				echo json_encode(array(
					"id" => $comment->id, "pid" => $comment->pid,
					"html" => $this->renderPartial("//blog/_comment", array("comment" => $comment), true),
				));
			}
		} else {
			$this->redirect($parent->post->url . "#cmt_" . $comment->id);
		}
	}

	public function actionComment_remove($book_id, $post_id, $comment_id) {
		$post_id = (int) $post_id;
		$comment_id = (int) $comment_id;
		$this->loadBook($book_id);

		if(!Yii::app()->request->isPostRequest) $this->redirect("/blog/{$post_id}");

		$json = array("id" => $comment_id);

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

	public function actionEdit($book_id, $post_id = 0) {
		$post_id = (int) $post_id;
		$book = $this->loadBook($book_id);

		if(!$book->can("announce") && !Yii::app()->user->can("blog_moderate")) {
			throw new CHttpException(403, "Вы не можете анонсировать этот перевод. " . $book->getWhoCanDoIt("announce"));
		}

		if($post_id != 0) {
			$post = Announce::model()->findByPk($post_id, "t.topics BETWEEN 80 AND 89 AND book_id = :book_id", array(":book_id" => $book->id));
			if(!$post) throw new CHttpException(404, "Анонса не существует. Возможно, его удалили.");
		} else {
			$post = new Announce();
			$post->user_id = Yii::app()->user->id;
			$post->book_id = $book->id;
			$post->cdate = date("Y-m-d H:i:s");
			if($post->wasToday) {
				Yii::app()->user->setFlash("warning", "Внимание! <span title='По московскому времени'>Сегодня</span> уже был опубликован анонс этого перевода. Вы сможете разместить следующий анонс только завтра.");
			}
		}
		$post->book = $book;

		if(isset($_POST["Announce"])) {
			$post->attributes = $_POST["Announce"];

			if($post->save()) {
				// Добавляем пост в мои обсуждения
				$post->setTrack();

				$this->redirect($post->url);
			}
		}

		$this->side_view = array("edit_side" => array("book" => $book));
		$this->render("edit", array("book" => $book, "post" => $post));
	}

	public function actionRemove($book_id, $post_id) {
		$post_id = (int) $post_id;
		$this->loadBook($book_id);

		if(!Yii::app()->user->can("blog_moderate")) {
			throw new CHttpException(403, "Только модераторы блогов Нотабеноида могут удалять анонсы.");
		}

		if(!$_POST["really"]) $this->redirect($this->book->getUrl("announces"));

		$post = BlogPost::model()->findByPk($post_id, "t.topics BETWEEN 80 AND 89 AND t.book_id = :book_id", array(":book_id" => $this->book->id));
		if(!$post) throw new CHttpException(404, "Анонса не существует. Возможно, его уже удалили.");
		$post->book = $this->book;

		$post->delete();

		$this->redirect($this->book->getUrl("announces"));
	}
}