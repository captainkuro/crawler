<?php
// http://www.batoto.net/comic/_/comics/xblade-r789
// http://www.batoto.net/read/_/27286/xblade_ch41_by_twilight-dreams-scans
class Batoto_Crawler implements Manga_Crawler {

	public function __construct() {
		$this->p = new Page(null, array(
			CURLOPT_COOKIE => "__cfduid=d465669ccb6295c41a5a2bd907adc7a561483287224; member_id=198664; pass_hash=b809f4145b905e70ea15297e4aae188e; ipsconnect_d8874f8d538b1279c8106e636bf7afe9=1; coppa=0; session_id=ae177802e27bad86474e2cde8e852753; rteStatus=rte
",
			CURLOPT_REFERER => 'http://bato.to/reader',
			'become_firefox' => true,
			CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest', 'Accept-Language: en-US,en;q=0.5'],
		));
	}

	public function is_supported($url) {
		return strpos($url, 'http://www.batoto.net/') !== false
			|| strpos($url, 'http://bato.to/') !== false;
	}

	public function is_single_chapter($url) {
		return strpos($url, '/read/_') !== false;
	}

	public function get_infix($url) {
		if (preg_match('/ch(\d+)/', $url, $m)) {
			return $m[1];
		} else {
			return '';
		}
	}

	public function get_info($base) {
		// crawl chapters
		$p = $this->p;
		$p->fetch_url($base);
		$h = new simple_html_dom();
		$h->load($p->content());

		$table = $h->find('.chapters_list', 0);
		$list = array();
		foreach ($table->find('.lang_English') as $tr) {
			$a = $tr->find('a', 0);
			$href = $a->href;
			$desc = $a->text();
			preg_match('/h\.(\d+):?/', $desc, $m);
			$infix = $m[1];
			$group = $tr->find('td', 2)->text();
			$list[] = array(
				'url' => $href,
				'desc' => $desc.' by '.$group,
				'infix' => $infix,
			);
		}
		$p->close();
		unset($a); unset($href); unset($desc); unset($infix); unset($group);
		unset($table); unset($tr); unset($h);
		gc_collect_cycles();
		$this->p = $p;
		return $list;
	}

	public function get_images($chapter_url, $prefix, $infix) {
		$urls = $this->get_areader($chapter_url);
		$ifx = Text::create($infix)->pad(3)->to_s();

		$result = array();
		foreach ($urls as $i => $page_url) {
			$this->p->fetch_url($page_url);
			$result = $result + $this->crawl_page($this->p, $prefix, $ifx, $i+1);
		}

		return $result;
	}

	private function get_areader($chapter_url) {
		$id = parse_url($chapter_url, PHP_URL_FRAGMENT);
		$url_pattern = "http://bato.to/areader?id={$id}&p=%s";
		$this->p->fetch_url(sprintf($url_pattern, 1));
// file_put_contents('batoto.debug', $this->p->content()); //debug
		$h = new simple_html_dom();
		$h->load($this->p->content());

		$m = Text::create($h->find('#page_select', 0)->last_child()->text())
			->regex_match('#page (\d+)#');
		$n = $m[1];

		$result = array();
		for ($i=1; $i<=$n; $i++) {
			$result[] = sprintf($url_pattern, $i);
		}
		return $result;
	}
	
	public function crawl_page($p, $prefix, $ifx, $i) {
		$p->go_line('id="full_image"');
		$img = $p->next_line(3)->dup()->cut_between('src="', '"')->to_s();
		$iname = urldecode(basename($img));
		$ext = pathinfo($iname, PATHINFO_EXTENSION);
		// // 12 karakter aneh
		// if (preg_match('/[0-9a-z]{13}\.\w+$/', $iname)) {
		// 	$iname = preg_replace('/\w{13}\.(\w+)$/', '.$1', $iname);
		// }
		// if (preg_match('/_(\d+)_[a-zA-Z]+\.\w{3}$/', $iname, $m)) {
		// 	$iname = $m[1].substr($iname, -4);
		// } else {
		// 	// ambil last 3 character
		// 	$iname = substr($iname, -7);
		// }
		$iname = Text::create($i)->pad(3).'.'.$ext;
		return array("$prefix-$ifx-$iname" => $img);
	}
	
}
