<?php
// http://www.batoto.com/comic/_/comics/xblade-r789
// http://www.batoto.com/read/_/27286/xblade_ch41_by_twilight-dreams-scans
class Batoto extends Manga_Crawler {
	protected $enable_single_chapter = true;
	protected $column_span = 3;
	
	// need to be overridden, return array[desc,url,infix]
	// $base is URL submitted
	public function extract_info($base) {
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
	
	// must be overridden, echo html of links
	// $v contain [url,desc,infix]
	public function crawl_chapter($v) {
		$ifx = Text::create($v['infix'])->pad(3)->to_s();
		$p = new Page($v['url']);
		// grab list of pages
		$p->go_line('id="page_select"');
		$pages = $p->next_line()->extract_to_array('value="', '"');
		// grab current image
		$this->crawl_page($p, $ifx);
		
		array_shift($pages);
		foreach ($pages as $purl) {
			$p = new Page($purl);
			$this->crawl_page($p, $ifx);
		}
		/*
		Manga_Crawler::multiProcess(4, $pages, array($this, 'crawl_page'), array($ifx));
		*/
	}
	
	public function crawl_page($p, $ifx) {
		$prefix = $this->prefix;
		$p->go_line('id="full_image"');
		$img = $p->next_line(3)->dup()->cut_between('src="', '"')->to_s();
		$iname = urldecode(basename($img));
		// 12 karakter aneh
		if (preg_match('/[0-9a-z]{13}\.\w+$/', $iname)) {
			$iname = preg_replace('/\w{13}\.(\w+)$/', '.$1', $iname);
		}
		if (preg_match('/_(\d+)_[a-zA-Z]+\.\w{3}$/', $iname, $m)) {
			$iname = $m[1].substr($iname, -4);
		} else {
			// ambil last 3 character
			$iname = substr($iname, -7);
		}
		echo "<li><a href='$img'>$prefix-$ifx-$iname</a></li>\n";
	}
	
	public function url_is_single_chapter($url) {
		return strpos($url, '/read/_') !== false;
	}
}
Batoto::factory()->run();
