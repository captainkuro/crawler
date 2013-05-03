<?php
//require_once '../crawler.php';

class Spider_Hfhgallery3 {
	public $url;
	public $blacklist = array(
		'http://img125.imagevenue.com',
	);
	
	public function __construct($url) {
		$this->url = $url;
	}
	
	public function go() {
		$start_url = $this->url;
		$c = new Crawler($start_url);
		$c->go2linewhere('<p><a href="');
		$c->close();
		$ledak = explode('<a href="', $c->curline);
		for ($i=1; $i<count($ledak); ++$i) {
			$aurl = Crawler::cutuntil($ledak[$i], '"');
			$aurl = str_replace('http://hentaifromhell.net/redirect.html?', '', $aurl);
			echo "<a href='$aurl'>$aurl</a><br />\n";flush();
			/*
			$basename = Crawler::cutuntillast($aurl, '/');
			if (!in_array($basename, $this->blacklist)) {
				$c = new Crawler($aurl);
				$c->go2linewhere('id="thepic"');
				$imgurl = $c->getbetween('SRC="', '"');
				$c->close();
				echo "<a href='$basename/$imgurl'>".Crawler::n($i,3).".jpg</a><br />\n";
				flush();
			} else {
				echo "$i blacklisted server<br/>";flush();
			}
			*/
		}
	}
}