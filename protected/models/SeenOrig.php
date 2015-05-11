<?php
class SeenOrig extends CActiveRecord {
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}
	public function tableName() {
		return 'seen';
	}
	public function primaryKey() {
		return array('user_id', 'orig_id');
	}

	public $user_id, $post_id, $orig_id;
	public $seen, $n_comments, $track, $n_new_comments;
}
?>
