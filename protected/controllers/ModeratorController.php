<?php
class ModeratorController extends Controller {
	public $layout = "column1";

	public $areas = array(
		"catalog" => "Структура каталога",
		"book_cat" => "Переводы по разделам"
	);
	public $area = "";

	public function filters() {
		return array(
			'accessControl',
		);
	}

	public function accessRules() {
		return array(
			["allow",
				"users" => Yii::app()->user->accessFilterUsers("cat_moderate"),
				"actions" => array("index", "catalog", "kitten", "chpid", "catedit", "catremove", "catswap", "book_cat"),
			],
			["allow",
				"users" => Yii::app()->user->accessFilterUsers("blog_topic_moderate"),
				"actions" => ["blogTopic"]
			],
			['deny', 'users' => ['*']],
		);
	}

	public function actionIndex() {
		echo "<a href='/moderator/catalog'>catalog</a>";
	}

	public function actionCatalog() {
		$categories = Category::model()->tree()->with("booksCount")->findAll();
		$this->area = "catalog";

		if(isset($_GET["edit"])) {
			$edit_node = Category::model()->findByPk(intval($_GET["edit"]));
		} else {
			$edit_node = new Category();
		}

		$this->render("catalog", array("categories" => $categories, "edit_node" => $edit_node));
	}

	public function actionKitten($pid = 0) {
		if($pid) {
			$parent = Category::model()->findByPk(intval($pid));
			if(!$parent) throw new CHttpException(404, "Раздела не существует");
		} else {
			$parent = new Category();
		}

		$child = new Category();
		$child->setAttributes($_POST["Category"]);

		$child->setParent($parent);

		if(!$child->save()) {
			Yii::app()->user->setFlash("error", $child->getErrorsString());
		}

		$this->redirect("/moderator/catalog?edit={$parent->id}");
	}

	public function actionCatedit($id) {
		$cat = Category::model()->findByPk(intval($id));
		if(!$cat) throw new CHttpException(404, "Раздела не существует");

		$cat->setAttributes($_POST["Category"]);

		if($cat->save()) {
//			$cat->calcPath();
		} else {
			Yii::app()->user->setFlash("error", $cat->getErrorsString());
		}
		$this->redirect("/moderator/catalog/?edit={$cat->id}");
	}

	public function actionCatremove($id) {
		$cat = Category::model()->findByPk(intval($id));
		if(!$cat) throw new CHttpException(404, "Раздела не существует");

		// Есть ли переводы в этом разделе?
//		$has_offers = Yii::app()->db->createCommand("SELECT 1 FROM books WHERE cat_id = :id LIMIT 1")->query(array(":id" => $cat->id))->count();
//		if($has_offers) {
//			Yii::app()->user->setFlash("error", "В этом разделе есть объявления, сначала нужно удалить или перенести их.");
//			$this->redirect("/moderator/catalog");
//		}

		if(!$cat->delete()) {
			Yii::app()->user->setFlash("error", $cat->getErrorsString());
		}

		$this->redirect("/moderator/catalog");
	}

	public function actionCatswap($id) {
		$id2 = (int) $_POST["id2"];

		$cat1 = Category::model()->findByPk($id);
		$cat2 = Category::model()->findByPk($id2);
		$mp_len = count($cat1->mp);
		if(count($cat2->mp) != $mp_len) {
			Yii::app()->user->setFlash("error", "Разделы должны находиться на одном уровне вложенности.");
			$this->redirect("/moderator/catalog/?edit={$id}");
		}

		$scope1 = Yii::app()->db->createCommand("SELECT id FROM catalog WHERE mp[0:{$mp_len}] = :mp")->queryColumn(array(
			":mp" => $cat1->mpPacked
		));

		$scope2 = Yii::app()->db->createCommand("SELECT id FROM catalog WHERE mp[0:{$mp_len}] = :mp")->queryColumn(array(
			":mp" => $cat2->mpPacked
		));

		$n = $mp_len + 1;

		Yii::app()->db->createCommand()->update(
			"catalog",
			array("mp" => new CDbExpression(":mp::smallint[] || mp[{$n}:999]", array(":mp" => $cat2->mpPacked))),
			array("in", "id", $scope1)
		);

		Yii::app()->db->createCommand()->update(
			"catalog",
			array("mp" => new CDbExpression(":mp::smallint[] || mp[{$n}:999]", array(":mp" => $cat1->mpPacked))),
			array("in", "id", $scope2)
		);

		$this->redirect("/moderator/catalog/?edit={$id}");
	}

	public function actionChpid($id) {
		$id = (int) $id;

		// Выводить отладочную информацию при вычислениях, и вместо редиректа - ссылка ok
		$debug = false;
		// При отладке ничего не писать в базу
		$debug_dry = false;

		$handle = Category::model()->findByPk(intval($id));
		if(!$handle) throw new CHttpException(404, "Раздела не существует");

		$parent = Category::model()->findByPk(intval($_POST["id2"]));
		if(!$parent) throw new CHttpException(404, "Родительский раздел удалён.");

		if($parent->id == $handle->id) {
			Yii::app()->user->setFlash("error", "Перемещение раздела в самого себя &ndash; благородная, но, увы, недостижимая цель.");
		} elseif(array_intersect_assoc($handle->mp, $parent->mp) == $handle->mp) {
			Yii::app()->user->setFlash("error", "Нельзя переместить раздел в его подраздел.");
		} else {
			if($debug) echo "Handle: {$handle->dump}<br />";
			if($debug) echo "Parent: {$parent->dump}<br />";

			$n = count($handle->mp);
			$cats = Category::model()->findAll(
				array(
					"order" => "mp",
					"condition" => "mp[1:{$n}] = :mp",
					"params" => array(
						":mp" => "{" . join(",", $handle->mp) . "}"
					),
				)
			);

			if($debug) {
				echo "tree:<ul>\n";
				foreach($cats as $cat) {
					echo "<li>{$cat->dump}</li>\n";
				}
				echo "</ul>\n\n";
			}

			$t = Yii::app()->db->createCommand("SELECT MAX(mp) FROM catalog WHERE pid = :pid")->queryScalar(array(":pid" => $parent->id));
			if($t == "") {
				$mp_base = $parent->mp;
				$mp_base[] = 1;
			} else {
				$mp_base = explode(",", substr($t, 1, -1));
				$mp_base[count($mp_base) - 1]++;
			}
			if($debug) echo "mp_base = [" . join(",", $mp_base) . "] ('{$t}')<br />";

			if($debug) echo "new tree:<ul>\n";
			$n = count($handle->mp); $i = 0;
			$sql = array(); $params = array();
			$sql[] = "UPDATE catalog SET pid = :pid WHERE id = :id";
			$params[":pid"] = $parent->id;
			$params[":id"] = $handle->id;
			foreach($cats as $cat) {
				$cat->mp = array_merge($mp_base, array_slice($cat->mp, $n));
				$sql[] = "UPDATE catalog SET mp = :mp_{$i} WHERE id = {$cat->id}";
				$params[":mp_{$i}"] = "{" . join(",", $cat->mp) . "}";
				$i++;

				if($debug) echo "<li>{$cat->dump}</li>\n";
			}
			if($debug) echo "</ul>\n\n";

			if(count($sql) > 0) {
				$sql = "BEGIN;\n" . join(";\n", $sql) . ";\nCOMMIT;";

				if($debug) {
					echo "<pre>";
					echo "{$sql}";
					print_r($params);
					echo "</pre>";
				}

				if(!$debug_dry || !$debug) Yii::app()->db->createCommand($sql)->execute($params);
			}

			if($debug) {
				echo "<a href='/moderator/catalog'>Okay</a>";
				exit;
			}
		}
		$this->redirect("/moderator/catalog?edit={$handle->id}");
	}

	public function actionBook_cat() {
		$this->area = "book_cat";

		if(isset($_POST["cat_id"])) {
			$sql = array();
			foreach($_POST["cat_id"] as $book_id => $cat_id) {
				$cat_id = (int) $cat_id;
				$book_id = (int) $book_id;

				if($cat_id == 0) continue;
				if($cat_id != -1) {
					$sql[] = "UPDATE books SET cat_id = {$cat_id} WHERE id = {$book_id};";
				}
				$sql[] = "DELETE FROM moder_book_cat WHERE book_id = {$book_id};";
			}

			if(count($sql) > 0) {
				$sql = "BEGIN;\n" . join("\n", $sql) . "\nCOMMIT;";
				Yii::app()->db->createCommand($sql)->execute();
			}

			$this->redirect("/moderator/book_cat/Book_page/" . (int) $_GET["Book_page"]);
		}

		$f = Book::model()->with("moder_cat");
		$books_dp = new CActiveDataProvider($f, array(
			"criteria" => array("order" => "t.s_title"),
			"pagination" => array("pageSize" => 10),
		));

		$cats = Category::model()->indented_list()->findAll();
		$categories = array();
		$branches = Yii::app()->params["catalog_branches"];
		foreach($cats as $cat) {
			$typ = $branches[$cat->mp[0]];
			$categories[$typ][] = $cat;
		}

		$this->render("book_cat", array("books_dp" => $books_dp, "categories" => $categories));
	}


	public function actionBlogTopic() {
		$post_id = (int) $_POST["post_id"];
		$topic = (int) $_POST["topic"];

		$T = Yii::app()->params["blog_topics"]["common"];
		if(!isset($T[$topic])) {
			echo json_encode(["error" => "Нет такой рубрики ($topic)."]);
		} else {
			Yii::app()->db
				->createCommand("UPDATE blog_posts SET topics = :topic WHERE id = :id")
				->execute([":id" => $post_id, ":topic" => $topic]);

			echo json_encode(["id" => $post_id, "topicHtml" => "<a href='/blog?topics[]=$topic'>{$T[$topic]}</a>"]);
		}

	}
}