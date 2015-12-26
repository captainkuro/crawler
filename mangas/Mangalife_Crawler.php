<?php
// http://manga.life/read-online/OnepunchMan
// http://manga.life/read-online/OnepunchMan/chapter-53.2/index-1/page-1
class Mangalife_Crawler implements Manga_Crawler {
	public function is_supported($url) {
		return strpos($url, '//manga.life/') !== false;
	}

	public function is_single_chapter($url) {
		return strpos($url, '/chapter-') !== false;
	}

	public function get_infix($url) {
		if (preg_match('/chapter-([\d\.]+)/', $url, $m)) {
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

		$links = $h->find('.list', 1);
		foreach ($links->find('a.list-group-item') as $a) {
			$desc = trim($a->innertext);
			$href = 'http://manga.life' . $a->href;
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

		$base_chapter_url = dirname($chapter_url);
		$pages_url = array();
		$select = $h->find('select.changePageSelect', 0);
		foreach ($select->find('option') as $option) {
			$pages_url[] = $base_chapter_url . '/' . $option->value;
		}

		$result = array();
		foreach ($pages_url as $i => $page_url) {
			$p2 = new Page($page_url);
			$p2->go_line('/s16000/');
			$src = $p2->curr_line()->cut_between('src="', '"');
			$ext = $src->cut_rafter('.');
			$iname = Text::create($i+1)->pad(3).'.'.$ext;

			$result["$prefix-$ifx-$iname"] = $src->to_s();
		}
		return $result;
	}
}