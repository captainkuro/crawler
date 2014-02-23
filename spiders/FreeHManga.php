<?php
// Common source code
// http://hentai4manga.com/hentai_manga/page_210.html#.Uwm7XIWuqf9
// http://hentai4manga.com/hentai_manga/
// http://hentai4manga.com/tags/english/
// hentai4manga.com/hentai_manga/Inuburo-Working-Girl-039-s-Sexually-Exposed-Scenes-Hataraku-Oneesan-English-doujin-moe-us_21228/
// http://www.hcomicbook.com/hentai_manga/
// http://www.hcomicbook.com/hentai_doujin/
// http://www.hcomicbook.com/tags/english/

class Book extends Model {

	public function samples() {
		$thumbs = $this->thumbnails();
		return array_slice($thumbs, 0, 2);
	}

	public function thumbnails() {
		$img_dir = dirname($this->thumb);
		$pages = array();
		for ($i = 1; $i <= $this->pages; $i++) {
			$pages[] = $img_dir . '/' . Text::create($i)->pad(3) . '.jpg';
		}
		return $pages;
	}

	public function pages() {
		$thumbs = $this->thumbnails();
		$mapping = function ($url) {
			return Text::create($url)->regex_replace('/-thumb[^\/]+/', '')->to_s();
		};
		return array_map($mapping, $thumbs);
	}
}

class FreeHManga implements Spider {

	private $sites = array(
		'http://www.hcomicbook.com/tags/english/',
		'http://hentai4manga.com/tags/english/',
	);

	public function get_title() {
		return 'FreeHManga spider';
	}

	public function get_db_path() {
		return './sqlite/free_hmanga.db';
	}

	public function create_database() {
		ORM::get_db()->query('CREATE TABLE `book` (
		  `id` integer NOT NULL CONSTRAINT pid PRIMARY KEY AUTOINCREMENT,
		  `title` varchar NOT NULL,
		  `url` varchar NOT NULL,
		  `date` varchar NOT NULL,
		  `pages` integer NOT NULL,
		  `thumb` varchar NOT NULL,
		  `tags` text NOT NULL
		)');
	}

	private function scrap_page($url) {
		$base_url = 'http://' . parse_url($url, PHP_URL_HOST);
		$p = new Page($url);
		$h = new simple_html_dom();
		$h->load($p->content());
		$boxes = $h->find('.textbox');

		$result = array();
		foreach ($boxes as $box) {
			// image/url
			$content = $box->find('.textbox-content', 0);
			$url = $base_url . $content->find('a', 0)->href;
			$thumb = $base_url . $content->find('img', 0)->src;
			// other data
			$label = $box->find('.webcss-label', 0);
			
			$title = $label->find('p', 0)->find('a', 0)->innertext;
			$title = html_entity_decode($title, ENT_COMPAT, 'UTF-8');

			$h2 = $label->find('h2', 0);
			$date = Text::create($h2->innertext)->cut_after('>:')->to_s();

			$h5 = $label->find('h5', 0);
			$tags = Text::create($h5->innertext)->strip_tags()->cut_after(':')->to_s();
			$tags = array_filter(explode(',', $tags), 'trim');

			$view = $label->find('.webcss_view', 0);
			$m = Text::create($view->innertext)->regex_match('/(\d+)/');
			$pages = $m[1];

			$item = array(
				'title' => $title,
				'url' => $url,
				'date' => $date,
				'pages' => $pages,
				'thumb' => $thumb,
				'tags' => '#'.implode('#', $tags).'#',
			);
			$result[] = $item;
		}
		return array_reverse($result);
	}

	private function filter_scrap($scraps) {
		$only_english = function ($scrap) {
			return strpos($scrap['tags'], '#english#') !== FALSE;
		};
		return array_filter($scraps, $only_english);
	}

	private function get_last_page($url) {
		$p = new Page($url);
		$p->go_line('Pages|');
		$m = $p->curr_line()->regex_match('/(\d+) Pages/');
		return $m[1];
	}

	private function add_scraps($scraps) {
		foreach ($scraps as $scrap) {
			echo $scrap['url']."\n";
			$book = Model::factory('Book')->create();
			$book->hydrate($scrap);
			$book->save();
		}
	}

	public function action_test() {
		$book = Model::factory('Book')->find_one(73);
		HH::print_downloads($book->title, $book->thumbnails(), $book->pages());
	}

	public function action_init() {
		$to_surf = array(
			'http://hentai4manga.com/hentai_manga/',
			'http://www.hcomicbook.com/hentai_manga/',
			'http://www.hcomicbook.com/hentai_doujin/',
		);
		echo '<pre>';
		$grand_result = array();
		foreach($to_surf as $url) {
			$n = $this->get_last_page($url);
			for ($i = $n; $i > $n-10; --$i) {
				$page_url = $url . "page_$i.html";
				$scraps = $this->filter_scrap($this->scrap_page($page_url));
				$this->add_scraps($scraps);
			}
		}
	}

	public function action_update() {

	}

	public function action_search() {

	}

}