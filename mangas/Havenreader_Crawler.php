<?php
// http://haven-reader.net/index.php?mode=view&series=World+Embryo
// http://haven-reader.net/index.php?mode=view&series=World%20Embryo&chapter=009
class Havenreader_Crawler implements Manga_Crawler {

	public function is_supported($url) {
		return strpos($url, 'http://haven-reader.net/') !== false;
	}

	public function is_single_chapter($url) {
		return strpos($url, 'chapter=') !== false;
	}

	public function get_infix($url) {
		if (preg_match('/chapter=(\d+)/', $url, $m)) {
			return $m[1];
		} else {
			return '';
		}
	}

	public function get_info($base) {
		$p = new Page($base);
		$h = new simple_html_dom();
		$h->load($p->content());
		$chapters = $h->find('select[name="chapter"]', 0);

		$list = array();
		foreach ($chapters->find('option') as $opt) {
			if ($opt->value !== '-- INDEX --') {
				$c = $opt->value;
				$list[] = array(
					'url' => $base.'&chapter='.$c,
					'desc' => $c,
					'infix' => $c,
				);
			}
		}
		return $list;
	}

	public function get_images($chapter_url, $prefix, $infix) {
		$basename = 'http://haven-reader.net';
		$ifx = Text::create($infix)->pad(3)->to_s();
		// open index page
		$p = new Page($chapter_url . '&page=0');
		$h = new simple_html_dom();
		$h->load($p->content());
		$main = $h->find('#main', 0);

		// print_r($p->content());
		$pages = array();
		foreach ($main->find('img.PageLink') as $img) {
			// convert thumb to img
			$thumb = $img->src;
			$real = Text::create($thumb)
				->replace('/thumbs/', '/')
				->regex_replace('/\.jpg$/', '')
				->to_s();
			$alt = $img->alt;
			$pages["$prefix-$ifx-$alt"] = $basename . $real;
		}
		return $pages;
	}
}