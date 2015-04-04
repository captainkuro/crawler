<?php

class FacebookAlbum_Downloader implements ADownloader {
	private $graph_url = 'https://graph.facebook.com/v1.0/{ID}/photos?pretty=0&limit=50';
	private $dir;

	public function display() {
		return 'Facebook Album';
	}

	public function download () {
		echo "Album URL: ";
		$album_url = trim(fgets(STDIN));
		echo "Save Dir: ";
		$this->dir = trim(fgets(STDIN));
		$this->dir = rtrim($this->dir, '/') . '/';

		$current_url = $this->album_to_gallery($album_url);
		$active	= true;
		while ($active) {
			echo "Opening {$current_url}...\n";
			$p = new Page($current_url);
			$json = json_decode($p->content());

            $this->download_images($json);
            if (isset($json->paging->next)) {
            	$current_url = $json->paging->next;
            } else {
            	$active = false;
            }
		}
	}

	private function album_to_gallery($album_url) {
		if (preg_match('/\?set=a\.(\d+)/', $album_url, $match)) {
			$id = $match[1];
			$gallery = str_replace('{ID}', $id, $this->graph_url);
			return $gallery;
		} else {
			throw new Exception("Unrecognized Album URL");
		}
	}

	private function download_images($json) {
		foreach ($json->data as $post) {
			$output = $this->output_path($post);
			// try hires
			$image_url = isset($post->images[0]) ? $post->images[0]->source : $post->source;
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