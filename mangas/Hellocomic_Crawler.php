<?php
// http://www.hellocomic.com/comic/view?slug=batgirl
// http://www.hellocomic.com/batgirl/c00/p1
class Hellocomic_Crawler implements Manga_Crawler {
	public function is_supported($url) {
		return strpos($url, 'http://www.hellocomic.com/') !== false;
	}

	public function is_single_chapter($url) {
		return preg_match('#/c([\d]+)/p#', $url);
	}

	public function get_infix($url) {
		if (preg_match('#/c([\d]+)/p#', $url, $m)) {
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

		$links = $h->find('#w0', 0);
		foreach ($links->find('a') as $a) {
			$desc = $a->innertext;
			$href = $a->href;
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
		$p = new Page($chapter_url);
		$h = new simple_html_dom();
		$h->load($p->content());

		$pattern_url = Text::create($chapter_url)
			->regex_replace('#/p1$#', '/p%s')
			->to_s();

		$select = $h->find('#e1', 0);
		$result = array();
		foreach ($select->find('option') as $option) {
			$p2 = new Page(sprintf($pattern_url, $option->value));
			$h2 = new simple_html_dom();
			$h2->load($p2->content());
			$img = $h2->find('.coverIssue', 0)->find('img', 0)->src;
			$ext = pathinfo($img, PATHINFO_EXTENSION);
			$iname = Text::create($option->value)->pad(3)->to_s().'.'.$ext;

			$result["$prefix-$ifx-$iname"] = $img;
		}
		return $result;
	}
}