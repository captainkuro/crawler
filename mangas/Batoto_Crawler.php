<?php
// http://www.batoto.net/comic/_/comics/xblade-r789
// http://www.batoto.net/read/_/27286/xblade_ch41_by_twilight-dreams-scans
class Batoto_Crawler implements Manga_Crawler {

	public function is_supported($url) {
		return strpos($url, 'http://www.batoto.net/') !== false
			|| strpos($url, 'http://bato.to/') !== false;
	}

	public function is_single_chapter($url) {
		return strpos($url, '/read/_') !== false;
	}

	public function get_infix($url) {
		if (preg_match('/ch(\d+)/', $url, $m)) {
			return $m[1];
		} else {
			return '';
		}
	}

	public function get_info($base) {
		// crawl chapters
		$p = new Page($base);
		$p->go_line('h3 class="maintitle"');
		
		$list = array();
		do {
			if ($p->curr_line()->contain('book_open.png')) {
				$line = $p->curr_line()->dup();
				$href = $line->dup()->cut_between('href="', '"')->to_s();
				$desc = $line->dup()->cut_between('/>', '</a')->to_s();
				preg_match('/h\.(\d+):?/', $desc, $m);
				$infix = $m[1];
				preg_match('/_by_(.*)$/', $href, $m);
				$group = $m[1];
				// cek bahasa
				$lang = $p->next_line(2);
				if ($lang->contain('English')) {
					$list[] = array(
						'url' => $href,
						'desc' => $desc.' by '.$group,
						'infix' => $infix,
					);
				}
			}
		} while (!$p->next_line()->contain('</table>'));
		return $list;
	}

	public function get_images($chapter_url, $prefix, $infix) {
		$ifx = Text::create($infix)->pad(3)->to_s();
		$p = new Page($chapter_url);
		// grab list of pages
		$p->go_line('id="page_select"');
		$pages = $p->next_line()->extract_to_array('value="', '"');
		// grab current image
		
		$result = $this->crawl_page($p, $prefix, $ifx, 1);
		
		array_shift($pages);
		foreach ($pages as $i => $purl) {
			$p = new Page($purl);
			$result = $result + $this->crawl_page($p, $prefix, $ifx, $i+2);
		}
		return $result;
	}
	
	public function crawl_page($p, $prefix, $ifx, $i) {
		$p->go_line('id="full_image"');
		$img = $p->next_line(3)->dup()->cut_between('src="', '"')->to_s();
		$iname = urldecode(basename($img));
		$ext = pathinfo($iname, PATHINFO_EXTENSION);
		// // 12 karakter aneh
		// if (preg_match('/[0-9a-z]{13}\.\w+$/', $iname)) {
		// 	$iname = preg_replace('/\w{13}\.(\w+)$/', '.$1', $iname);
		// }
		// if (preg_match('/_(\d+)_[a-zA-Z]+\.\w{3}$/', $iname, $m)) {
		// 	$iname = $m[1].substr($iname, -4);
		// } else {
		// 	// ambil last 3 character
		// 	$iname = substr($iname, -7);
		// }
		$iname = Text::create($i)->pad(3).'.'.$ext;
		return array("$prefix-$ifx-$iname" => $img);
	}
	
}
