<?php

class Ngomik_Downloader implements ADownloader {
	public function display() {
		return 'Ngomik.com';
	}

	public function download () {
		echo "Komik URL: ";
		$komik_url = trim(fgets(STDIN));
		echo "Save Dir: ";
		$dir = trim(fgets(STDIN));

		$episodes = $this->all_episodes($komik_url);
		$this->download_episodes($dir, $episodes);
	}

	private function open($url) {
		echo "Opening {$url}...\n";
		$p = new Page($url);
		$h = new simple_html_dom();
		$h->load($p->content());
		return $h;
	}

	private function all_episodes($komik_url) {
		$h = $this->open($komik_url);

		$result = array();
		$div = $h->find('#tab-chapter', 0);
		foreach ($div->find('.list-group-item') as $a) {
			$href = $a->href;
			$href = preg_replace('/\/cover$/', '/read', $href);
			$href = str_replace('https:', 'http:', $href);
			$text = str_replace('&nbsp;', '', $a->plaintext);
			$text = trim(html_entity_decode($text, ENT_COMPAT, 'UTF-8'));
			$text = preg_replace('/\s\s+/', ' ', $text);
			$result[$href] = $text;
		}
		return $result;
	}

	private function download_episodes($dir, $episodes) {
		$dir = rtrim($dir, '/') . '/';

		foreach ($episodes as $url => $text) {
			$episode_dir = $dir . $text . '/';
			if (!is_dir($episode_dir)) {
				mkdir($episode_dir);

				$h = $this->open($url);
				$i = 1;
				$thumbnails = $h->find('#modal-chapter-thumbnail', 0);
				foreach ($thumbnails->find('.img-thumbnail') as $img) {
					$src = $img->src;
					$src = str_replace('https:', 'http:', $src);
					$src = preg_replace('/\/150x200\//', '/600x0/', $src);
					$filename = Text::create($i++)->pad(3)->to_s();
					$outpath = $episode_dir . $filename . '.jpg';
					download_it($src, $outpath);
				}
			}
		}
	}
}