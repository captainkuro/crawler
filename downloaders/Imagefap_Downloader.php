<?php
require_once "crawler.php";
class Imagefap_Downloader implements ADownloader {

	private $default_dir;

	public function display() {
		return 'Imagefap.com';
	}

	public function download () {
		$this->default_dir = DConfig::p_folder();

		echo "Gallery URL: ";
		$gallery_url = trim(fgets(STDIN));
		echo "Save Dir [{$this->default_dir}]: ";
		$dir = trim(fgets(STDIN));

		$dir = $this->prepare_dir($dir, $gallery_url);
		$this->download_all($gallery_url, $dir);
	}

	private function prepare_dir($dir, $gallery_url) {
		$dir = $dir ? $dir : $this->default_dir;
		$url_dir = urldecode(basename($gallery_url));
		$new_dir = rtrim($dir, '/') . '/' . $url_dir . '/';
		if (!is_dir($new_dir)) {
			mkdir($new_dir);
		}
		return $new_dir;
	}

	private function download_all($base, $destination) {
		$sitename = "http://www.imagefap.com";
		$finish = false;
		$firstbase = $base;
		
		$i = 1;
		while (!$finish) {
			$c = new Crawler($base);
			echo $base."\n";
			$c->go_to(array('<table style=', ':: next ::'));
			if (Crawler::is_there($c->curline, ':: next ::')) {
				$finish = false;
				$urld = Crawler::extract($c->curline, 'href="', '"');
				$base = $firstbase.html_entity_decode($urld);
				$c->go_to('<table style=');
			} else {
				$finish = true;
			}
			while ($line = $c->readline()) {
				if (Crawler::is_there($line, 'border=0')) {
					$img = Crawler::extract($line, 'src="', '"');
					$img = str_replace('/thumb/', '/full/', $img);
					$img = preg_replace('/\/x\d\./', '/', $img);
					$filename = basename($img);
					$ext = Crawler::cutfromlast($filename, '.');
					$text = Crawler::n($i++, 4);
					
					$this->save_to($img, "$destination/$text$ext");
				} else if (Crawler::is_there($line, '</form>')) {
					break;
				}
			}
			$c->close();
		}
	}

	private function save_to($url, $file) {
		download_it($url, $file, "--header=\"Accept: image/*\"");
	}
}