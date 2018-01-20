<?php
// https://kiss-manga.com/tensei-shitara-slime-datta-ken.manga
// https://kiss-manga.com/read-tensei-shitara-slime-datta-ken-chapter-34
class Kiss_Manga_Crawler implements Manga_Crawler {
	
	public function is_supported($url) {
		return strpos($url, 'kiss-manga.com/') !== false;
	}

	public function is_single_chapter($url) {
		return strpos($url, '/read-') !== false
			&& strpos($url, '-chapter-') !== false;
	}

	public function get_infix($url) {
		$segment = basename($url);
		if (preg_match('#-chapter-(\d+)#', $segment, $m)) {
			return $m[1];
		} else {
			return '';
		}
	}

	public function get_info($base) {
		$DOMAIN = 'https://kiss-manga.com';
		$p = new Page($base);
		$h = new simple_html_dom();
		$h->load($p->content());

		$list = array();
		$chapters = $h->find('.list_chapters', 0);
		foreach ($chapters->find('a') as $a) {
			$href = $DOMAIN . $a->href;
			$desc = $a->text();
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

		$i = 1;
		$result = array();
		$images = $h->find('.fullsizable');

		foreach ($images as $img) {
			$src = $img->src;
			$iname = Text::create($i++)->pad(3)->to_s() .
				Text::create(basename($src))
					->cut_rfrom('.')
					->to_s();
			$name = "$prefix-$ifx-$iname";
			$result[$name] = $src;
		}
		
		return $result;
	}
	
}
