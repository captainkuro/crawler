<?php

class ZBirthOfLevi_Downloader implements ADownloader {
	public function display() {
		return 'Z - Birth of Levi';
	}

	public function download () {
		$start = 'http://www.horizonscans.com/acwnr';
		
		$chapters = $this->grab_chapters($start);
		$this->download_chapters($chapters);
	}

	private function grab_chapters($start) {
		$p = new Page($start);
		$h = new simple_html_dom();
		$h->load($p->content());
		$chapters = array();
		$domain = 'http://www.horizonscans.com';

		$boxes = $h->find('.box');
		foreach ($boxes as $box) {
			$a = $box->find('a', 0);
			$title = $box->find('h3', 0)->plaintext;
			$url = $domain . $a->href;

			$infix = $this->determine_infix($title);
			if ($infix) {
				$chapters[$infix] = $url;
			}
		}
		return $chapters;
	}

	private function determine_infix($title) {

		if (preg_match('/Special Edition (\d+) Small Booklet/i', $title, $m)) {
			return 'ZBooklet' . $m[1];
		} else if (preg_match('/Special Chapter (\d+)/i', $title, $m)) {
			return 'Special' . $m[1];
		} else if (preg_match('/Chapter (\d+)/i', $title, $m)) {
			return Text::create($m[1])->pad(3)->to_s();
		} else {
			return null;
		}
	}

	private function download_chapters($chapters) {
		$prefix = 'Birth_of_Levi';
		$dir = '/home/khandar-gdp/tmp/birth of levi/';

		foreach ($chapters as $infix => $url) {
			echo "Opening {$url}...\n";
			$p = new Page($url);
			$p->go_line('pages[1]=');
			$i = 1;
			do {
				$line = $p->curr_line();
				$img = $line->cut_between('="', '"');
				$suffix = Text::create($i++)->pad(3)->to_s();
				$ext = $img->cut_rafter('.');
				$filename = "{$dir}{$prefix}-{$infix}-{$suffix}.{$ext}";
				
				if (!is_file($filename)) {
					download_it($img->to_s(), $filename);
				}
			} while ($p->next_line()->contain('pages['));
		}
	}
}