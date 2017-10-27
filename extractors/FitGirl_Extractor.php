<?php

class FitGirl_Extractor implements Extractor {

	public function can_extract($url) {
		return strpos($url, 'http://fitgirl-repacks.site/') === 0;
	}

	public function extract($columns, $s, $n, $url) {
		$result = array();

		for ($i=$s; $i<=$n; $i++) {
			$purl = rtrim($url, '/') . '/';
			if ($i > 1) $purl .= 'page/'.$i.'/';
			$p = new Page($purl);
			// var_dump($p->content());
			$h = new simple_html_dom();
			$h->load($p->content());

			foreach ($h->find('.hentry') as $post) {
				$item = array();

				$title_a = $post->find('.entry-title', 0)->find('a', 0);
				$item['link'] = "<a href='{$title_a->href}'>link</a>";

				$title_a = $post->find('.entry-title', 0)->find('a', 0);
				$item['title'] = $title_a->innertext;

				$subtitle = $post->find('span.entry-date', 0);
				$date = Text::create($subtitle->text());
				$item['date'] = $date;

				$content = $post->find('.entry-content', 0);
				$parsed = Text::create($content->innertext)->regex_match('#Genres/Tags: <strong>([^<]*)<#');
				$categories = isset($parsed[1]) ? $parsed[1] : '';
				$item['categories'] = $categories;

				$content = $post->find('.entry-content', 0);
				$item['content'] = strip_tags($content->innertext, '<br><p><span><strong><ul><li><div>');
				$item['description'] = $item['content'];
				$item['short'] = substr($item['content'], 0, 700);

				$content = $post->find('.entry-content', 0);
				$img = $content->find('img', 0);
				$item['image'] = $img ? $img->outertext() : '';
				for ($j=2; $j<=5; $j++) {
					$imgX = $content->find('img', $j-1);
					$item['image'.$j] = $imgX ? $imgX->outertext() : '';
				}

				$result[] = $item;
			}
		}
		return $result;
	}
}