<?php

class Rule34xxx_Downloader implements ADownloader {
	private $default_dir = 'D:\Temp\onion';

	public function display() {
		return 'Rule34 XXX';
	}

	public function download () {
		echo "List URL: ";
		$list_url = trim(fgets(STDIN));
		echo "Save Dir [{$this->default_dir}]: ";
		$dir = trim(fgets(STDIN));

		$dir = $this->prepare_dir($dir, $list_url);
		$this->collect_images($list_url, $dir);
	}

	private function create_dom($url) {
		echo "Opening {$url}\n";
		$p = new Page($url);
		$h = new simple_html_dom();
		$h->load($p->content());
		return $h;
	}

	private function prepare_dir($dir, $list_url) {
		$dir = $dir ? $dir : $this->default_dir;
		$tags = Text::create($list_url)
			->cut_after('tags=')
			->to_s();

		$new_dir = rtrim($dir, '/') . '/' . $tags . '/';
		if (!is_dir($new_dir)) {
			mkdir($new_dir);
		}
		return $new_dir;
	}

	private function collect_images($url, $dir) {
		$continue = true;
		$domain = 'http://rule34.xxx/';
		$base = 'http://rule34.xxx/index.php';
		do {
			echo $url."\n";
			$p = new Page($url);
			$p->go_line('class="thumb"');
			do { if ($p->curr_line()->contain('href="')) {
				$href = $p->curr_line()
					->cut_between('href="', '"')
					->to_s();
				$href = htmlspecialchars_decode($href);
				echo "$domain$href\n";
				$p2 = new Page($domain . $href);
				$p2->go_line('Original image');
				$src = $p2->curr_line()
					->cut_between('href="http:', '"')
					->to_s();
				$src = 'http:' . $src;
				$outpath = $dir . basename($src);
				download_it($src, $outpath, "--header=\"Accept: image/*\"");
	// echo '<pre>'.htmlspecialchars($p2->curr_line()).'</pre>';
			}} while (!$p->next_line()->contain('<center>'));
			$p->reset_line();
			$p->go_line('id="paginator"');
			if ($p->curr_line()->contain('alt="next"')) {
				$m = $p->curr_line()->regex_match('/href="([^"]+)" alt="next"/');
				$url = $base.html_entity_decode($m[1]);
			} else {
				$continue = false;
			}
		} while ($continue);
	}

}