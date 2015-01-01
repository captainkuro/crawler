<?php

class FacebookAlbum_Downloader implements ADownloader {
	private $graph_url = 'https://graph.facebook.com/v1.0/406507987653/photos?pretty=0&limit=50';
	private $dir = '/home/khandar-gdp/tmp/kostum/';

	public function display() {
		return 'Facebook Album - KOSTUM';
	}

	public function download () {
		$current_url = $this->graph_url;
		$active	= true;
		while ($active) {
			echo "Opening {$current_url}...\n";
			$p = new Page($current_url);
			$json = json_decode($p->content());

            $this->download_images($json);
            if ($json->paging->next) {
            	$current_url = $json->paging->next;
            } else {
            	$active = false;
            }
		}
	}

	private function download_images($json) {
		foreach ($json->data as $post) {
			$output = $this->output_path($post);
			$image_url = $post->source;
			if (!is_file($output)) {
				echo "Downloading {$image_url}\n";
				download_it($image_url, $output);
			}
		}
	}

	private function output_path($post) {
		$time = $post->created_time;
		$id = $post->id;
		$formatted_time = date('Y-m-d', strtotime($time));
		return "{$this->dir}{$formatted_time}-{$id}.jpg";
	}
}