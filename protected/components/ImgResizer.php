<?php
	/**
	 *	Уменьшает картинку из $src_file, вписывая его в прямоугольник $max_w x $max_h и записывает в $dst_file. Формат - jpg
	 *	Возвращает массив (ширина, высота) получившегося файла или false в случае ошибки.
	 *
	 *	Если max_w или max_h == false, то изображение тупо копируется
	 *
	 *	$WM - ассоциативный массив:
	 *		img: путь к PNG-файлу, который будет припечатан в правый нижний угол фотки
	 *		w, h: ширина и высота PNG-шника. Если == 0, то вычисляются сами
	 *		x, y: координаты, где поставить картинку. Отрицательные значения - считать от правого или нижнего края
	 *
	 *	$exact - если true, то вырезает точный прямоугольник $max_w x $max_h, обрезая лишнее
	 *
	 *	@return list($width, $height)
	*/
	class ImgResizer {
		public $ErrorCode = 0;

		public function resize($src_file, $dst_file, $max_w, $max_h, $WM = null, $exact = false) {
			list($ow, $oh, $otyp) = @getimagesize($src_file);
			if($otyp < 1 or $otyp > 3) {
				if($this) $this->ErrorCode = 1 . " - $otyp";
				return false;
			}

			if($max_w == false) $max_w = $ow;
			if($max_h == false) $max_h = $oh;

			/* Вычисляем результирующие размеры: $srcx, $srcy, $w = $dstw, $h = $dsth */
			if($exact) {
				$w = $max_w;
				$h = $max_h;
				if($oh > $ow) {
					$Ratio = $ow / $w;
					$srcw = $ow;
					$srch = $h * $Ratio;
					$srcx = 0;
					$srcy = ($oh - $srch) / 2;
				} else {
					$Ratio = $oh / $h;
					$srcw = $w * $Ratio;
					$srch = $oh;
					$srcx = ($ow - $srcw) / 2;
					$srcy = 0;
				}
				$srcx = (int) $srcx;
				$srcy = (int) $srcy;
			} else {
				$srcx = 0;
				$srcy = 0;
				$srcw = $ow;
				$srch = $oh;
				if($ow > $max_w) {
					$w = $max_w;
					$h = $oh / ($ow / $max_w);
				} else {
					$w = $ow;
					$h = $oh;
				}
				if($h > $max_h) {
					$w = $w / ($h / $max_h);
					$h = $max_h;
				}
				$w = (int) $w; $h = (int) $h;
			}

			if($otyp == 1) $src = @ImageCreateFromGIF($src_file);
			else if($otyp == 2) $src = @ImageCreateFromJPEG($src_file);
			else if($otyp == 3) $src = @ImageCreateFromPNG($src_file);
			if(!$src) {
				$this->ErrorCode = 2;
				return false;
			}

			$dst = ImageCreateTrueColor($w, $h);
			if(!$dst) {
				$this->ErrorCode = 3;
				return false;
			}

//			echo "ImageCopyResampled($dst, $src, 0,0, $srcx,$srcy, $w, $h, $srcw,$scrh);<br/>";
			ImageCopyResampled($dst, $src, 0,0, $srcx,$srcy, $w, $h, $srcw,$srch);

			if($WM !== null) {
				$wm = imagecreatefrompng($WM['img']);

				imagealphablending($dst, true);
				imagealphablending($wm, true);

				if($WM['w'] == 0 or $WM['h'] == 0) {
					$WM['w'] = imagesx($wm);
					$WM['h'] = imagesy($wm);
				}

				if($WM['x'] < 0) $WM['x'] = $w - $WM['w'] + $WM['x'];
				if($WM['y'] < 0) $WM['y'] = $h - $WM['h'] + $WM['y'];

				imagecopymerge($dst, $wm, $WM['x'], $WM['y'], 0, 0, $WM['w'], $WM['h'], 100);
			}

			touch($dst_file);
			ImageJPEG($dst, $dst_file);

			return array($w, $h);
		}
	}
