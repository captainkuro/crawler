<?php
// http://www.mangastream.to/vinland-saga.html
// http://www.mangastream.to/vinland-saga-chapter-104.html
// http://www.mangastream.to/vinland-saga-chapter-104-page-2.html
class Mangastreamto_Crawler implements Manga_Crawler {
	public function is_supported($url) {
		return strpos($url, 'http://www.mangastream.to/') !== false;
	}

	public function is_single_chapter($url) {
		return strpos($url, '-chapter-') !== false;
	}

	public function get_infix($url) {
		if (preg_match('/-chapter-([\d]+)/', $url, $m)) {
			return $m[1];
		} else {
			return '';
		}
	}

	public function get_info($base) {
		$p = new Page($base);
		$h = new simple_html_dom();
		$h->load($p->content());
		$list = array();

		$links = $h->find('.ch-subject', 0);
		foreach ($links->find('a') as $a) {
			$desc = $a->innertext;
			$href = 'http://www.mangastream.to' . $a->href;
			$list[] = array(
				'url' => $href,
				'desc' => $desc,
				'infix' => $desc,
			);
		}
		return array_reverse($list);
	}

	public function get_images($chapter_url, $prefix, $infix) {
		$ifx = Text::create($infix)->pad(3)->to_s();
		$p = new Page($chapter_url);
		$h = new simple_html_dom();
		$h->load($p->content());

		$pattern_url = Text::create($chapter_url)
			->regex_replace('#-page-\d.*.html$#', '.html')
			->regex_replace('#\.html$#', '-page-%s.html')
			->to_s();
		$pages = $h->find('#id_page', 0);
		$n = $pages->last_child()->value;

		$result = array();
		list($img_name, $img_url) = $this->crawl_page($p, $prefix, $ifx, 1);
		$result[$img_name] = $img_url;
		
		for ($i=2; $i<=$n; $i++) {
			$p = new Page(sprintf($pattern_url, $i));
			list($img_name, $img_url) = $this->crawl_page($p, $prefix, $ifx, $i);
			$result[$img_name] = $img_url;
		}
		return $result;
	}

	public function crawl_page($p, $prefix, $ifx, $i) {
		$h = new simple_html_dom();
		$h->load($p->content());

		$img = $h->find('.manga-page', 0);
		$src = $img->src;
		$iname = urldecode(basename($src));
		$ext = pathinfo($iname, PATHINFO_EXTENSION);
		$iname = Text::create($i)->pad(3).'.'.$ext;

		return array("$prefix-$ifx-$iname", $src);
	}
}