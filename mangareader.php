<?php
require 'crawler.php';
class Mangareader extends Manga_Crawler {
	private $sitename = 'http://www.mangareader.net';
	protected $enable_single_chapter = true;
	
	// need to be overridden, return array[desc,url,infix]
	// $base is URL submitteds
	public function extract_info($base) {
		echo '<tr><td colspan="2">Progress.. ';
		$c = new Crawler($base);
		$c->go_to('id="listing"');
		$list = array();
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, 'class="chico_')) {
				if (!Crawler::is_there($line, ' href="')) $line = $c->readline();
				$chp = Crawler::extract($line, 'href="', '"');
				$ifx = Crawler::cutfromlast1($chp, '/');
				$ifx = str_replace('chapter-', '', $ifx);
				$ifx = str_replace('.html', '', $ifx);
				$list[] = array(
					'url' => $this->sitename . $chp,
					'infix' => $ifx,
					'desc' => strip_tags(Crawler::extract($line, ': ', '</td>')),
				);
				echo $ifx.'.. ';
			} else if (Crawler::is_there($line, '</table>')) {
				break;
			}
		}
		$c->close();
		echo 'End</td></tr>';
		return $list;
	}
	
	// must be overridden, echo html of links
	// $v contain [url,desc,infix]
	public function crawl_chapter($v) {
		$c = new Crawler($v['url']);
		$c->go_to('id="pageMenu"');
		$pages = array();
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, '<option')) {
				$pages[] = $this->sitename . Crawler::extract($line, 'value="', '"');
			} else if (Crawler::is_there($line, '</select>')) {
				break;
			}
		}
		//$pages = Crawler::extract_to_array($c->curline, 'value="', '"');
		$c->close();
		
		Crawler::multiProcess(4, $pages, array($this, 'mangareader_1_page'), array($v['infix']));
	}
	
	public function mangareader_1_page($fil, $url, $chapter) {
		$prefix = $this->prefix;
		$chapter = Crawler::pad($chapter, 3);
		$c = new Crawler($fil);
		$c->go_to('width="800"');
		$img = $c->getbetween('src="', '"');
		// if (@$_GET['show_url']) echo "<a href='$url'>URL</a> ";
		echo '<a href="'.$img.'">'.$prefix.'-'.$chapter.'-'.basename($img).'</a>'."<br/>\n";
		$c->close();
	}
	
	public function url_is_single_chapter($url) {
		return (bool)preg_match('/\/\d+$/', $url);
	}
}

$m = new Mangareader();
$m->run();
