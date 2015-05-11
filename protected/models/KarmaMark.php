<?php
class KarmaMark extends CActiveRecord {
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function tableName() {
		return "karma_rates";
	}
	public function primaryKey() {
		return array("to_uid", "from_uid");
	}

	public $dat, $from_uid, $to_uid, $mark = 0, $note;

	public function attributeLabels() {
		return array(
			"mark" => "Оценка",
			"note" => "Комментарий"
		);
	}

	public function rules() {
		return array(
			array("mark", "in", "range" => array(-1, 0, 1)),
			array("note", "clean"),
		);
	}

	public function clean($attr, $params) {
		$this->$attr = trim(htmlspecialchars(strip_tags($this->$attr, ENT_QUOTES | ENT_HTML5)));
	}

	public function relations() {
		return array(
			"from" => array(self::BELONGS_TO, "User", "from_uid"),
			"to" => array(self::BELONGS_TO, "User", "to_uid"),
		);
	}

	public function to_user($user_id) {
		$this->getDbCriteria()->mergeWith(array(
			"condition" => "t.to_uid = " . intval($user_id)
		));

		return $this;
	}

	public function from_user($user_id) {
		$this->getDbCriteria()->mergeWith(array(
			"condition" => "t.from_uid = " . intval($user_id)
		));

		return $this;
	}

	protected function beforeSave() {
		if($this->mark == 0) {
			if(!$this->isNewRecord)	$this->delete();
			else return false;
		}

		return true;
	}

	protected function afterSave() {
		// Инкрементируем users.n_karma
		if($this->mark != 0) {
			if($this->isNewRecord) {
				Yii::app()->db->createCommand("UPDATE users SET n_karma = n_karma + 1, rate_u = rate_u + :mark WHERE id = :to_uid")->execute(array(":to_uid" => $this->to_uid, ":mark" => $this->mark));
			} else {
				// Пересчитываем users.rate_i
				Yii::app()->db->createCommand("UPDATE users SET rate_u = COALESCE((SELECT SUM(mark) FROM karma_rates WHERE to_uid = :to_uid), 0)::int WHERE id = :to_uid")->execute(array(":to_uid" => $this->to_uid));
			}
		}
	}

	protected function afterDelete() {
		Yii::app()->db->createCommand("
			UPDATE users
			SET
				n_karma = subquery.n_karma,
				rate_u  = subquery.rate_u
			FROM (
				SELECT COUNT(*) as n_karma, COALESCE(SUM(mark), 0) as rate_u
				FROM karma_rates WHERE to_uid = :to_uid
			) AS subquery
			WHERE users.id = :to_uid
		")->execute(array(":to_uid" => $this->to_uid));
	}
}
?>
