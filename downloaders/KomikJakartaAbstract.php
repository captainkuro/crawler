<?php

abstract class KomikJakartaAbstract implements ADownloader {
	protected $dir;
	protected $home;
	private $active;
	private $images;

	public function download () {
		// grab this page
		// grab next pages
		// until meet existing file or at the end
		$page_url = $this->home;
		$this->active = true;
		$this->images = array();
		while ($this->active) {
			echo "Opening {$page_url}...\n";
			$p = new Page($page_url);
			$h = new simple_html_dom();
            $h->load($p->content());

            $this->collect_images($h);
            $page_url = $this->next_page($h);
		}
		$this->download_images();
	}

	private function collect_images($h) {
		$img_tags = $h->find('.spg-clip img');
		foreach ($img_tags as $img_tag) {
			if (!$this->is_image_already_exist($img_tag)) {
				$this->images[] = $img_tag->src;
			} else {
				$this->active = false;
				break;
			}
		}
	}

	private function is_image_already_exist($img) {
		$filename = basename($img->src);
		return is_file($this->dir . $filename);
	}

	private function next_page($h) {
		$current = $h->find('.spg-pagination .current', 0);
		$next = $current->next_sibling();
		if ($next) {
			return $this->home . $next->href;
		} else {
			$this->active = false;
			return '';
		}
	}

	private function download_images() {
		for ($n = count($this->images)-1; $n >= 0; $n--) {
			$img_url = $this->images[$n];
			$output = $this->dir . basename($img_url);
			echo "Downloading {$img_url}...\n";
			download_it($img_url, $output);
		}
	}
}