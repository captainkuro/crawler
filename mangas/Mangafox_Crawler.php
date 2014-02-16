<?php
// http://mangafox.me/manga/fairy_tail/
// http://mangafox.me/manga/fairy_tail/v34/c313/
class Mangafox_Crawler implements Manga_Crawler {
	
	public function is_supported($url) {
		return strpos($url, 'http://mangafox.me/') !== false;
	}

	public function is_single_chapter($url) {
		return (bool)preg_match('/\/c\d+\//', $url);
	}

	public function get_infix($url) {
		if (preg_match('/\/c(\d+)\//', $url, $m)) {
			return (int)$m[1];
		} else {
			return '';
		}
	}

	public function get_info($base) {
		$p = new Page($base);
		$p->go_line('id="chapters"');
		$list = array();
		$item = array();
		do {
			$line = $p->curr_line();
			if ($line->contain('class="tips"')) {
				$item['url'] = $line->cut_between('href="', '"')->to_s();
				$item['infix'] = $this->get_infix($item['url']);
				$item['desc'] = $line->cut_between('tips">', '</')->to_s();
			} else if ($line->contain('title nowrap')) {
				$item['desc'] .= ': '.$line->cut_between('nowrap">', '</')->to_s();
				$list[] = $item;
			}
		} while (!$p->next_line()->contain('id="discussion"'));
		return $list;
	}

	public function get_images($chapter_url, $prefix, $infix) {
		$ifx = Text::create($infix)->pad(3)->to_s();
		$p = new Page($chapter_url);
		// grab total page
		$p->go_line('id="top_bar"');
		$p->go_line_regex('/of \d+\w+/');
		$tot = $p->curr_line()->regex_match('/of (\d+)/');
		$tot = $tot[1];
		// grab first image
		$p->go_line('id="viewer"');
		$p->next_line(2);
		$src = $p->curr_line()->cut_between('src="', '"');
		$name = basename($src);

		$result = array("$prefix-$ifx-$name" => $src);
		for ($i=2; $i<=$tot; $i++) {
			$p = new Page(dirname($chapter_url).'/'.$i.'.html');
			$p->go_line('id="viewer"');
			$p->next_line(2);
			$src = $p->curr_line()->cut_between('src="', '"');
			$name = basename($src);
			
			$result["$prefix-$ifx-$name"] = $src;
		}
		return $result;
	}

}
