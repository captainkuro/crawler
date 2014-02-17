<?php
// http://www.mangainn.com/manga/2373_yamada-kun-to-7-nin-no-majo
// http://www.mangainn.com/manga/chapter/78967_yamada-kun-to-7-nin-no-majo-chapter-01
class Mangainn_Crawler implements Manga_Crawler {
	
	public function is_supported($url) {
		return strpos($url, 'http://www.mangainn.com/') !== false;
	}

	public function is_single_chapter($url) {
		return (bool)preg_match('/\/chapter\/\d+/', $url);
	}

	public function get_infix($url) {
		if (preg_match('/(\d+)$/', $url, $m)) {
			return $m[1];
		} else {
			return '';
		}
	}

	public function get_info($base) {
		$p = new Page($base);
		$p->go_line('<div class="divThickBorder" style="padding:7px">');
		$raw = $p->next_line()->dup();
		$list = array();
		foreach (explode('<tr>', $raw->to_s()) as $line) {
			$tline = new Text($line);
			if ($tline->contain('href="')) {
				$href = $tline->dup()->cut_between('href="', '"')->to_s();
				$desc = $tline->dup()->cut_between('">', '</a')->to_s();
				preg_match('/([\.\d]+) :/', $desc, $m);
				$infix = $m[1];
				$list[] = array(
					'url' => $href,
					'desc' => strip_tags($desc),
					'infix' => $infix,
				);
			}
		}
		return $list;
	}
	
	public function get_images($chapter_url, $prefix, $infix) {
		$ifx = Text::create($infix)->pad(3)->to_s();
		$p = new Page($chapter_url);
		// grab list of pages
		$p->go_line('id="cmbpages"');
		$pages = $p->curr_line()->extract_to_array('value="', '"');
		// grab current image
		$result = $this->crawl_page($p, $prefix, $ifx);
		array_shift($pages);
		// grab the rest of pages
		foreach ($pages as $i => $page) {
			$p = new Page($chapter_url.'/page_'.$page);
			$result = $result + $this->crawl_page($p, $prefix, $ifx);
		}
		return $result;
	}
	
	public function crawl_page($p, $prefix, $ifx) {
		$p->go_line('id="imgPage"');
		$img = $p->next_line()->dup()->cut_between('src="', '"')->to_s();
		$iname = urldecode(basename($img));
		// 001_02_22_2012_10_46_59.jpg jadi 001.jpg
		preg_match('/^([^_]+).*\.(\w+)$/', $iname, $m); // $m[1] no urut, $m[2] extension
		$iname = Text::create($m[1])->pad(3)->to_s().'.'.$m[2];
		return array("$prefix-$ifx-$iname" => $img);
	}
	
}
