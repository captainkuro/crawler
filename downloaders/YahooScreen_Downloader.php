<?php

class YahooScreen_Downloader implements ADownloader {
	public function display() {
		return 'Yahoo! Screen';
	}

	public function download () {
		echo "Video URL: ";
		$video_url = trim(fgets(STDIN));
		echo "Save Dir: ";
		$dir = trim(fgets(STDIN));

		$streams = $this->collect_streams($video_url);
		$this->print_streams($streams);
		echo "Which video: ";
		$choice = trim(fgets(STDIN));
		echo "Output filename: ";
		$filename = trim(fgets(STDIN));
		$this->download_video($streams, $choice, $dir, $filename);
	}

	private function collect_streams($url) {
		$p = new Page($url);
		$p->go_line('"streams":[{');
		$json_part = $p->curr_line()->cut_between('"streams":[{', '}]');
		$streams = '[{' . $json_part . '}]';
		$streams = json_decode($streams);
		$result = array();
		foreach ($streams as $stream) {
			$result[] = (object)array(
				'res' => $stream->width.'x'.$stream->height,
				'url' => $stream->host . $stream->path,
				'ext' => $stream->format,
			);
		}
		return $result;
	}

	private function print_streams($streams) {
		foreach ($streams as $i => $stream) {
			echo "[$i] {$stream->res}\n";
		}
	}

	private function download_video($streams, $choice, $dir, $filename) {
		if (!isset($streams[$choice])) {
			echo "INVALID CHOICE!";
			return;
		}
		$stream = $streams[$choice];
		$new_filename = "$dir/$filename.{$stream->ext}";
		download_it($stream->url, $new_filename);
	}
}