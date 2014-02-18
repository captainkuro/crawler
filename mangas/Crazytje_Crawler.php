<?php
// http://crazytje.be/Manga/48509610-dea8-436e-9230-772c425a6735
// http://crazytje.be/reader/show/4f5b32ef-5890-4f42-9682-39a9d0359e0c/0
class Crazytje_Crawler implements Manga_Crawler {

	public function is_supported($url) {
		return strpos($url, 'http://crazytje.be/') !== false;
	}

	public function is_single_chapter($url) {
		return preg_match('/\/0$/', $url);
	}

	public function get_infix($url) {
		return '';
	}

	public function get_info($base) {
		$sitename = "http://crazytje.be";
		$p = new Page($base);
		$p->go_line('latestreleases');
		$chapters = array();
		$tempurl = null;
		$tempdesc = null;
		$v = 1;
		do {
			$line = $p->curr_line();
			if ($line->contain('class="readonlinetext"><a href="')) {
				$tempurl = $line->cut_between('class="readonlinetext"><a href="', '"');
			} else if ($line->contain("class='description2'") && $tempurl) {
				$tempdesc = $line->cut_between("class='description2'>", '</div>');
				if ($m = $tempdesc->regex_match('/ch?(\d+)/')) {
					$infix = $m[1];
				} else if ($m = $tempdesc->regex_match('/v(\d+)/')) {
					$infix = $m[1];
				} else {
					$infix = $v;
				}
				$chapters[] = array(
					'url' => $sitename . $tempurl->to_s(),
					'desc' => $tempdesc->to_s(),
					'infix' => $infix,
				);
				$tempurl = null;
				$tempdesc = null;
				$v++;
			}
		} while (!$p->next_line()->contain('</table>'));
		return $chapters;
	}

	public function get_images($chapter_url, $prefix, $infix) {
		$ifx = Text::create($infix)->pad(3)->to_s();
		$p = new Page($chapter_url);

		// ada dua kemungkinan, 1 vol berisi n chap atau 1 chap saja
		$p->go_line_or(array('data[volumechapter]', 'data[pages]'));
		$result = array();
		if ($p->curr_line()->contain('data[volumechapter]')) {
			$volchaps = array();
			do {
				$line = $p->curr_line();
				if ($line->contain('</option>')) {
					$volchaps[$line->cut_between('value="', '"')->to_s()] = $line->cut_between('>', '</option')->to_s();
				}
			} while (!$p->next_line()->contain('</select><br'));
			// traverse per chapter
			foreach ($volchaps as $key2 => $val2) {
				$url2 = dirname($chapter_url).'/'.$key2;
				$result = $result + $this->crawl_page($url2, $prefix, $ifx);
			}

		} else if ($p->curr_line()->contain('data[pages]')) {
			$result = $result + $this->crawl_page($chapter_url, $prefix, $ifx);
		}
		return $result;
	}

	public function crawl_page($url, $prefix, $ifx) {
		$p = new Page($url);
		$p->go_line('data[pages]');
		$pages = array();
		do {
			$line = $p->curr_line();
			if ($line->contain('</option>')) {
				$pages[] = $line->cut_between('>', '</option')->to_s();
			}
		} while (!$p->next_line()->contain('</select>'));
		$p->go_line('scanlations');
		$imgurl = $p->curr_line()->cut_between('<img src="', '"')->to_s();
		$imgbase = dirname($imgurl);
		$result = array();
		foreach ($pages as $page) {
			$result["{$prefix}-{$ifx}-{$page}"] = "{$imgbase}/{$page}";
		}
		return $result;
	}

}
