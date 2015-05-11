<?php
class UploadedImage extends CComponent {
	public $area = "", $slot = 0, $subslot = 0, $seed = 0, $w = 0, $h = 0;
	public $fileExtension = ".jpg";

	// data - массив или строка {slot,subslot,seed,w,h}
	public function __construct($area = "heap", $data = null) {
		$this->area = $area;

		if($data !== null) {
			if(!is_array($data)) $data = explode(",", substr($data, 1, -1));

			list($this->slot, $this->subslot, $this->seed, $this->w, $this->h) = $data;
		}
	}


	/** @param CUploadedFile $file **/
	public function upload($file, $resize_w = null, $resize_h = null) {
		$this->slot = date("Y") - 2000;
		$this->subslot = (int) date("m");
		$this->checkDir();
		$this->generateSeed();

		$R = new ImgResizer();
		list($this->w, $this->h) = $R->resize($file->tempName, $this->path, $resize_w, $resize_h);

		// 50x50 thumbnail
		list($th_w, $th_h) = $R->resize($file->tempName, $this->getPath("5050"), 50, 50);
		return true;
	}

	public function getExists() {
		return $this->slot != 0 && $this->subslot != 0 && $this->seed != 0;
	}

	public function getDir() {
		if($this->slot == 0 || $this->subslot == 0) return false;

		return $_SERVER["DOCUMENT_ROOT"] . "/i/{$this->area}/{$this->slot}/{$this->subslot}";
	}

	public function getPath($modification = null) {
		if($modification !== null) $modification = "-{$modification}";
		return $this->dir . "/" . $this->seed . $modification . $this->fileExtension;
	}

	public function getUrl($modification = null) {
		if($modification !== null) $modification = "-{$modification}";
		return "/i/{$this->area}/{$this->slot}/{$this->subslot}/{$this->seed}{$modification}{$this->fileExtension}";
	}

	public function getTag($modification = null) {
		return "<img src='" . $this->getUrl($modification) . "' " . ($modification === null ? "width='{$this->w}' height='{$this->h}' " : "") . "alt='' />";
	}

	protected function generateSeed() {
		$cnt = 0;
		do {
			$this->seed = mt_rand(1, 32000);
		} while(is_file($this->path) && $cnt++ < 50);

		if($cnt >= 50) {
			throw new CHttpException(500, "System Error: storage full: {$this}");
		}

		return true;
	}

	protected function checkDir() {
		$d = explode("/", $this->dir);

		for($i = -1; $i <= 0; $i++) {
			$dir = join("/", array_slice($d, 0, $i == 0 ? null : $i));
			if(!is_dir($dir)) mkdir($dir, 0755);
		}
	}

	public function delete() {
		if(!$this->exists) return;

		@unlink($this->path);
		@unlink($this->getPath("5050"));
		$this->slot = $this->subslot = $this->seed = $this->w = $this->h = 0;
	}

	public function __toString() {
		$r = "{";
		foreach(array("slot", "subslot", "seed", "w", "h") as $k) {
			if($r != "{") $r .= ",";
			$r .=  intval($this->$k);
		}
		$r .= "}";
		return $r;
	}
}
?>