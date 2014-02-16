<?php
// http://mangafox.me/manga/fairy_tail/
// http://mangafox.me/manga/fairy_tail/v34/c313/
class Mangafox extends Manga_Crawler {
	protected $enable_single_chapter = true;
	protected $reverse_download_chapters = true;
	
	// need to be overridden, return array[desc,url,infix]
	// $base is URL submitted
	public function extract_info($base) {
		$p = new Page($base);
		$p->go_line('id="chapters"');
		$list = array();
		$item = array();
		do {
			$line = $p->curr_line();
			if ($line->contain('class="tips"')) {
				$item['url'] = $line->cut_between('href="', '"')->to_s();
				preg_match('/\/c([^\/]+)/', $item['url'], $m);
				$item['infix'] = $m[1];
				$item['desc'] = $line->cut_between('tips">', '</')->to_s();
			} else if ($line->contain('title nowrap')) {
				$item['desc'] .= ': '.$line->cut_between('nowrap">', '</')->to_s();
				$list[] = $item;
			}
		} while (!$p->next_line()->contain('id="discussion"'));
		return $list;
	}

	// must be overridden, echo html of links
	// $v contain [url,desc,infix]
	public function crawl_chapter($v) {
		$ifx = Text::create($v['infix'])->pad(3)->to_s();
		$prefix = $this->prefix;
		$p = new Page($v['url']);
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
		echo "<a href='$src'>$prefix-$ifx-$name</a><br>\n";
		// iterate
		for ($i=2; $i<=$tot; $i++) {
			$p = new Page(dirname($v['url']).'/'.$i.'.html');
			$p->go_line('id="viewer"');
			$p->next_line(2);
			$src = $p->curr_line()->cut_between('src="', '"');
			$name = basename($src);
			echo "<a href='$src'>$prefix-$ifx-$name</a><br>\n";
		}
	}

	public function url_is_single_chapter($url) {
		return (bool)preg_match('/\/c\d+\//', $url);
	}
}
Mangafox::factory()->run();
