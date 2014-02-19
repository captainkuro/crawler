<?php
// http://mangahead.com/Manga-Raw-Scan/Denpa-Kyoushi
// http://mangahead.com/Manga-English-Scan/Detective-Conan
// http://mangahead.com/Manga-Raw-Scan/Denpa-Kyoushi/Denpa-Kyoushi-104-Raw-Scan
// http://mangahead.com/Manga-English-Scan/Detective-Conan/Detective-Conan-880-English-Scan
class Mangahead_Crawler implements Manga_Crawler {

	public function is_supported($url) {
		return strpos($url, 'http://mangahead.com/') !== false;
	}

	public function is_single_chapter($url) {
		return substr_count($url, '/') == 5;
	}

	public function get_infix($url) {
		if (preg_match('/-(\d+)-[\w]+-[\w]+$/', $url, $m)) {
			return $m[1];
		} else {
			return '';
		}
	}

	public function get_info($base) {
		$domain = 'http://mangahead.com';
		$p = new Page($base);
		$p->go_line('class="mangaviewer_toppest_navig"');
		$hrefs = $p->curr_line()->extract_to_array('href="', '"');

		$list = array();
		foreach ($hrefs as $href) {
			$list[] = array(
				'url' => $domain.$href,
				'desc' => basename($href),
				'infix' => $this->get_infix($domain.$href),
			);
		}
		return $list;
	}

	public function get_images($chapter_url, $prefix, $infix) {
		$ifx = Text::create($infix)->pad(3)->to_s();

		$sitename = "http://mangahead.com";
		$pref = Text::create($chapter_url);
		if (!$pref->contain('index.php')) {
			$pref = $pref->replace($sitename.'/Manga', $sitename.'/index.php/Manga');
		}
		$finish = false;
		if ($pref->contain('?page=')) {
			$page = (int)$pref->cut_after('?page=')->to_s();
			$pref = $pref->cut_until('?page=');
		} else {
			$page = 1;
		}
		$pages = array();
		while (!$finish) {
			// file_put_contents('/tmp/head', $chapter_url."\n", FILE_APPEND);
			$p = new Page($chapter_url);
			$p->go_line('<blockquote>');
			if ($p->curr_line()->contain('&nbsp;&nbsp;&rsaquo;')) {
				$finish = false;
				$chapter_url = $pref . '/?page=' . (++$page);
			} else {
				$finish = true;
			}
			
			$srcs = $p->curr_line()->extract_to_array('<img src="', '"');
			foreach ($srcs as $src) {
				$parturl = Text::create($src)
					->replace('index.php', 'mangas')
					->replace('?action=thumb', '')
					->to_s();
				$name = basename($parturl);
				$pages["$prefix-$ifx-$name"] = $sitename.$parturl;
			}
		}
		return $pages;
	}
}