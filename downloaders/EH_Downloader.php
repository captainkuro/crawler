<?php

class EH_Downloader implements ADownloader {
	private $default_dir;

	public function display() {
		return 'E-H Gallery';
	}

	public function download () {
		$this->default_dir = DConfig::p_folder();
		
		echo "Gallery URL: ";
		$gallery_url = trim(fgets(STDIN));
		echo "Save Dir [{$this->default_dir}]: ";
		$dir = trim(fgets(STDIN));

		$dir = $this->prepare_dir($dir, $gallery_url);
		$images = $this->collect_images($gallery_url);
		$this->download_images($images, $dir);
	}

	private function create_dom($url) {
		echo "Opening {$url}\n";
		$p = new Page($url, array(CURLOPT_COOKIE => 'nw=1'));
		$h = new simple_html_dom();
		$h->load($p->content());
		return $h;
	}

	private function prepare_dir($dir, $gallery_url) {
		$dir = $dir ? $dir : $this->default_dir;
		$h = $this->create_dom($gallery_url);
		
		$title = $h->find('title', 0)->innertext;
		$filtered = preg_replace('/[^\w \-]/', ' ', $title);
		$filtered = preg_replace('/  +/', ' ', $filtered);
		$filtered = str_replace(' - E-Hentai Galleries', '', $filtered);
		$filtered = substr(trim($filtered), 0, 100);
		$new_dir = rtrim($dir, '/') . '/' . $filtered . '/';
		if (!is_dir($new_dir)) {
			mkdir($new_dir);
		}
		return $new_dir;
	}

	private function collect_images($gallery_url) {
		$images = array();
		$active = true;
		while ($active) {
			$h = $this->create_dom($gallery_url);

			$thumbnails = $h->find('.gdtm');
			foreach ($thumbnails as $thumb) {
				$title = $thumb->find('img', 0)->title;
				$href = $thumb->find('a', 0)->href;
				$images[$title] = $href;
			}

			$pagination = $h->find('.ptt tr', 0);
			$next = $pagination->last_child()->find('a', 0);
			if ($next) {
				$gallery_url = $next->href;
			} else {
				$active = false;
			}
		}
		return $images;
	}

	private function download_images($images, $dir) {
		$i = 1;
		$dir = substr($dir, 0, 120);
		foreach ($images as $page_url) {
			$ext = '.jpg';
			$filename = Text::create($i)->pad(3)->to_s().$ext;
			$outpath = $dir . $filename;
			if (!is_file($outpath)) {
				$retry = false;
				do {
					$image_src = $this->get_image_src($page_url);
					// $ext2 = Text::create($image_src)->cut_rafter('.')->to_s();
					// if (strlen($ext2) == 3) {
					// 	$outpath = $dir . $filename . '.' . $ext2;
					// }

					download_it($image_src, $outpath);
					$retry = filesize($outpath) === 0/* || filesize($outpath) === 925*/;
				} while ($retry);
			}
			$i++;
		}
	}

	private function get_image_src($page_url) {
		$h = $this->create_dom($page_url);
		
		$srcs = Text::create($h->innertext)->extract_to_array('src="', '"');
		uasort($srcs, function ($a, $b) {
			return (strlen($a) > strlen($b)) ? -1 : 1;
		});
		return htmlspecialchars_decode(reset($srcs));
		// $img = $h->find('#img', 0);
		// return htmlspecialchars_decode($img->src);
	}
}