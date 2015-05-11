<?php
/**
 * @property integer $id
 * @property integer $user_id
 * @property string  $cdate
 * @property boolean $seen
 * @property integer $typ
 * @property string  $msg
 */
class Notice extends CActiveRecord {
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function tableName() {
		return "notices";
	}

	const INVITE = 1;
	const JOIN_ACCEPTED = 2;
	const JOIN_DENIED = 3;
	const EXPELLED = 4;
	const JOIN_REQUEST = 5;
	const BANNED = 6;
	const UNBANNED = 7;
	const CROWNED = 8;
	const DEPOSED = 9;
	const CHAPTER_ADDED = 10;
	const CHAPTER_STATUS = 11;

	public function user($user_id, $new_only = true) {
		$c = $this->getDbCriteria();
		$c->mergeWith(array(
			"condition" => "t.user_id = " . intval($user_id),
			"order" => "t.cdate desc"
		));
		if($new_only) $c->addCondition("seen = false");

		return $this;
	}

	public function render() {
		$m = explode("\n", $this->msg);
		if($this->typ == self::INVITE) {
			return "<a href='/users/{$m[2]}' class='user'>{$m[3]}</a> приглашает вас в закрытую группу перевода <a href='/book/{$m[0]}'>{$m[1]}</a>.<br /><a href='/book/{$m[0]}/invite_accept'>Принять приглашение </a> или <a href='/book/{$m[0]}/invite_decline'>Отказаться</a>?";
		} elseif($this->typ == self::JOIN_ACCEPTED) {
			return "Ваша заявка на участие в группе перевода <a href='/book/{$m[0]}'>{$m[1]}</a> рассмотрена и одобрена.";
		} elseif($this->typ == self::JOIN_DENIED) {
			return "Ваша заявка на участие в группе перевода <a href='/book/{$m[0]}'>{$m[1]}</a> рассмотрена и, увы, отклонена.";
		} elseif($this->typ == self::EXPELLED) {
			return "Вас исключили из группы перевода <a href='/book/{$m[0]}'>{$m[1]}</a>.";
		} elseif($this->typ == self::JOIN_REQUEST) {
			return "<a href='/users/{$m[2]}' class='user'>{$m[3]}</a> хочет вступить в группу перевода <a href='/book/{$m[0]}'>{$m[1]}</a>. Вы - модератор этого перевода и можете принять решение на странице <a href='/book/{$m[0]}/members'>участников перевода</a>.";
		} elseif($this->typ == self::BANNED) {
			return "За какие-то пригрешения, модераторы или владелец перевода забанили вас в группе перевода <a href='/book/{$m[0]}'>{$m[1]}</a>. Вы больше не сможете войти в этот перевод до тех пор, пока они не отменят своего, возможно, опрометчивого решения.";
		} elseif($this->typ == self::UNBANNED) {
			return "Вы были забанены в группе перевода <a href='/book/{$m[0]}'>{$m[1]}</a>, но сегодня владелец перевода или модераторы сменили гнев на милость и разбанили вас.";
		} elseif($this->typ == self::CROWNED) {
			return "Владелец перевода <a href='/book/{$m[0]}'>{$m[1]}</a> назначил вас модератором.";
		} elseif($this->typ == self::DEPOSED) {
			return "Владелец перевода <a href='/book/{$m[0]}'>{$m[1]}</a> лишил вас модераторских полномочий. Негодуем вместе с вами.";
		} elseif($this->typ == self::CHAPTER_ADDED) {
			return "Вы поставили закладку на перевод <a href='/book/{$m[0]}'>{$m[1]}</a> и пожелали следить за изменениями в нём. Так вот, туда только что добавили новую главу \"{$m[3]}\"";
		} elseif($this->typ == self::CHAPTER_STATUS) {
			return "Вы поставили закладку на перевод <a href='/book/{$m[0]}'>{$m[1]}</a> и пожелали следить за изменениями в нём. Так вот, статус главы \"{$m[3]}\" изменился на \"{$m[4]}\"";
		} else {
			return $this->msg;
		}
	}
}
?>
