<?php
class ReadyCache extends CFileCache {
	public $embedExpiry = false;	// since Yii 1.1.4, migrate and test!!!
	public $fixedExpiry = 604800;	// если embedExpiry == false, то expiry для всех файлов ставится принудительно на эту величину (7 дней)

	/**
	 * @param $key
	 * @return bool|int mtime файла в кеше или false, если файла не существует
	 */
	public function getCacheFileMTime($id) {
		$mtime = @filemtime($this->getCacheFile($this->generateUniqueKey($id)));
		if(!$mtime) return false;
		return $this->embedExpiry ? $mtime : $mtime - $this->fixedExpiry;
	}

	/**
	 * expires у нас теперь фиксированный для всех файлов
	 * @param $id
	 * @param $value
	 * @param int $expire
	 * @param null $dependency
	 * @return mixed
	 */
	public function set($id, $value, $expire = 0, $dependency = null) {
		if($this->embedExpiry) return parent::set($id, $value, $expire, $dependency);
		else return parent::set($id, $value, $this->fixedExpiry, $dependency);
	}
}