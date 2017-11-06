<?php
// http://mangastream.com/manga/one_piece
// http://mangastream.com/read/one_piece/713/1967/1
class Mangastream_Crawler implements Manga_Crawler {

	public function is_supported($url) {
		return strpos($url, 'http://mangastream.com/') !== false
			|| strpos($url, 'https://mangastream.com/') !== false
			|| strpos($url, 'http://readms.com/') !== false
			|| strpos($url, 'http://readms.net/') !== false;
	}

	public function is_single_chapter($url) {
		return strpos($url, '/r/') !== false;
	}

	public function get_infix($url) {
		if (preg_match('/(\d+)\/\d+\/1\??/', $url, $m)) {
			return $m[1];
		} else {
			return '';
		}
	}

	public function get_info($base) {
		// crawl chapters
		$p = new Page($base);
		$p->go_line('<table class="table table-striped">');
		
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

	public function get_images($chapter_url, $prefix, $infix) {
		$ifx = Text::create($infix)->pad(3)->to_s();
		$p = new Page($chapter_url);
		// grab list of pages
		$p->go_line('Last Page (');
		$n = $p->curr_line()->cut_between('Last Page (', ')')->to_s();
		$dir_url = dirname($chapter_url);
		// grab current image
		$result = array();
		list($img_name, $img_url) = $this->crawl_page($p, $prefix, $ifx, 1);
		$result[$img_name] = $img_url;
		for ($i=2; $i<=$n; $i++) {
			try {
				$p = new Page($dir_url.'/'.$i);
				list($img_name, $img_url) = $this->crawl_page($p, $prefix, $ifx, $i);
				$result[$img_name] = $img_url;
			} catch (Exception $e) {
				echo "Unable to open: $dir_url/$i<br>";
			}
		}
		return $result;
	}

	public function crawl_page($p, $prefix, $ifx, $i) {
		$p->go_line('id="manga-page"');
		$img = $p->curr_line()->cut_between('src="', '"')->to_s();
		$iname = urldecode(basename($img));
		$ext = pathinfo($iname, PATHINFO_EXTENSION);
		$iname = Text::create($i)->pad(3).'.'.$ext;
		
		return array("$prefix-$ifx-$iname", $img);
	}
	
}
