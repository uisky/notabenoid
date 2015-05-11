<?php

class SearchController extends Controller {
//	public $layout = "//layouts/column1";

	public function actionIndex() {
		$filter = new SearchFilter("search");

		// Критерий с условиями поиска
		$C = new CDbCriteria();
		$C->with = array("owner", "cat");

		$filter->setAttributes($_GET, true);
		if($filter->validate()) {
			if($_GET["from"] == "header" && mb_substr($filter->t, 0, 1) == "@") {
				$this->redirect("/users/go?login=" . mb_substr($filter->t, 1));
			}

			$filter->t = str_replace("/", "", $filter->t);
			if($filter->t != "") {
				$words = explode(" ", $filter->t);
				$words_cnt = 0;
				foreach($words as $i => $w) {
					$w = trim($w);
					if(mb_strlen($w) <= 2) continue;
					$param = "word_" . $i;
					$w = pg_escape_string($w);
					$C->addCondition("s_title ILIKE :{$param} OR t_title ILIKE :{$param}");
					$C->params[$param] = '%' . $w . '%';

					$words_cnt++;
				}
				if($words_cnt == 0) $filter->t = "";
			}

			if($filter->cat) {
				$n = count($filter->category->mp);
				$C->addCondition("cat.mp[1:{$n}] = '{" . join(",", $filter->category->mp) . "}'");
			}

			if($filter->s_lang != 0) $C->addCondition("s_lang = {$filter->s_lang}");
			if($filter->t_lang != 0) $C->addCondition("t_lang = {$filter->t_lang}");

			if($filter->ready) $C->addCondition("t.n_verses != 0 AND t.d_vars >= t.n_verses");

			if($filter->gen) $C->addCondition("t.ac_read = 'a' AND t.ac_gen = 'a'");
			if($filter->tr) $C->addCondition("ac_tr = 'a'");
		}

		$C->order = SearchFilter::$sortSQL[$filter->sort];

		$dp = new CActiveDataProvider(Book::model()->with("owner"), array(
			"criteria" => $C,
			"pagination" => array("pageSize" => 50),
		));

		if($filter->doSearch) {
			$dp->totalItemCount = Yii::app()->db
				->createCommand("SELECT COUNT(*) FROM books t LEFT JOIN catalog cat ON t.cat_id = cat.id WHERE {$C->condition}")
				->queryScalar($C->params);

			// Пишем в логи
			if($dp->totalItemCount > 0 && $_GET["from"] != "stop" && $filter->t != "") {
				Yii::app()->db->createCommand("INSERT INTO search_history (ip, request) VALUES (:ip, :request)")
					->execute(array(":ip" => $_SERVER["HTTP_X_REAL_IP"] ? $_SERVER["HTTP_X_REAL_IP"] : $_SERVER["REMOTE_ADDR"], ":request" => $filter->t));
			}
		}

		$this->side_view = array("index_side" => array("filter" => $filter));
		$this->render("index", array("filter" => $filter, "dp" => $dp));
	}

}