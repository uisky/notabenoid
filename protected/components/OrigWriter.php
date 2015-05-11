<?php
class OrigWriter {
	private $sql = "";
	private $sql_params = array();
	private $cnt = 0;
	public $pagesize = 400;

	public function __construct() {
	}

	public function push($orig) {
		if($this->sql == "") $this->sql = "INSERT INTO orig (chap_id, ord, t1, t2, body) VALUES ";
		else $this->sql .= ",\n";

		$this->sql .= "({$orig->chap_id}, :ord{$this->cnt}, :t1{$this->cnt}, :t2{$this->cnt}, :body{$this->cnt})";
		$this->sql_params[":ord{$this->cnt}"] = $orig->ord;
		$this->sql_params[":t1{$this->cnt}"] = $orig->t1;
		$this->sql_params[":t2{$this->cnt}"] = $orig->t2;
		$this->sql_params[":body{$this->cnt}"] = $orig->body;

		$this->cnt++;
		if($this->cnt >= $this->pagesize) $this->flush();

		return true;
	}

	public function flush() {
		if($this->sql == "") return false;

//		echo "<pre><b>EXECUTE SQL:</b>\n{$this->sql}\n<b>PARAMS:</b> " . print_r($this->sql_params, true) . "</pre>"; exit;

		Yii::app()->db->createCommand($this->sql)->execute($this->sql_params);

		$this->sql = "";
		$this->sql_params = array();
		$this->cnt = 0;

		return true;
	}
}
