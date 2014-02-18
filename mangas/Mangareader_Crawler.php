<?php
require 'crawler.php';
// http://www.mangareader.net/onepunch-man/42/36
// http://www.mangapanda.com/gigantomakhia/6
class Mangareader_Crawler implements Manga_Crawler {

	public function is_supported($url) {
		return strpos($url, 'http://www.mangareader.net/') !== false
			|| strpos($url, 'http://www.mangapanda.com/') !== false;
	}

	public function is_single_chapter($url) {
		return (bool)preg_match('/\/\d+$/', $url);
	}

	public function get_infix($url) {
		if (preg_match('/\/([^\/]*)$/', $url, $m)) {
			return $m[1];
		} else {
			return '';
		}
	}

	private function sitename($base) {
		if (isset($this->base) && strpos($this->base, 'mangapanda.com') !== false) {
			return 'http://www.mangapanda.com';
		}
		return 'http://www.mangareader.net';
	}

	public function get_info($base) {
		$sitename = $this->sitename($base);
		$c = new Crawler($base);
		$c->go_to('id="listing"');
		$list = array();
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, 'class="chico_')) {
				if (!Crawler::is_there($line, ' href="')) $line = $c->readline();
				$chp = Crawler::extract($line, 'href="', '"');
				$ifx = Crawler::cutfromlast1($chp, '/');
				$ifx = str_replace('chapter-', '', $ifx);
				$ifx = str_replace('.html', '', $ifx);
				$list[] = array(
					'url' => $sitename . $chp,
					'infix' => $ifx,
					'desc' => strip_tags(Crawler::extract($line, '">', '</td>')),
				);
			} else if (Crawler::is_there($line, '</table>')) {
				break;
			}
		}
		$c->close();
		return $list;
	}

	public function get_images($chapter_url, $prefix, $infix) {
		$sitename = $this->sitename($chapter_url);
		$c = new Crawler($chapter_url);
		$c->go_to('id="pageMenu"');
		$pages = array();
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, '<option')) {
				$pages[] = $sitename . Crawler::extract($line, 'value="', '"');
			} else if (Crawler::is_there($line, '</select>')) {
				break;
			}
		}
		$c->close();
		
		$result = array();
		foreach ($pages as $page) {
			$result = $result + $this->mangareader_1_page($page, $page, $prefix, $infix);
		}
		return $result;
	}
	
	public function mangareader_1_page($fil, $url, $prefix, $chapter) {
		$chapter = Crawler::pad($chapter, 3);
		$c = new Crawler($fil);
		$c->go_to('width="800"');
		$img = $c->getbetween('src="', '"');
		
		preg_match('/(\d+\.\w+)$/', basename($img), $m);
		$iname = $m[1];
		$c->close();
		
		$name = $prefix.'-'.$chapter.'-'.$iname;
		return array($name => $img);
	}
	
}
