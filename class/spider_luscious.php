<?php
//require_once '../crawler.php';

class Spider_Luscious {
	public $url;
	
	public function __construct($url) {
		if (!Crawler::is_there($url, '/page/1')) {
			$url = Crawler::cutuntillast($url, '/') . '/page/1';
		}
		$this->url = $url;
	}
	
	public function go() {
		$mark1 = '<a target="_blank" title="Show fullsized image" href=';
		$mark2 = '<a title="Next Image" rel="next" href=';
		$host = 'http://lu.scio.us';
		$finish = false;
		$number = 0;
		$url = $this->url;
		preg_match('/\/([^\/]+)\/page\/1/', $url, $m);
		$text = $m[1];
		while (!$finish) {
			echo $url."<br/>\n";flush();
			$c = new Crawler($url);
			$c->go_to('id="pid_');
			while ($line = $c->readline()) {
				if (Crawler::is_there($line, 'src="')) {
					$img = Crawler::extract($line, 'src="', '"');
					$img = str_replace('thumb_100_', @$_GET['big'] ? '' : 'normal__', $img);
					$num = Crawler::pad(++$number, 3);
					$filnm = basename($img);
					$ext = Crawler::cutafter($filnm, '.');
					// $text = $num . $ext;
					// preg_match('/\/(\d+\/\d+)\//', $img, $m);
					// $text = $m[1];
					echo "<a href='$img'>$text</a><br/>\n";flush();
				} else if (Crawler::is_there($line, '</ul>')) {
					break;
				}
			}
			$c->go_to('class="pager"');
			$c->readline();
			if (Crawler::is_there($c->curline, 'Pager_next')) {
				$finish = false;
				$url = $host . Crawler::extract($c->curline, '<a rel="next" href="', '"');
			} else {
				$finish = true;
			}
			$c->close();
		}
	}
}