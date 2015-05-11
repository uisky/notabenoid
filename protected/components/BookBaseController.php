<?php
class BookBaseController extends Controller {
	/** @return Book */
	protected function loadBook($book_id, $class = "Book") {
		$book_id = (int) $book_id;
		
		if($this->book === null) {
			$this->book = $class::model()->with("owner", "membership", "cat")->findByPk($book_id);
		}
		if(!$this->book) throw new CHttpException(404, "Такого перевода не существует. Возможно, он удалён или вы неправильно набрали адрес. Попробуйте воспользоваться <a href='/search'>поиском</a>, например.");

		if($this->book->typ == "P") {
			throw new CHttpException(410, "Извините, раздел перевода фраз временно отключен для переосмысления. Следите за <a href='/blog?topic=64'>нашим блогом</a>, если хотите первыми узнать, когда он снова будет запущен.");
		}

		if($this->book->opts_get(Book::OPTS_BAN_COPYRIGHT)) {
			$this->book->facecontrol = Book::FC_INVITE;
			foreach(Yii::app()->params["ac_areas"] as $ac => $title) {
				if($this->book->$ac == "a") $this->book->$ac = "g";
			}

			$reason = BookBanReason::model()->findByPk($this->book->id);
			if(!$reason) $reason = new BookBanReason();

			if(!$this->book->can("read")) {
				$html = "Сожалеем, но этот перевод заблокирован по заявке правообладателя";
				if($reason->url != "") $html .= " <a href='{$reason->url}' rel='nofollow'>{$reason->title}</a>";
				elseif($reason->title != "") $html .= " {$reason->title}";
				if($reason->email != "") $html .= " (<a href='mailto:{$reason->email}'>{$reason->email}</a>)";
				$html .= ".<br /><br />";
				$html .= "<img src='http://img.leprosorium.com/2182718' style='display: block; margin: 20px auto' />";
				throw new CHttpException(403, $html);
			}
		}

		// Формируем понятное сообщение об ошибке, если нам нельзя в этот перевод (!$this->book->can("read"))
		if(!$this->book->can("read")) {
			$msg = $this->book->deniedWhy;

			// Bells & Whistles, показываются только на странице с ошибкой, а не при ajax-запросе
			if(!Yii::app()->request->isAjaxRequest) {

				// Ебала с группами, предлагаем вступить или проверяем, есть ли инвайт
				if($this->book->membership->status != GroupMember::BANNED) {
					if($this->book->ac_read == "g") {
						if($this->book->facecontrol == Book::FC_CONFIRM) {
							$msg .= $this->renderPartial("//book/_join", array("book" => $this->book), true);
						} elseif($this->book->facecontrol == Book::FC_INVITE) {
							if($this->book->user_invited(Yii::app()->user->id)) {
								$msg .= "<br /><br />И, кстати, это приглашение у вас есть.<br /><br />" .
									"<a href='" . $this->book->getUrl("invite_accept")  . "' class='btn btn-success'><i class='icon-ok icon-white'></i> Принять</a> " .
									"<a href='" . $this->book->getUrl("invite_decline") . "' class='btn btn-inverse'><i class='icon-remove-sign icon-white'></i> Отказать</a>";
							}
						}
					}
				}

				$msg .= "<br /><br /><a href='/search?t=" . urlencode($this->book->s_title) . "'>Поискать похожие переводы</a> | ";
				$msg .= "<a href='" . $this->book->owner->getUrl("books") . "'>Другие переводы от {$this->book->owner->login}</a> | ";
				if(!Yii::app()->user->isGuest) $msg .= "<a href='/mail/write?to=" . urlencode($this->book->owner->login) . "'>Написать письмо {$this->book->owner->login}</a> ";
			}

			throw new CHttpException(403, $msg);
		}

		return $this->book;
	}
}