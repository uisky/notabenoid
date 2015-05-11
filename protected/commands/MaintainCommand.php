<?php
class MaintainCommand extends CConsoleCommand {

	private $options = array(
		"profile-sql" => true,
	);

	private $profileInfo = array();
	private function profileStart($title) {
		$this->profileInfo = array(
			"title" => $title,
			"startTime" => time(),
		);
	}

	private function profileStop($msg = "") {
		if(count($this->profileInfo) <= 0) echo "profileStop called without profileStart\n";

		$t = time() - $this->profileInfo["startTime"];
		if($t < 60) $tt = "{$t} sec";
		else $tt = sprintf("%02d:%02d", $t / 60, $t % 60);

		echo "{$this->profileInfo["title"]}: {$tt} {$msg}\n";
	}

	private function execSQL($title, $sql) {
		$this->profileStart($title);
		try {
			$n = Yii::app()->db->createCommand($sql)->execute();
		} catch(CDbException $e) {
			echo "SQL ERROR: " . $e->getMessage();
		}
		$this->profileStop($n != 0 ? "affected: {$n}" : "");
	}

	public function actionMidnight() {
		// 1. download_log
		$this->execSQL("download_log", "
			BEGIN;
			UPDATE books SET n_dl_today = 0, n_invites = 30 WHERE n_dl_today != 0 OR n_invites != 30;
			UPDATE chapters SET n_dl_today = 0 WHERE n_dl_today != 0;
			TRUNCATE download_log;
			COMMIT;
		");

		$this->execSQL("remind_tokens", "DELETE FROM remind_tokens WHERE now() - cdate > interval '12 hour'");

		$this->execSQL("ban obsolete", "DELETE FROM ban WHERE until < current_date");
	}

	public function actionDailyFixes() {
		// Укорачиваем search_history
		$this->execSQL("truncate search_history", "DELETE FROM search_history WHERE cdate < now() - interval '7 days'");

		// Считаем глобальные показатели статистики
		$this->profileStart("global counters");
		$global_stat = array(
			"n_users" => Yii::app()->db->createCommand("SELECT reltuples::int FROM pg_class WHERE relname = 'users'")->queryScalar(),
			"n_books" => Yii::app()->db->createCommand("SELECT reltuples::int FROM pg_class WHERE relname = 'books'")->queryScalar(),
			"n_orig"  => Yii::app()->db->createCommand("SELECT reltuples::int FROM pg_class WHERE relname = 'orig'")->queryScalar(),
			"n_tr"    => Yii::app()->db->createCommand("SELECT reltuples::int FROM pg_class WHERE relname = 'translate'")->queryScalar(),
		);
		file_put_contents(YiiBase::getPathOfAlias("application.runtime") . "/global_stat.ser", serialize($global_stat));
		$this->profileStop();

		$this->execSQL("groups & invites clean", "
			DELETE FROM groups WHERE status = 0 AND n_trs = 0;
			DELETE FROM invites WHERE cdate + interval '100 day' <= now();
		");
	}

	public function actionAutotherapy() {
/*
		// translate.(rating, n_marks)
		$this->execSQL("translate.(rating, n_marks)", "
			-- 1 час 10 минут
			BEGIN;
			UPDATE translate SET rating = 0, n_votes = 0 WHERE rating != 0 OR n_votes != 0;
			WITH stats AS (SELECT tr_id, COUNT(*) AS n_votes, SUM(mark) AS rating FROM marks GROUP BY tr_id)
			UPDATE translate SET rating = stats.rating, n_votes = stats.n_votes FROM stats WHERE stats.tr_id = translate.id;
			COMMIT;
		");

		// @todo: Есть ли там автофикс на n_comments? Если нет - повесить, очень долго считается.
		// orig: n_comments, n_trs
		$this->execSQL("orig: (n_comments, n_trs)", "
			BEGIN;
			-- 6:50
			UPDATE orig SET n_comments = 0, n_trs = 0 WHERE n_comments != 0 OR n_trs != 0;
			-- 5:30
			WITH stats AS (SELECT orig_id, COUNT(*) AS cnt FROM comments WHERE orig_id IS NOT NULL AND NOT (user_id IS NULL AND body = '') GROUP BY orig_id)
			UPDATE orig SET n_comments = cnt FROM stats WHERE stats.orig_id = orig.id;
			-- 1:00
			WITH stats AS (SELECT orig_id, COUNT(*) AS n_trs FROM translate GROUP BY orig_id)
			UPDATE orig SET n_trs = stats.n_trs FROM stats WHERE orig.id = stats.orig_id;
			COMMIT;
		");

		// chapters: n_verses, n_vars, d_vars, last_tr
		$this->execSQL("chapters: n_verses, n_vars, d_vars, last_tr", "
			BEGIN;

			-- 2 sec
			UPDATE chapters SET n_verses = 0, n_vars = 0, d_vars = 0 WHERE n_verses != 0 OR n_vars != 0 OR d_vars != 0;

			-- 4:00
			WITH stats AS (SELECT chap_id, COUNT(*) AS cnt FROM orig GROUP BY chap_id)
			UPDATE chapters SET n_verses = cnt FROM stats WHERE stats.chap_id = chapters.id;

			-- 6 sec
			WITH broken AS (
				SELECT
					ch.id,
					ch.n_vars, COUNT(tr.*) as real_n_vars,
					ch.d_vars, COUNT(DISTINCT tr.orig_id) AS real_d_vars,
					ch.last_tr, MAX(tr.cdate) as real_last_tr
				FROM chapters ch
				LEFT JOIN translate tr ON ch.id = tr.chap_id
				GROUP BY ch.id
				HAVING ch.last_tr != MAX(tr.cdate) OR ch.n_vars != COUNT(tr.*) OR ch.d_vars != COUNT(DISTINCT tr.orig_id)
			)
			UPDATE chapters SET
				n_vars = broken.real_n_vars,
				d_vars = broken.real_d_vars,
				last_tr = broken.real_last_tr
			FROM broken WHERE broken.id = chapters.id;

			COMMIT;
		");

		// books: n_chapters, n_verses, n_vars, d_vars, last_tr
		$this->execSQL("books: n_chapters, n_verses, n_vars, d_vars, last_tr", "
			BEGIN;
			UPDATE books SET n_chapters = 0, n_verses = 0, n_vars = 0, d_vars = 0, last_tr = NULL;
			WITH stats AS (SELECT book_id, COUNT(*) cnt, SUM(n_verses) nv, SUM(n_vars) n, SUM(d_vars) d, MAX(last_tr) AS last_tr FROM chapters GROUP BY book_id)
			UPDATE books SET n_chapters = cnt, n_verses = nv, n_vars = n, d_vars = d, last_tr = stats.last_tr FROM stats WHERE stats.book_id = books.id;
			COMMIT;
		");

		// blog_posts: n_comments
		$this->execSQL("blog_posts: n_comments, lastcomment", "
			BEGIN;
			UPDATE blog_posts SET n_comments = 0;
			WITH stats AS (SELECT post_id, COUNT(*) AS cnt, MAX(cdate) as lastcomment FROM comments WHERE post_id IS NOT NULL AND NOT (user_id IS NULL AND body = '') GROUP BY post_id)
			UPDATE blog_posts SET n_comments = stats.cnt, lastcomment = stats.lastcomment FROM stats WHERE stats.post_id = blog_posts.id;
			COMMIT;
		");

		// groups
		$this->execSQL("groups autotherapy", "
			BEGIN;
			UPDATE groups SET n_trs = 0, rating = 0;
			WITH stats AS (SELECT book_id, user_id, COUNT(*) as cnt, SUM(rating) as sum FROM translate GROUP BY book_id, user_id)
			UPDATE groups SET n_trs = cnt, rating = sum FROM stats WHERE stats.book_id = groups.book_id AND stats.user_id = groups.user_id;
			COMMIT;
		");
*/

/*
		// users: n_comments, n_trs, rate_t, n_karma, rate_u
		$this->execSQL("users: n_comments, n_trs, rate_t, n_karma, rate_u", "
			BEGIN;

			UPDATE users SET n_comments = 0, n_trs = 0, n_karma = 0, rate_u = 0;

			WITH stats AS (SELECT user_id, COUNT(*) as cnt FROM comments WHERE user_id IS NOT NULL GROUP BY user_id)
			UPDATE users SET n_comments = cnt FROM stats WHERE stats.user_id = users.id;

			WITH stats AS (SELECT user_id, COUNT(*) as cnt, SUM(rating) sum FROM translate GROUP BY user_id)
			UPDATE users SET n_trs = cnt, rate_t = sum FROM stats WHERE stats.user_id = users.id;

			WITH stats AS (SELECT to_uid, COUNT(*) cnt, SUM(mark) sum FROM karma_rates GROUP BY to_uid)
			UPDATE users SET n_karma = cnt, rate_u = sum FROM stats WHERE stats.to_uid = users.id;

			COMMIT;
		");
*/
	}

	public function actionSwitchStats() {
		$users = array();
		$fh = fopen(Yii::app()->basePath . "/runtime/higgs.log", "r");
		while(!feof($fh)) {
			$l = fgets($fh);
			if($l == "") continue;

			$a = explode("\t", $l);
			$a[2] = (int) $a[2];
			$users[$a[1]] = $a[2];
		}
		fclose($fh);

		$states = array(0 => 0, 1 => 0);
		foreach($users as $u => $state) {
			$states[$state]++;
		}

		$n = count($users);
		printf("Попробовали новый интерфейс: %d\nОстались: %d (%d%%)\nВернулись к старому: %d (%d%%)\n",
			$n,
			$states[1], $states[1] / $n * 100,
			$states[0], $states[0] / $n * 100
		);

	}

	/**
	 * Запускалось один раз после миграции с 2.0
	 * Лучше больше не повторять.
	 */
	public function NEVERFUCKINGRUNTHIS_actionSeen_fix() {
/*
-- Старая попытка автофикса:
UPDATE seen
SET n_comments = (SELECT COUNT(*) FROM comments c WHERE c.post_id = seen.post_id AND c.cdate <= seen.seen AND c.user_id != seen.user_id AND NOT (c.user_id IS NULL AND c.body = ''))
WHERE post_id IS NOT NULL;

-- Показать глюки в постах
SELECT
	s.user_id, s.post_id,
	s.seen, p.lastcomment, p.lastcomment - s.seen,
	s.n_comments, p.n_comments, p.n_comments - s.n_comments
FROM seen s LEFT JOIN blog_posts p ON s.post_id = p.id
WHERE
	s.n_comments > p.n_comments
	AND s.track AND s.post_id IS NOT NULL
ORDER BY s.user_id;
*/

		echo "Fixing seen for blog posts. Getting stats...\n";
		$seen = Yii::app()->db->createCommand("SELECT user_id, post_id, seen, n_comments FROM seen WHERE track ORDER BY user_id")->queryAll();
		$n = count($seen);
		echo "{$n} tracked seen records\n";
		$n_fixed = 0;
		foreach($seen as $s) {
			$seen_comments = Yii::app()->db->createCommand("
				SELECT COUNT(*) as n_comments
				FROM comments
				WHERE
					NOT (user_id IS NULL AND body = '') AND post_id IS NOT NULL
					AND post_id = :post_id
					AND (cdate <= :seen OR user_id = :user_id)
			")->queryScalar(array(":post_id" => $s["post_id"], ":user_id" => $s["user_id"], ":seen" => $s["seen"]));

			if($seen_comments == $s["n_comments"]) continue;

			echo "{$s["user_id"]}:{$s["post_id"]}:\t{$s["seen"]}, {$s["n_comments"]}. Really had seen {$seen_comments}     \r";

			Yii::app()->db->createCommand("UPDATE seen SET n_comments = :n_comments WHERE user_id = :user_id AND post_id = :post_id")
				->execute(array(":user_id" => $s["user_id"], ":post_id" => $s["post_id"], ":n_comments" => $seen_comments));

			$n_fixed++;
		}
		printf("Fixed records: %d (%d%%)                                                                                    \n", $n_fixed, ($n_fixed / $n) * 100);


		$n_fixed = 0; $n_updated = 0; $n_inserted = 0;
		// Добавляем в seen те фрагменты оригинала, где юзеры оставляли комментарии
		echo "Fixing seen for origs. Getting stats...\n";
		$origs = Yii::app()->db->createCommand("
			SELECT
				c.user_id, c.orig_id, MAX(c.cdate) AS lastcomment
			FROM comments c
			WHERE c.orig_id IS NOT NULL AND c.user_id IS NOT NULL
			GROUP BY c.user_id, c.orig_id
			ORDER BY c.orig_id;
		")->queryAll();
		$n = count($origs);
		echo "{$n} user:orig pairs\n";
		foreach($origs as $o) {
			$seen_comments = Yii::app()->db->createCommand("
				SELECT COUNT(*) AS n_comments
				FROM comments
				WHERE
					NOT (user_id IS NULL AND body = '')
					AND orig_id = :orig_id
					AND (cdate <= :lastcomment OR user_id = :user_id)
			")->queryScalar(array(":user_id" => $o["user_id"], ":orig_id" => $o["orig_id"], ":lastcomment" => $o["lastcomment"]));

			// echo "{$o["user_id"]}\t{$o["orig_id"]}\t{$seen_comments}\n";
			$a = Yii::app()->db->createCommand("UPDATE seen SET track = true, n_comments = :n_comments WHERE user_id = :user_id AND orig_id = :orig_id")
				->execute(array(":user_id" => $o["user_id"], ":orig_id" => $o["orig_id"], ":n_comments" => $seen_comments));
			if($a == 0) {
				$n_inserted++;
			} else {
				$n_updated++;
			}

			if($n_fixed % 1000 == 0) printf("Fixed records: %d (%d%%), INS: %d, UPD: %d\r", $n_fixed, ($n_fixed / $n) * 100, $n_inserted, $n_updated);
			$n_fixed++;
		}
		printf("DONE: Fixed records: %d (%d%%), INS: {$n_inserted}, UPD: {$n_updated}\n", $n_fixed, ($n_fixed / $n) * 100);

	}
}