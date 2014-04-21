<?php
// http://www.videogamesindonesia.com/comics/index.php
class VgiComics_extractor implements Extractor {
	public function can_extract($url) {
		return strpos($url, 'http://www.videogamesindonesia.com/comics/') === 0;
	}

	public function extract($columns, $s, $n, $url) {

		if (strpos($url, 'vgi-comics-archives.php')) {
			$to_extract = $this->grab_from_archive($s, $n, $url);
		} else {
			// gather links
			$links = array();
			while (count($links) < $n) {
				$p = new Page($url);
				$h = new simple_html_dom();
				$h->load($p->content());
				$articles = $h->find('.aarticles', 0);
				foreach ($articles->find('a.athmb') as $thumb) {
					$links[] = $thumb->href;
				}
				$more = $h->find('a.vmore', 0);
				if (!$more) break;
				$url = $more->href;
			}

			$to_extract = array_slice($links, $s-1, $n-$s+1);
		}

		return $this->grab_from_list($to_extract);
	}

	private function grab_from_archive($s, $n, $url) {
		// http://www.videogamesindonesia.com/comics/vgi-comics-archives.php
		$p = new Page($url);
		$h = new simple_html_dom();
		$h->load($p->content());

		$article = $h->find('.main-area', 0)->find('article', 0);
		$table = $article->find('table', 0);

		$result = array();
		foreach ($table->find('a') as $a) {
			$href = $a->href;
			$href = str_replace('../', 'http://www.videogamesindonesia.com/', $href);
			$result[] = $href;
		}
		return array_slice($result, $s-1, $n-$s+1);;
	}

	private function grab_from_list($to_extract) {
		$result = array();
		foreach ($to_extract as $purl) {
			$p = new Page($purl);
			$h = new simple_html_dom();
			$h->load($p->content());

			$item = array(
				'url' => $purl,
				'link' => "<a href='{$purl}'>link</a>",
			);
			$article = $h->find('.main-area', 0)->find('article', 0);
			$item['title'] = $article->find('h1', 0)->innertext;
			$detail = $article->find('.artdet', 0);
			$item['image'] = $detail->find('img', 0)->outertext();
			$item['image'] = str_replace('../', 'http://www.videogamesindonesia.com/', $item['image']);
			$item['content'] = $detail->innertext;
			$item['description'] = strip_tags($detail->innertext, '<br><p>');

			$result[] = $item;
		}
		return $result;
	}
}