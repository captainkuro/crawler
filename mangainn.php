<?php
// http://www.mangainn.com/manga/2373_yamada-kun-to-7-nin-no-majo
// http://www.mangainn.com/manga/chapter/78967_let-s-try-it
class Mangainn extends Manga_Crawler {
	protected $enable_single_chapter = true;
	
	public function url_is_single_chapter($url) {
		return (bool)preg_match('/\/chapter\/\d+/', $url);
	}
	
	// need to be overridden, return array[desc,url,infix]
	// $base is URL submitted
	public function extract_info($base) {
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
	
	// must be overridden, echo html of links
	// $v contain [url,desc,infix]
	public function crawl_chapter($v) {
		$ifx = Text::create($v['infix'])->pad(3)->to_s();
		$p = new Page($v['url']);
		// grab list of pages
		$p->go_line('id="cmbpages"');
		$pages = $p->curr_line()->extract_to_array('value="', '"');
		// grab current image
		$this->crawl_page($p, $ifx);
		array_shift($pages);
		// grab the rest of pages
		foreach ($pages as $i => $page) {
			// $pages[$i] = $v['url'].'/page_'.$page;
			$p = new Page($v['url'].'/page_'.$page);
			$this->crawl_page($p, $ifx);
		}
		// Manga_Crawler::multiProcess(4, $pages, array($this, 'crawl_page'), array($ifx));
	}
	
	public function crawl_page($p, $ifx) {
		$prefix = $this->prefix;
		$p->go_line('id="imgPage"');
		$img = $p->next_line()->dup()->cut_between('src="', '"')->to_s();
		$iname = urldecode(basename($img));
		// 001_02_22_2012_10_46_59.jpg jadi 001.jpg
		preg_match('/^([^_]+).*\.(\w+)$/', $iname, $m); // $m[1] no urut, $m[2] extension
		$iname = Text::create($m[1])->pad(3)->to_s().'.'.$m[2];
		echo "<a href='$img'>$prefix-$ifx-$iname</a><br/>\n";
	}
	
	public function grab_chapter_infix($url) {
		$p = new Page($url);
		$p->go_line('id="gotoMangaInfo"');
		$m = $p->curr_line()->regex_match('/Chapter (\w*)<\//');
		return $m[1];
	}
}
Mangainn::factory()->run();