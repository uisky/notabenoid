<?php
class CatalogController extends Controller {
	public function actionIndex($cat_id = 0) {
		$cat_id = (int) $cat_id;
		if($cat_id) {
			$cat = Category::model()->findByPk((int) $cat_id);
			$branch = $cat->mp;
		} else {
			$cat = $branch = null;
		}

		$tree = Category::model()->tree($branch)->with("booksCount")->findAll();

		if($cat) {
			$n = count($cat->mp);
			$books_dp = new CActiveDataProvider(Book::model()->with("cat"), array(
				"criteria" => array (
					"condition" => "cat.mp[1:{$n}] = '{$cat->mpPacked}'",
					"order" => "t.s_title",
				),
				"pagination" => array("pageSize" => 50)
			));
		} else {
			$books_dp = null;
		}

		if($_GET["ajax"]) $this->renderPartial("catalog_ajax", array("tree" => $tree));
		else {
			$this->render("catalog", array("cat" => $cat, "tree" => $tree, "books_dp" => $books_dp));
		}
	}

}
