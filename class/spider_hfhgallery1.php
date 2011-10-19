<?php
//require_once '../crawler.php';

class Spider_Hfhgallery1 {
	public $url;
	
	public function __construct($url) {
		$this->url = $url;
	}
	
	public function go() {
		$start_url = $this->url;
		$base = 'http://gallery.hentaifromhell.net';
		$selesai = false;
		while (!$selesai) {
			$craw = new Crawler($start_url);
			$craw->go2linewhere('showimg.php?c=');
			
			while ($line = $craw->readline()) {
				if (strpos($line, 'showimg.php?c=') !== false) {
					$raw = Crawler::extract_to_array($line, '<a href="', '"');
					foreach ($raw as $r) {
						$href = str_replace('showimg.php?c=', '', $r);
						$text = basename(dirname($href)); // basename($href);
						echo '<a href="'.$href.'">'.$text.'</a>'."<br />\n";
					}
					// $href = Crawler::extract($line, '<a href="', '"');
				} else if (strpos($line, 'Next&raquo;') !== false) {
					if (strpos($line, '<a href') !== false) {
						$start_url = $base.Crawler::extract($line, '<a href="', '"');
					} else {
						$selesai = true;
					}
					break;
				}
			}
			$craw->close();
		}
	}
}