<?php
/* Foolslide
http://mangacurse.info/reader/reader/series/soul_eater/
http://manga.redhawkscans.com/reader/series/hayate_no_gotoku/
http://reader.imperialscans.com/reader/series/historys_strongest_disciple_kenichi/
*/
class Foolslide extends Manga_Crawler {
	protected $enable_single_chapter = true;
	
	// need to be overridden, return array[desc,url,infix]
	// $base is URL submitteds
	public function extract_info($base) {
		// crawl chapters
		$p = new Page($base);
		$p->go_line('class="list"');
		$list = array();
		do {if ($p->curr_line()->contain('class="title"') && $p->curr_line()->contain('title=')) {
			$line = $p->curr_line()->dup();
			$href = $line->dup()->cut_between('href="', '"')->to_s();
			$desc = $line->dup()->cut_between('title="', '">')->to_s();
			$infix = basename($href);
			$list[] = array(
				'url' => $href,
				'desc' => $desc,
				'infix' => $infix,
			);
		}} while (!$p->next_line()->contain('</article>'));
		return $list;
	}
	
	// must be overridden, echo html of links
	// $v contain [url,desc,infix]
	public function crawl_chapter($v) {
		$ifx = Text::create($v['infix'])->pad(3)->to_s();
		$p = new Page($v['url']);
		// grab list of pages
		$p->go_line('="changePage(');
		$pages = $p->curr_line()->extract_to_array('href="', '"');
		// grab current image
		$this->crawl_page($p, $ifx);
		
		array_shift($pages);
		foreach ($pages as $purl) {
			$this->crawl_page(new Page($purl), $ifx);
		}
	}
	
	public function crawl_page($p, $ifx) {
		$prefix = $this->prefix;
		$p->go_line('class="open"');
		$img = $p->curr_line()->dup()->cut_between('src="', '"')->to_s();
		$iname = urldecode(basename($img));
		echo "<a href=\"$img\">$prefix-$ifx-$iname</a><br/>\n";
	}
	
	public function url_is_single_chapter($url) {
		return strpos($url, '/page/1') !== false;
	}
}
$f = new Foolslide();
$f->run();
