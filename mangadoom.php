<?php
// http://mangadoom.com/One-Piece/
// http://mangadoom.com/One-Piece/711/2/
class Mangadoom extends Manga_Crawler {
	protected $enable_single_chapter = true;
	protected $reverse_download_chapters = true;
	
	// need to be overridden, return array[desc,url,infix]
	// $base is URL submitted
	public function extract_info($base) {
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

	// must be overridden, echo html of links
	// $v contain [url,desc,infix]
	public function crawl_chapter($v) {
		$ifx = Text::create($v['infix'])->pad(3)->to_s();
		$p = new Page($v['url']);
		// grab total page
		$p->go_line('select class="cbo_wpm_pag"');
		$p->next_line();
		$p->go_line('select class="cbo_wpm_pag"');
		$pages = $p->curr_line()->extract_to_array('value="', '"');
		// grab first image
		$p->reset_line();
		$this->crawl_page($p, $ifx);
		// iterate
		array_shift($pages);
		foreach ($pages as $page) {
			$purl = $v['url'].$page.'/';
			$q = new Page($purl);
			$this->crawl_page($q, $ifx);
		}
	}

	protected function crawl_page($p, $ifx) {
		$prefix = $this->prefix;
		$p->go_line('id="img_mng_enl"');
		$src = $p->curr_line()->cut_between('src="', '"')->to_s();
		$name = Text::create(basename($src));
		if ($name->contain('?')) {
			$name = $name->cut_until('?');
		}
		echo "<a href='$src'>$prefix-$ifx-$name</a><br>\n";
	}

	public function url_is_single_chapter($url) {
		return (bool)preg_match('/\d+\/$/', $url);
	}

	public function grab_chapter_infix($url) {
		preg_match('/(\d+)\/$/', $url, $m);
		return $m[1];
	}
}
Mangadoom::factory()->run();
