<?php
// http://mangadoom.com/One-Piece/
// http://mangadoom.com/One-Piece/710/
class Mangadoom_Crawler implements Manga_Crawler {

	public function is_supported($url) {
		return strpos($url, 'http://mangadoom.com/') !== false;
	}

	public function is_single_chapter($url) {
		return (bool)preg_match('/\d+\/$/', $url);
	}

	public function get_infix($url) {
		if (preg_match('/(\d+)\/$/', $url, $m)) {
			return $m[1];
		} else {
			return '';
		}
	}

	public function get_info($base) {
		$p = new Page($base);
		$p->go_line('id="disqus_thread"');
		$p->go_line('ul class="lst"');
		$list = array();
		$item = array();
		do {
			$line = $p->curr_line();
			if ($line->contain('href="')) {
				$url = $line->cut_between('href="', '"');
				$desc = $line->cut_between('title="', '"');
				$m = $infix = $url->regex_match('/\/(\d+)\/$/');
				$list[] = array(
					'desc' => $desc->to_s(),
					'url' => $url->to_s(),
					'infix' => $m[1],
				);
			}
		} while (!$p->next_line()->contain('</ul>'));
		return $list;
	}

	public function get_images($chapter_url, $prefix, $infix) {
		$ifx = Text::create($infix)->pad(3)->to_s();
		$p = new Page($chapter_url);
		// grab total page
		$p->go_line('select class="cbo_wpm_pag"');
		$p->next_line();
		$p->go_line('select class="cbo_wpm_pag"');
		$pages = $p->curr_line()->extract_to_array('value="', '"');
		// grab first image
		$p->reset_line();
		$result = $this->crawl_page($p, $prefix, $ifx);
		// iterate
		array_shift($pages);
		foreach ($pages as $page) {
			$purl = $chapter_url.$page.'/';
			$q = new Page($purl);
			$result = $result + $this->crawl_page($q, $prefix, $ifx);
		}
		return $result;
	}

	protected function crawl_page($p, $prefix, $ifx) {
		$p->go_line('id="img_mng_enl"');
		$src = $p->curr_line()->cut_between('src="', '"')->to_s();
		$name = Text::create(basename($src));
		if ($name->contain('?')) {
			$name = $name->cut_before('?');
		}
		if ($m = $name->regex_match('/(\d+)_\d+_\d+_\d+_\d+_/')) {
			$ext = $name->cut_rafter('.');
			$name = $m[1] . '.'.$ext;
		}
<<<<<<< HEAD
		$name = urldecode($name);
=======
>>>>>>> 56099a4c5190be3fe985f2a05ab84fae05b2e73f
		return array("$prefix-$ifx-$name" => $src);
	}

}
