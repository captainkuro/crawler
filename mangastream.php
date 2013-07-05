<?php
// http://mangastream.com/manga/one_piece
// http://mangastream.com/read/one_piece/713/1967/1
class Mangastream extends Manga_Crawler {
	protected $enable_single_chapter = true;
	
	// need to be overridden, return array[desc,url,infix]
	// $base is URL submitted
	public function extract_info($base) {
		// crawl chapters
		$p = new Page($base);
		$p->go_line('<table class="table table-striped">');
		// print_r($p);exit;//debug
		$list = array();
		do {
			if ($p->curr_line()->contain('href="')) {
				$line = $p->curr_line();
				$href = $line->cut_between('href="', '"');
				$desc = $line->cut_between('">', '</a');
				$infix = $desc->regex_match('/(\d+)/');
				$infix = $infix[1];
				
				$list[] = array(
					'url' => $href->to_s(),
					'desc' => $desc->to_s(),
					'infix' => $infix,
				);
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
		$p->go_line('Last Page (');
		$n = $p->curr_line()->cut_between('Last Page (', ')')->to_s();
		$dir_url = dirname($v['url']);
		// grab current image
		$this->crawl_page($p, $ifx);
		for ($i=2; $i<$n; $i++) {
			$p = new Page($dir_url.'/'.$i);
			$this->crawl_page($p, $ifx);
		}
	}
	
	public function crawl_page($p, $ifx) {
		$prefix = $this->prefix;
		$p->go_line('id="manga-page"');
		$img = $p->curr_line()->cut_between('src="', '"')->to_s();
		$iname = urldecode(basename($img));
		// 12 karakter aneh
		echo "<li><a href='$img'>$prefix-$ifx-$iname</a></li>\n";
	}
	
	public function url_is_single_chapter($url) {
		return strpos($url, '/read/') !== false;
	}

	public function grab_chapter_infix($url) {
		preg_match('/(\d+)\/\d+\/1$/', $url, $m);
		return $m[1];
	}
}
Mangastream::factory()->run();
