<?php
// http://lifehacker.com/
// http://lifehacker.com/?startTime=1413997200386
class Kinja_extractor implements Extractor {
	public function can_extract($url) {
		return strpos($url, 'http://kotaku.com') !== false
			|| strpos($url, 'http://io9.com') !== false
			|| strpos($url, 'http://lifehacker.com') !== false;
	}

	public function extract($columns, $s, $n, $url) {
		$result = array();
		for ($i=$s; $i<=$n; $i++) {
			// @todo
		}
		return $result;
	}
}