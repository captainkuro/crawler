<?php
/* Foolslide
http://mangacurse.info/reader/reader/series/soul_eater/
http://manga.redhawkscans.com/reader/series/hayate_no_gotoku/
http://reader.imperialscans.com/reader/series/historys_strongest_disciple_kenichi/
http://manga.redhawkscans.com/reader/read/hayate_no_gotoku/en/0/26/
*/
class Foolslide_Crawler implements Manga_Crawler {
	
	public function is_supported($url) {
		return strpos($url, '/reader/series/') !== false
			|| strpos($url, '/reader/read/') !== false
			|| preg_match('/reader\.[^\/]+\/series\//', $url)
			|| preg_match('/reader\.[^\/]+\/read\//', $url);
	}

	public function is_single_chapter($url) {
		return (int)basename($url) > 0;
	}

	public function get_infix($url) {
		if (preg_match('/\/0\/([\d\/]*)/', $url, $m)) {
			$chunk = trim($m[1], '/');
			return str_replace('/', '.', $chunk);
		} else if (preg_match('/\/en\/\d+\/([\d\/]*)/', $url, $m)) {
			$chunk = trim($m[1], '/');
			return str_replace('/', '.', $chunk);
		} else {
			return '';
		}
	}

	public function get_info($base) {
		// crawl chapters
		$p = new Page($base);
		$p->go_line('class="list"');
		$list = array();
		do {if ($p->curr_line()->contain('class="title"') && $p->curr_line()->contain('title=')) {
			$line = $p->curr_line()->dup();
			$href = $line->dup()->cut_between('href="', '"')->to_s();
			$desc = $line->dup()->cut_between('title="', '">')->to_s();
			$infix = $this->get_infix($href);
			$list[] = array(
				'url' => $href,
				'desc' => $desc,
				'infix' => $infix,
			);
		}} while (!$p->next_line()->contain('</article>'));
		return $list;
	}
	
	public function get_images($chapter_url, $prefix, $infix) {
		$ifx = Text::create($infix)->pad(3)->to_s();
		$p = new Page($chapter_url);
		// grab list of pages
		$p->go_line('="changePage(');
		$pages = $p->curr_line()->extract_to_array('href="', '"');
		// grab current image
		$result = $this->crawl_page($p, $prefix, $ifx);
		
		array_shift($pages);
		foreach ($pages as $purl) {
			$result = $result + $this->crawl_page(new Page($purl), $prefix, $ifx);
		}
		return $result;
	}
	
	public function crawl_page($p, $prefix, $ifx) {
		$p->go_line('class="open"');
		$img = $p->curr_line()->dup()->cut_between('src="', '"')->to_s();
		$iname = urldecode(basename($img));
		
		$name = "$prefix-$ifx-$iname";
		return array($name => $img);
	}
	
}
