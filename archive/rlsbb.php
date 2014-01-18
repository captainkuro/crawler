<?php
/*
Release BB
http://www.rlsbb.com/
*/

class RLSBB {
	
	public function test_parse($page_url) {
		$p = new Page($page_url);
		$h = new simple_html_dom();
		$h->load($p->content());
		foreach ($h->find('div.post') as $post) {
			$title_a = $post->find('h3.postTitle', 0)->find('a', 0);
			$subtitle = $post->find('div.postSubTitle', 0);
			$date = Text::create($subtitle->innertext)->regex_match('/Posted on (.*) in </');
			$date = $date[1];
			$categories = array();
			foreach ($subtitle->find('a[rel=category tag]') as $c) {
				$categories[] = $c->innertext;
			}
			$content = $post->find('div.postContent', 0);
			$row = array(
				'url' => $title_a->href,
				'title' => $title_a->innertext,
				'date' => $date,
				'category' => $categories,
				'content' => htmlspecialchars($content->innertext),
			);
			print_r($row);
		}
	}
}

// test run
echo '<pre>';
$r = new RLSBB();
$r->test_parse('http://www.rlsbb.com/page/3');