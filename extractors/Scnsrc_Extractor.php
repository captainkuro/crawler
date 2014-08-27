<?php

class Scnsrc_Extractor implements Extractor {
	
	public function can_extract($url) {
		return strpos($url, 'http://www.scnsrc.me') === 0;
	}

	public function extract($columns, $s, $n, $url) {
		$result = array();
		for ($i=$s; $i<=$n; $i++) {
			$purl = rtrim($url, '/') . '/';
			if ($i > 1) $purl .= 'page/'.$i.'/';
			$p = new Page($purl);
			$h = new simple_html_dom();
			$h->load($p->content());

			foreach ($h->find('div.post') as $post) {
				$item = array();

				if (in_array('link', $columns)) {
					$title_a = $post->find('h2', 0)->find('a', 0);
					$item['link'] = "<a href='{$title_a->href}'>link</a>";
				}
				if (in_array('title', $columns)) {
					$title_a = $post->find('h2', 0)->find('a', 0);
					$item['title'] = $title_a->innertext;
				}
				if (in_array('date', $columns)) {
					$date = $post->find('span.date', 0);
					$item['date'] = $date->innertext;
				}
				if (in_array('category', $columns)) {
					$meta = $post->find('div.meta', 0);
					$categories = array();
					foreach ($meta->find('a[rel=category tag]') as $c) {
						$categories[] = $c->innertext;
					}
					$item['categories'] = implode(', ', $categories);
				}
				if (in_array('content', $columns)) {
					$content = $post->find('.storycontent', 0);
					$item['content'] = strip_tags($content->innertext, '<br>');
				}
				if (in_array('image', $columns)) {
					$content = $post->find('.storycontent', 0);
					$img = $content->find('img', 0);
					$item['image'] = $img ? $img->outertext() : '';
					$img2 = $content->find('img', 1);
					$item['image2'] = $img2 ? $img2->outertext() : '';
				}

				$result[] = $item;
			}
		}
		return $result;
	}
}