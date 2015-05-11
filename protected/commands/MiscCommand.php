<?php
class MiscCommand extends CConsoleCommand {
	public function actionInviteTop($type, $limit = 100, $giveinvites = 0, $dry = false, $delay = 5) {
		$admin = User::model()->findByPk(1);

		$crt = new CDbCriteria([
			"condition" => "t.can & 1::bit(16) = 0::bit(16)",
			"limit" => (int) $limit,
		]);

		if($type == "karma") {
			$crt->order = "rate_u desc";
		} elseif($type == "rating") {
			$crt->order = "rate_t desc";
		} elseif($type == "translations") {
			$crt->order = "n_trs desc";
		} elseif($type == "owners") {
			$crt->join = "RIGHT JOIN books b ON b.owner_id = t.id";
			$crt->addCondition("b.n_verses > 10 and b.last_tr > '2014-08-08' and b.last_tr <= '2015-01-01'");
			$crt->order = "n_trs desc";
		} else {
			echo "--type=karma|rating|owners|translations\n";
		}

		$crt->join .= "\nLEFT JOIN reg_invites invites ON t.id = invites.to_id";
		$crt->addCondition("invites.to_id IS NULL");

		$users = User::model()->findAll($crt);
		if(count($users) == 0) {
			echo "Нет кандидатов для приглашения\n";
			return;
		}

		$cnt = 0;
		foreach($users as $user) {
			printf("Приглашается %d / %d %16s karma:%3d rating:%5d ntrs:%5d ...%s", ++$cnt, count($users), $user->login, $user->rate_u, $user->rate_t, $user->n_trs, str_repeat(" ", 16));

			$user->n_invites = $giveinvites;
			$user->save(false, ["n_invites"]);

			$invite = RegInvite::gen($admin);
			$invite->to_id = $user->id;
			$invite->to_email = $user->email;
			if($giveinvites > 0) {
				$invite->message = <<<TTT
Вы можете пригласить сюда ещё {$giveinvites} человек. Для этого после восстановления членства, зайдите в свой профиль на
вкладку «Приглашения».";
TTT;
			}

			if($dry) {
				echo "\n";
			} else {
				try {
					$invite->sendMail();
				} catch(Exception $e) {
					echo "ERROR SENDING to {$invite->to_email}: " . $e->getMessage() . "\n";
				}

				if(!$invite->save(false)) echo "ERROR SAVE\n" . print_r($invite->errors, true) . "\n";
				sleep($delay);
				echo "\r";
			}
		}
		echo "\n";
	}
}