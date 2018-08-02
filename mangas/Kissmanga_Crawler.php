<?php
// http://kissmanga.com/Manga/Assassin-s-Creed-4-Black-Flag-Kakusei
class Kissmanga_Crawler implements Manga_Crawler {
	
	public function is_supported($url) {
		return strpos($url, 'kissmanga.com/') !== false;
	}

	public function is_single_chapter($url) {
		return strpos($url, '?url=') !== false;
	}

	public function get_infix($url) {
		$segment = basename($url);
		if (preg_match('/(\d+)/', $segment, $m)) {
			return $m[1];
		} else {
			return '';
		}
	}

	public function get_info($base) {
		$DOMAIN = 'http://kissmanga.com';
		// crawl chapters
		$p = new Page($base, array('become_firefox' => true, 'bypass_cloudflare' => true));
		$h = new simple_html_dom();
		$h->load($p->content());

		$table = $h->find('table.listing', 0);
		$list = array();

		foreach ($table->find('a') as $a) {
			$href = $DOMAIN . $a->href;
			$desc = $a->title;
			$infix = $this->get_infix($href);
			$list[] = array(
				'url' => $href,
				'desc' => $desc,
				'infix' => $infix,
			);
		}
		return $list;
	}
	
	public function get_images($chapter_url, $prefix, $infix) {
		$ifx = Text::create($infix)->pad(3)->to_s();
		$p = new Page($chapter_url, array('become_firefox' => true, 'bypass_cloudflare' => true));
		// grab list of pages
		$p->go_line('var lstImages');
		$i = 1;
		$result = array();
		do { if ($p->curr_line()->contain('lstImages.push')) {
			$line = $p->curr_line();
			$img = $line->cut_between('push("', '")');
			$iname = Text::create($i++)->pad(3)->to_s() .
				Text::create(basename($img))
					->cut_rfrom('.')
					->cut_before('?')
					->to_s();
			$name = "$prefix-$ifx-$iname";
			$result[$name] = $img;
		}} while (!$p->next_line()->contain('new Array()'));
		$pages = $p->curr_line()->extract_to_array('href="', '"');
		
		return $result;
	}
	
}
