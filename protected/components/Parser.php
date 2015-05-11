<?php
	class Parser extends CApplicationComponent {
		public function init() {

		}

		public function parse($t) { return $this->out($t); }

		private function plaintext($t) {
			$t = preg_replace(
				'#((http|ftp|https):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&amp;:/~\+\#]*[\w\-\@?^=%&amp;/~\+\#])?)#',
				"<a href='\\1'>\\1</a>",
				$t
			);

			$t = preg_replace('/(\s)-(\s)/', '\1&ndash;\2', $t);

			// вместо одного регекспа, взгромоздить здесь гору реплейсов отсюда http://webscript.ru/stories/01/05/21/2766304
			$t = preg_replace('|"([^"]*)"|i', '&laquo;\1&raquo;', $t);

			// Ники юзеров
			$t = preg_replace('/@([a-zA-Z0-9_]+)/', '@<a href="/users/go?login=\1" class="user">\1</a>', $t);

			return nl2br($t);
		}

		/**
		* Парсер на вывод текста. Делает несложные замены, касающиеся типографики на лету, чтобы не усложнять код, который
		* пользователю потом может понадобиться отредактировать
		*
		* @param string $t входной текст
		* @return string
		*/
		public function out($t) {
			// Привет! <a href="http://romakhin.ru">http://romakhin.ru/</a> - ссылка на мой сайтик!

			$len = mb_strlen($t);
			$parse = true;
			$cursor = 0;
			$out = "";
			do {
				if($parse) {
					$i = mb_strpos($t, "<", $cursor);
					if($i === false) $i = $len;
					$out .= $this->plaintext(mb_substr($t, $cursor, $i - $cursor));
//					echo "<pre>CUT&PARSE: '" . htmlspecialchars(mb_substr($t, $cursor, $i - $cursor)) . "' cursor={$cursor}, i={$i}, len={$len}</pre>";
					$cursor = $i;
					$parse = false;
				} else {
					$i = mb_strpos($t, ">", $cursor);
					if($i === false) $i = $len;
					$out .= mb_substr($t, $cursor, $i - $cursor + 1);
//					echo "<pre>CUT: '" . htmlspecialchars(mb_substr($t, $cursor, $i - $cursor + 1)) . "' cursor={$cursor}, i={$i}, len={$len}</pre>";
					$cursor = $i + 1;
					$parse = true;
				}
			} while($cursor < $len);

			$out = preg_replace('@<a(.*?href=[\"\']https?://.*?)>(.*?)</a>@is','<a rel="nofollow"$1>$2</a>', $out);

			return $out;
		}

		/**
		* Парсер перед сохранением текста (пост, комментарий).
		* Преобразовывает ссылки.
		*
		* @param string $html
		*/
		public function in($html) {
			$html =
				"<!DOCTYPE html><html><head>" .
				"<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">" .
				"<body id='body'>" .
					$html .
				"</body></html>";

			$dom = new DOMDocument();
			$dom->strictErrorChecking = false;

			$dom->loadHTML($html);
			$nodes = $dom->getElementById("body")->childNodes;

			echo "<pre>";
			foreach($nodes as $node) {
				echo "Node: {$node->nodeType} - {$node->nodeName} - " . addcslashes($node->nodeValue, "\r\n") . "\n";
				if($node->nodeType == XML_TEXT_NODE) {
					$node->nodeValue = preg_replace(
						'#((http|ftp|https):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&amp;:/~\+\#]*[\w\-\@?^=%&amp;/~\+\#])?)#',
						"[a href='\\1']\\1[/a]",
						$node->nodeValue
					);
				}
			}
			echo "</pre>";

			return $dom->saveHTML($body);
		}

		public static function prepareForDOM($html, $encoding = "UTF-8") {
			$html = iconv($encoding, 'UTF-8//TRANSLIT', $html);
			$html = preg_replace('/<(script|style|noscript)\b[^>]*>.*?<\/\1\b[^>]*>/is', '', $html);
			$tidy = new tidy;
			$config = array(
				'drop-font-tags' => true,
				'drop-proprietary-attributes' => true,
				'hide-comments' => true,
				'indent' => true,
				'logical-emphasis' => true,
				'numeric-entities' => true,
				'output-xhtml' => true,
				'wrap' => 0
			);
			$tidy->parseString($html, $config, 'utf8');
			$tidy->cleanRepair();
			$html = $tidy->value;
			$html = preg_replace('#<meta[^>]+>#isu', '', $html);
			$html = preg_replace('#<head\b[^>]*>#isu', "<head>\r\n<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />", $html);
			return $html;
		}
	}
?>
