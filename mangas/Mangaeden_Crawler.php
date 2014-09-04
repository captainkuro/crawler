<?php
// http://www.mangaeden.com/en-manga/one-piece/

class Mangaeden_Crawler implements Manga_Crawler {
	
	public function is_supported($url) {
		return strpos($url, 'http://www.mangaeden.com/') !== false;
	}

	public function is_single_chapter($url) {
		return (bool)preg_match('/\/1\/$/', $url);
	}

	public function get_infix($url) {
		if (preg_match('/\/([\d\.]+)\/1\/$/', $url, $m)) {
			return $m[1];
		} else {
			return '';
		}
	}

	public function get_info($base) {
		$domain = 'http://www.mangaeden.com';
		$p = new Page($base);
		$h = new simple_html_dom();
		$h->load($p->content());

		$links = $h->find('.chapterLink');
		$list = array();
		foreach ($links as $a) {
			$url = $domain . $a->href;
			$desc = $a->find('b', 0)->innertext();
			$list[] = array(
				'url' => $url,
				'desc' => $desc,
				'infix' => $this->get_infix($url),
			);
		}

		return $list;
	}
	
	public function get_images($chapter_url, $prefix, $infix) {
		$ifx = Text::create($infix)->pad(3)->to_s();
		$p = new Page($chapter_url);
		// grab list of pages
		$p->go_line('id="pageInfo"');
		$n = $p->curr_line()->cut_between(' of ', '<')->to_s();
		$pages = array();
		for ($i=1; $i<=$n; $i++) {
			$pages[] = preg_replace('/\/1\/$/', '/'.$i.'/', $chapter_url);
		}
		
		// grab current image
		$p->reset_line();
		$result = $this->crawl_page($p, $prefix, $ifx, 1);
		array_shift($pages);

		// grab the rest of pages
		foreach ($pages as $i => $page) {
			$p = new Page($page);
			$result = $result + $this->crawl_page($p, $prefix, $ifx, $i+2);
		}
		return $result;
	}
	
	public function crawl_page($p, $prefix, $ifx, $i) {
		$p->go_line('id="mainImg"');
		$img = $p->curr_line()->dup()->cut_between('src="', '"')->to_s();
		$iname = Text::create($i)->pad(3)->to_s();
		preg_match('/\.(\w+)$/', $img, $m);
		$iname = $iname.'.'.$m[1];
		return array("$prefix-$ifx-$iname" => $img);
	}
	
}
