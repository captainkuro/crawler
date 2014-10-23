<?php
// http://limbero.org/jl8/5
class LimberoJl8_Extractor implements Extractor {
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

			$nav = $h->find('#nav', 0);
			$images = '';
			foreach ($nav->find('img') as $img) {
				$images .= $img->outertext().'<br>';
			}
			$item = array(
				'image' => $nav->find('img', 0)->outertext(),
				'images' => $images,
				'link' => "<a href='$purl'>Link</a>",
			);
			$result[] = $item;
		}
		return $result;
	}
}