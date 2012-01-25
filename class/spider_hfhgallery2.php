<?php
//require_once '../crawler.php';

class Spider_Hfhgallery2 {
	public $url;
	
	public function __construct($url) {
		$this->url = $url;
	}
	
	public function extract_page($url) {
		echo $url, "<br />\n";flush();
		$c = new Crawler($url);
		$c->go2linewhere('<a accesskey="v"');
		$h = $c->getbetween('<img src="', '"');
		echo '<a href="'.$h.'">'.basename($h).'</a>'."<br />\n";
		flush();
		$c->close();
	}
	
	public function go() {
		$start_url = $this->url;
		if (preg_match('/gallery1\.hentaifromhell\.net/', $start_url)) {
			$base_url = 'http://gallery1.hentaifromhell.net';
		} else {
			$base_url = 'http://gallery.hentaifromhell.net';
		}
		$finish = false;
		while (!$finish) {
			$finish = true;
			echo $start_url, "<br />\n";flush();
			$c = new Crawler($start_url);
			$c->go2linewhere('<li class="thumbnail">');
			while ($line = $c->readline()) {
				//echo "<pre>$line</pre><br/>\n";flush();
				if (strpos($line, 'src="') !== false) {
					//ambil gambar
					$uri = Crawler::extract($line, 'src="', '"');
					$uri = str_replace('/thumbs/', '/images/', $uri);
					preg_match('/(\\/small\\/\\d+-)/', $uri, $matches);
					$uri = str_replace($matches[1], '/', $uri);
					//$uri = html_entity_decode($uri);
					//$this->extract_page($uri);
					$file = basename(dirname($uri));
					echo "<a href='$uri'>$file</a><br/>\n";flush();
				} else if (strpos($line, 'class="pagNext"') !== false) {
					//next page
					$finish = false;
					$start_url = html_entity_decode(Crawler::extract($line, 'class="pagNext" href="', '"'));
					break;
				} else if (strpos($line, '</table>') !== false) {
					// selesai
					break;
				}
			}
			$c->close();
		}
	}
}