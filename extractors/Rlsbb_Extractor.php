<?php

class Rlsbb_Extractor implements Extractor {

	public function can_extract($url) {
		return strpos($url, 'https://www.rlsbb.ru') === 0
			|| strpos($url, 'https://rlsbb.ru') === 0
			|| strpos($url, 'http://www.rlsbb.ru') === 0
			|| strpos($url, 'http://rlsbb.ru') === 0;
	}

	public function extract($columns, $s, $n, $url) {
		$result = array();
		for ($i=$s; $i<=$n; $i++) {
			$purl = rtrim($url, '/') . '/';
			if ($i > 1) $purl .= 'page/'.$i.'/';
			$p = new Page($purl, array(
				'bypass_cloudflare' => strpos($url, 'http://rlsbb.com') === 0,
			));
			// var_dump($p->content());
			$h = new simple_html_dom();
			$h->load($p->content());

			foreach ($h->find('div.post') as $post) {
				$item = array();

				$title_a = $post->find('.postTitle', 0)->find('a', 0);
				$item['link'] = "<a href='{$title_a->href}'>link</a>";

				$title_a = $post->find('.postTitle', 0)->find('a', 0);
				$item['title'] = $title_a->innertext;

				$subtitle = $post->find('.postSubTitle', 0);
				$date = Text::create($subtitle->innertext)->regex_match('/Posted on (.*) in </');
				$date = $date[1];
				$item['date'] = $date;

				$subtitle = $post->find('.postSubTitle', 0);
				$categories = array();
				foreach ($subtitle->find('a[rel=category tag]') as $c) {
					$categories[] = $c->innertext;
				}
				$item['categories'] = implode(', ', $categories);

				$content = $post->find('.postContent', 0);
				if (!$content) {
					$content = $post->find('.entry-content', 0);
				}
				$item['content'] = strip_tags($content->innertext, '<br>');
				$item['description'] = $item['content'];

				$content = $post->find('.postContent', 0);
				if (!$content) {
					$content = $post->find('.entry-content', 0);
				}
				$img = $content->find('img', 0);
				$item['image'] = $img ? $img->outertext() : '';
				$img2 = $content->find('img', 1);
				$item['image2'] = $img2 ? $img2->outertext() : '';
				$img3 = $content->find('img', 2);
				$item['image3'] = $img3 ? $img3->outertext() : '';

				$result[] = $item;
			}
		}
		return $result;
	}
}