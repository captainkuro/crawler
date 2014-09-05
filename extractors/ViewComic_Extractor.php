<?php
//http://viewcomic.com/new-suicide-squad-002-2014/
//simply display all images in this chapter
class ViewComic_extractor implements Extractor {
	public function can_extract($url) {
		return strpos($url, 'http://viewcomic.com') !== false;
	}

	public function extract($columns, $s, $n, $url) {
		$result = array();

		$p = new Page($url);
		$h = new simple_html_dom();
		$h->load($p->content());

		$pinbin = $h->find('.pinbin-copy', 0);
		foreach ($pinbin->find('img') as $img) {
			$item = array(
				'image' => $img->outertext(),
			);
			$result[] = $item;
		}
		return $result;
	}
}