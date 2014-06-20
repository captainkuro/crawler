<?php
// http://hugemanga.com/Kungfu_Komang/
// http://hugemanga.com/Kungfu_Komang/Kung_Fu_Komang_Vol_21

class Hugemanga_Crawler implements Manga_Crawler {
	
	public function is_supported($url) {
		return strpos($url, 'hugemanga.com/') !== false;
	}

	public function is_single_chapter($url) {
		return substr_count($url, '/') === 4;
	}

	public function get_infix($url) {
		$segment = basename($url);
		if (preg_match('/(\d+)$/', $segment, $m)) {
			return $m[1];
		} else {
			return '';
		}
	}

	public function get_info($base) {
		$p = new Page($base);
		$h = new simple_html_dom();
		$h->load($p->content());

		$select = $h->find('select[name="chapter"]', 0);
		$list = array();
		foreach ($select->find('option') as $option) {
			preg_match('/(\d+)$/', $option->value, $m);
			$list[] = array(
				'url' => $base.'/'.$option->value,
				'desc' => $option->value,
				'infix' => $m[1],
			);
		}
		return $list;
	}
	
	public function get_images($chapter_url, $prefix, $infix) {
		$ifx = Text::create($infix)->pad(3)->to_s();
		$p = new Page($chapter_url);
		$h = new simple_html_dom();
		$h->load($p->content());

		$DOMAIN = 'http://hugemanga.com/';
		$first_image = $h->find('img.picture', 0);
		$pattern = $DOMAIN . 
			dirname($first_image->src) .'/'. 
			str_replace('01', '###', basename($first_image->src));

		$result = array();
		$pages = $h->find('select[name="page"]', 0);
		foreach ($pages->find('option') as $option) {
			$page = Text::create($option->value)->pad(2)->to_s();
			$full = str_replace('###', $page, $pattern);
			$name = basename($full);
			$result["$prefix-$ifx-$name"] = $full;
		}
		return $result;
	}
	
}
