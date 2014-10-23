<?php
// http://www.mangahere.co/manga/green_worldz/
// http://www.mangahere.co/manga/green_worldz/c043/
// http://www.mangahere.co/manga/green_worldz/c043/2.html
class Mangahere_Crawler implements Manga_Crawler {
	
	public function is_supported($url) {
		return strpos($url, 'www.mangahere.co') !== false;
	}

	public function is_single_chapter($url) {
		return (bool)preg_match('/\/c[\d\.]+\//', $url);
	}

	public function get_infix($url) {
		if (preg_match('/\/c([\d\.]+)\/$/', $url, $m)) {
			return $m[1];
		} else {
			return '';
		}
	}

	public function get_info($base) {
		$p = new Page($base);
		$h = new simple_html_dom();
		$h->load($p->content());

		$chapters = $h->find('.detail_list', 0)->find('ul', 0);
		$list = array();
		foreach ($chapters->find('li') as $li) {
			$a = $li->find('a', 0);
			$url = $a->href;
			$desc = $a->innertext();

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
		$h = new simple_html_dom();
		$h->load($p->content());

		$pages = array();
		$select = $h->find('select.wid60', 0);
		foreach ($select->find('option') as $option) {
			$pages[] = $option->value;
		}

		// grab current image
		$result = $this->crawl_page($p, $prefix, $ifx);
		array_shift($pages);

		// grab the rest of pages
		foreach ($pages as $i => $page) {
			$p = new Page($page);
			$result = $result + $this->crawl_page($p, $prefix, $ifx);
		}
		return $result;
	}
	
	public function crawl_page($p, $prefix, $ifx) {
		$h = new simple_html_dom();
		$h->load($p->content());
		$img = $h->find('#image', 0);
		$src = Text::create($img->src)->cut_before('?');
		$iname = $src->cut_rafter('/');

		return array("$prefix-$ifx-$iname" => $src->to_s());
	}
	
}
