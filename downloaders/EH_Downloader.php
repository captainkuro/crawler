<?php

class EH_Downloader implements ADownloader {
	private $default_dir = 'D:\Temp\onion';

	public function display() {
		return 'E-H Gallery';
	}

	public function download () {
		echo "Gallery URL: ";
		$gallery_url = trim(fgets(STDIN));
		echo "Save Dir [{$this->default_dir}]: ";
		$dir = trim(fgets(STDIN));

		$dir = $this->prepare_dir($dir, $gallery_url);
		$images = $this->collect_images($gallery_url);
		$this->download_images($images, $dir);
	}

	private function create_dom($url) {
		$p = new Page($url, array(CURLOPT_COOKIE => 'nw=1'));
		$h = new simple_html_dom();
		$h->load($p->content());
		return $h;
	}

	private function prepare_dir($dir, $gallery_url) {
		$dir = $dir ? $dir : $this->default_dir;
		$h = $this->create_dom($gallery_url);
		
		$title = $h->find('title', 0)->innertext;
		$filtered = preg_replace('/[^\w ]/', '', $title);
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
			echo "Opening {$gallery_url}\n";
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
		foreach ($images as $filename => $page_url) {
			$outpath = $dir . $filename;
			if (!is_file($outpath)) {
				$retry = false;
				do {
					$image_src = $this->get_image_src($page_url);
					download_it($image_src, $outpath);
					$retry = filesize($outpath) === 0;
				} while ($retry);
			}
		}
	}

	private function get_image_src($page_url) {
		$h = $this->create_dom($page_url);
		
		$img = $h->find('#img', 0);
		return $img->src;
	}
}