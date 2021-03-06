<?php
require 'crawler.php';
class Mangareader extends Manga_Crawler {
	private $sitename = 'http://www.mangareader.net';
	protected $enable_single_chapter = true;
	protected $column_span = 3;
	protected $reverse_choice_chapters = true;
	protected $reverse_download_chapters = true;
	
	public function display_header() {
		if (isset($this->base) && strpos($this->base, 'mangapanda.com') !== false) {
			$this->sitename = 'http://www.mangapanda.com';
		}
		parent::display_header();
	}
	
	// need to be overridden, return array[desc,url,infix]
	// $base is URL submitted
	public function extract_info($base) {
		echo '<tr><td colspan="3">Progress.. ';
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
		// $pages = Crawler::extract_to_array($c->curline, 'value="', '"');
		$c->close();
		
		echo '<ul>';
		// Crawler::multiProcess(4, $pages, array($this, 'mangareader_1_page'), array($v['infix']));
		foreach ($pages as $page) {
			$this->mangareader_1_page($page, $page, $v['infix']);
		}
		echo '</ul>';
	}
	
	public function mangareader_1_page($fil, $url, $chapter) {
		$prefix = $this->prefix;
		$chapter = Crawler::pad($chapter, 3);
		$c = new Crawler($fil);
		$c->go_to('width="800"');
		$img = $c->getbetween('src="', '"');
		// if (@$_GET['show_url']) echo "<a href='$url'>URL</a> ";
		preg_match('/(\d+\.\w+)$/', basename($img), $m);
		$iname = $m[1];
		echo '<li><a href="'.$img.'">'.$prefix.'-'.$chapter.'-'.$iname.'</a>'."</li>\n";
		$c->close();
	}
	
	public function url_is_single_chapter($url) {
		return (bool)preg_match('/\/\d+$/', $url);
	}
	
	public function grab_chapter_infix($url) {
		preg_match('/\/([^\/]*)$/', $url, $m);
		return $m[1];
	}
}

Mangareader::factory()->run();
