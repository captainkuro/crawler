<?php
// http://limbero.org/jl8/5
class LimberoJl8_extractor implements Extractor {
	public function can_extract($url) {
		return strpos($url, 'http://limbero.org/jl8') === 0;
	}

	public function extract($columns, $s, $n, $url) {
		$result = array();
		for ($i=$s; $i<=$n; $i++) {
			$purl = rtrim($url, '/') . '/' . $i;
			$p = new Page($purl);
			$h = new simple_html_dom();
			$h->load($p->content());

			$item = array('image' => $h->find('#nav', 0)->find('img', 0)->src);
			$result[] = $item;
		}
		return $result;
	}
}