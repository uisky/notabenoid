<?php
class MigrateCommand extends CConsoleCommand {
	public $migrations = array(
		"languages" => array(),
	);

	public function pg($sql) {
		return Yii::app()->db_pg->createCommand($sql)->execute();
	}

	public function array2insert($res) {
		$fields = ""; $values = "";
		foreach($res as $k => $v) {
			if($fields != "") $fields .= ", ";
			$fields .= "{$k}";

			if($values != "") $values .= ", ";
			$values .= ":{$k}";

			$params[":{$k}"] = $v;
		}

		return array($fields, $values, $params);
	}

	public function actionIndex() {
		echo "Доступные миграции:\n  - " . join("\n  - ", array_keys($this->migrations)) . "\n";
	}

	public function actionLanguages() {
		echo "Migrating languages\n";

		$this->pg("
			DROP TABLE IF EXISTS languages CASCADE;
			CREATE TABLE languages (
			  id            SERIAL PRIMARY KEY,
			  typ           smallint NOT NULL,
			  title         varchar(100) not null,
			  title_r       varchar(100) not null
			);
		");

		$r = Yii::app()->db_ms->createCommand("SELECT * FROM languages")->query();
		foreach($r as $res) {
			list($fields, $values, $params) = $this->array2insert($res);
			Yii::app()->db_pg->createCommand("INSERT INTO languages ({$fields}) VALUES ({$values})")->execute($params);
		}

		$this->pg("
			CREATE INDEX languages_typ_idx ON languages (typ);
			SELECT setval('languages_id_seq', max(id)) FROM languages;
		");
	}

	public function actionUsers() {
		echo "Migrating users\n";

		$this->pg("
			DROP TABLE IF EXISTS users CASCADE;
			CREATE TABLE users (
				id           serial,
				cdate        timestamp with time zone not null default now(),
				lastseen     timestamp with time zone not null default now(),

				can          bit(16) not null default b'0000011110011111',

				login        varchar(16) not null CHECK(login != ''),
				pass         varchar(32) not null CHECK(pass != ''),
				email        varchar(255) not null /* CHECK(email != '') -- есть старые юзеры без мыла */,
				sex          char not null default 'x' CHECK(sex IN ('x', 'm', 'f')),
				lang         smallint not null,
				upic         smallint[3],

				ini          bit(16) not null default b'0000000011100011',

				rate_t       int NOT NULL default 0,
				rate_c       int NOT NULL default 0,
				rate_u       smallint NOT NULL default 0,

				n_trs        int NOT NULL default 0,
				n_comments   int NOT NULL default 0
			);
		");

		$r = Yii::app()->db_ms->createCommand("SELECT * FROM users")->query();
		$nItems = $r->count();
		echo "Записей в mysql: {$nItems}\n";
		foreach($r as $cnt => $res) {
			/**
				@TODO: перенести юзерпики из ../../notabenoid.com/www/i/upic, переименовать их так, чтобы upic[0] был smallint, upic[1:2] = [witdh:height] большой картинки.
				большие картинки - /i/upic/$N/$id-$seed.jpg, маленькие - /i/upic/$N/$id-seed_th.jpg
			**/
			$upic = array(0, 0, 0);

			$row = array(
				"id" => $res["id"],
				"cdate" => $res["cdate"],
				"lastseen" => $res["lastlogin"] == 0 ? $res["cdate"] : $res["lastlogin"],
				"can" => sprintf("%016b", $res["act_code"] == 100 ? 0 : ($res["moderator"] ? 34575 : 1807)),
				"login" => $res["login"],
				"pass" => $res["pass"],
				"email" => $res["email"],
				"sex" => $res["sex"] == "" ? "x" : $res["sex"],
				"lang" => $res["lang"],
				"upic" => "{" . join(", ", $upic) . "}",
				"ini" => sprintf("%016b", $res["ini"]),
				"rate_t" => $res["rate_t"],
				"rate_c" => $res["rate_c"],
				"rate_u" => $res["rate_u"],
				"n_trs" => $res["ntrs"] > 2000000000 ? 0 : $res["ntrs"],
				"n_comments" => $res["ncomments"] > 2000000000 ? 0 : $res["ncomments"]
			);
			list($fields, $values, $params) = $this->array2insert($row);
			Yii::app()->db_pg->createCommand("INSERT INTO users ({$fields}) VALUES ({$values})")->execute($params);

			if($cnt % 100 == 0) echo "{$cnt} / {$nItems}\r";
		}
		echo "{$cnt} / {$nItems}\n";

		$this->pg("
			ALTER TABLE users ADD PRIMARY KEY(id);
			SELECT setval('users_id_seq', max(id)) FROM users;
			CREATE UNIQUE INDEX users_login_idx ON users ((lower(login)));
		");
	}

	public function actionOrig() {
		echo "Migrating orig\n";

		$this->pg("
			DROP TABLE orig IF EXISTS;
			CREATE TABLE orig (
				id          SERIAL,
				chap_id     int not null,

				ord int,
				t1 time(3),
				t2 time(3),

				body        text not null default '',
				n_comments  smallint not null default 0,
				old_id      int
			);
		");

		// Step 1. Копируем данные из book
		$r = Yii::app()->db_ms->createCommand("
			SELECT b.*
			FROM book b
				CROSS JOIN chapters c ON b.chap_id = c.id
				CROSS JOIN books bb ON c.book_id = bb.id
			WHERE bb.typ = 'A'
		");
		foreach($r as $res) {
			$row = array(
				"chap_id" => $res["chap_id"],
				"body" => $res["body"],
				"n_comments" => $res["nnotes"],
				"ord" => $res["id"],
				"old_id" => $res["id"],
			);
			list($fields, $values, $params) = $this->array2insert($row);
			Yii::app()->db_pg->createCommand("INSERT INTO orig ({$fields}) VALUES ({$values})")->execute($params);
		}

	}
}
?>
