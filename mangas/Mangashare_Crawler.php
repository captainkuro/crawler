<?php
// read.mangashare.com/Aiki/chapter-071/page001.html
// read.mangashare.com/Bleach
class Mangashare_Crawler implements Manga_Crawler {

	public function is_supported($url) {
		return strpos($url, 'http://read.mangashare.com/') !== false;
	}

	public function is_single_chapter($url) {
		return strpos($url, '/chapter-') !== false;
	}

	public function get_infix($url) {
		if (preg_match('/chapter-([^\/]+)/', $url, $m)) {
			return $m[1];
		} else {
			return '';
		}
	}

	public function get_info($base) {
		$p = new Page($base);
		$h = new simple_html_dom();
		$h->load($p->content());
		print_r($p->content());
		$data = $h->find('table.datalist', 0);

		$list = array();
		foreach ($data->find('tr.datarow') as $row) {
			$desc = $row->find('td', 1)->innertext;
			$href = $row->find('td', 3)->find('a', 0)->href;
			$list[] = array(
				'url' => $href,
				'desc' => $desc,
				'infix' => $this->get_infix($href),
			);
		}
		return $list;
	}

	public function get_images($chapter_url, $prefix, $infix) {
		$ifx = Text::create($infix)->pad(3)->to_s();
		$p = new Page($chapter_url);
		$h = new simple_html_dom();
		$h->load($p->content());
		$select = $h->find('select[name="pagejump"]', 0);
		$img = $h->find('#page', 0)->find('img', 0);
		$srcdir = dirname($img->src);

		$pages = array();
		foreach ($select->find('option') as $opt) {
			$pages["$prefix-$infix-{$opt->value}.jpg"] = $srcdir . '/' . $opt->value . '.jpg';
		}
		return $pages;
	}
}
