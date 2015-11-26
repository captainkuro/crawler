<?php

class Sankakucomplex_Downloader implements ADownloader {
	private $default_dir;
	private $page_from = 1;
	private $page_to = 10;

	public function display() {
		return 'Sankakucomplex Chan/Idol';
	}

	public function download () {
		$this->default_dir = DConfig::p_folder();

		echo "List URL: ";
		$list_url = trim(fgets(STDIN));
		echo "Save Dir [{$this->default_dir}]: ";
		$dir = trim(fgets(STDIN));
		echo "Page setting [1,10]: ";
		$page_conf = trim(fgets(STDIN));
		$page_conf = explode(',', $page_conf);
		if (!empty($page_conf[0])) {
			$this->page_from = (int)$page_conf[0];
		}
		if (!empty($page_conf[1])) {
			$this->page_to = (int)$page_conf[1];
		}

		$dir = $this->prepare_dir($dir, $list_url);
		$this->collect_images($list_url, $dir);
	}

	private function prepare_dir($dir, $list_url) {
		$dir = $dir ? $dir : $this->default_dir;

		$query = parse_url($list_url, PHP_URL_QUERY);
		parse_str($query, $params);
		$tags = urldecode($params['tags']);
		$tags = preg_replace('#[^\w )(]#', '', $tags);

		$new_dir = rtrim($dir, '/') . '/' . $tags . '/';
		if (!is_dir($new_dir)) {
			mkdir($new_dir);
		}
		return $new_dir;
	}

	private function collect_images($url, $dir) {
		if (strpos($url, '/idol.')) {
			$base = 'https://idol.sankakucomplex.com';
		} else {
			$base = 'https://chan.sankakucomplex.com';
		}
		$page = $this->page_from;
		$id = 1;
		
		$Turl = Text::create($url);
		do {
			if ($page > $this->page_to) break;
		
			$purl = $url.'&page='.$page;
			echo "$purl\n";
			do {
				$P = new Page($purl, array('become_firefox'=>true));
				$T = new Text($P->content());
				sleep(3); // 429 too many requests
			} while ($T->contain('429 Too many requests'));
			$a = $T->extract_to_array('href="', '"');
			foreach ($a as $i => $e) {
				$E = new Text($e);
				if (!$E->contain('/post/show')) {
					unset($a[$i]);
				}
			}
			if (!count($a)) break;
			foreach ($a as $i => $e) {
				$E = new Text($e);
				$kurl = $base . $e;
				echo "$kurl\n";flush();
				do {
					$P = new Page($kurl, array('become_firefox'=>true));
					$T = new Text($P->content());
					sleep(3); // 429 too many requests
				} while ($T->contain('429 Too many requests'));
				
				$P->go_line('id=highres');
				$img = $P->curr_line()->cut_between('href="', '"');
				
				/*if ($img->contain('.webm')) {
					echo "This is WEBM\n";
				} else*/ if ($img->to_s()) {
					$this->download_if_not_exist($img, $dir, $id);
					$id++;
				} else {
					echo "No id=highres\n";
				}
			}
			$page++;
		} while (true);
	}

	private function download_if_not_exist($img, $dir, $id) {
		if ($img->pos('//') === 0) {
			$src = 'https:' . $img->to_s();
		} else {
			$src = $img->to_s();
		}
		$filename = $img->cut_rafter('/')->cut_before('?');
		$outpath = $dir . Text::create($id)->pad(3)->to_s() . '-' . $filename->to_s();

		$hash = $filename->cut_rbefore('.')->to_s();
		if (!in_array($hash, $this->existing_hashes($dir)) && !is_file($outpath)) {
			sleep(3);
			download_it($src, $outpath, "--header=\"User-Agent: Mozilla/5.0 (Windows NT 5.1; rv:6.0.2) Gecko/20100101 Firefox/6.0.2\"");
		}
	}

	private function existing_hashes($dir) {
		$result = array();
		foreach (glob($dir.'*.*') as $f) {
			$filename = Text::create($f);
			$match = $filename->regex_match('/\d+-(\w+)\./');
			if ($match) {
				$result[] = $match[1];
			}
		}
		return $result;
	}
}

