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
		return array_slice($thumbs, 0, 3);
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
			for ($i = $n; $i > 0; --$i) {
				$page_url = $url . "page_$i.html";
				echo "\n".$page_url."\n";
				$scraps = $this->filter_scrap($this->scrap_page($page_url));
				$this->add_scraps($scraps);
			}
		}
	}

	private function is_already_exist($info) {
		$n = ORM::for_table('book')->where('url', $info['url'])->count();
		return $n > 0;
	}

	public function action_update() {
		echo '<pre>';
		foreach ($this->sites as $starting_url) {
			$page = 1;
			$next = true;
			$to_add = array();
			while ($next) {
				$page_url = $starting_url . ($page > 1 ? "$page/" : '');
				echo "\n".$page_url."\n";
				$scraps = $this->filter_scrap($this->scrap_page($page_url));
				foreach ($scraps as $k => $info) {
					if ($this->is_already_exist($info)) {
						unset($scraps[$k]);
						$next = false;
					}
				}
				$to_add = array_merge($scraps, $to_add);
				$page++;
			}
			$to_add = array_reverse($to_add);
			// print_r($to_add);
			// save
			$this->add_scraps($to_add);
		}
	}

	private function print_book($book) {
		?>
		<div class="col-md-6 result">
			<?php echo "{$book->title} | {$book->pages} pages | {$book->date}"; ?>
			<a href="<?php echo HH::url($this, "action=view&id={$book->id}"); ?>">VIEW</a>
			<a href="<?php echo $book->url; ?>">ORIGIN</a>
			<br>
			<?php $samples = $book->samples(); ?>
			<a href="<?php echo HH::url($this, "action=view&id={$book->id}"); ?>" title="<?php echo $book->title; ?>">
				<?php foreach ($samples as $img) : ?>
					<img src="<?php echo $img;?>" alt="th" width="150" />
				<?php endforeach; ?>
			</a>
			<?php echo str_replace('#', ', ', trim($book->tags, '#')); ?>
		</div>
		<?php
	}

	private function get_order_choices() {
		return array(
			'date desc', 'date asc', 
			'title asc', 'title desc', 
			'id desc', 'id asc', 
			'pages asc', 'pages desc',
		);
	}

	public function search($perpage, $curpage, $order, $any, $title) {
		$q = Model::factory('Book')
			->limit($perpage)
			->offset(($curpage-1) * $perpage);
		list($column, $direction) = explode(' ', $order);
		if ($direction == 'desc') {
			$q->order_by_desc($column);
		} else {
			$q->order_by_asc($column);
		}

		HH::add_filter($q, array('title'), $title);
		HH::add_filter($q, array('title', 'tags'), $any);
		return $q->find_many();
	}

	public function action_search() {

		$order = isset($_POST['order']) ? $_POST['order'] : 'date desc';
		$perpage = isset($_POST['perpage']) ? (int)$_POST['perpage'] : 20;
		$curpage = isset($_POST['curpage']) ? (int)$_POST['curpage'] : 1;
		
		if (isset($_POST['next'])) {
			$curpage++;
		} else if (isset($_POST['prev'])) {
			$curpage--;
		}
		if ($curpage < 1) $curpage = 1;

		// ORM::configure('logging', true);
		$result = $this->search($perpage, $curpage, $order, @$_POST['any'], @$_POST['title']);
		// echo ORM::get_last_query();
		?>
		<form class="form-horizontal" method="post" role="form">
			<div class="form-group row">
				<?php HH::print_form_field('Any', 'any', @$_POST['any']); ?>
			
				<?php HH::print_form_field('Title', 'title', @$_POST['title']); ?>
			</div>
			<div class="form-group row">
				<?php HH::print_form_field('Items', 'perpage', $perpage, 3); ?>
				
				<?php HH::print_form_field('Page', 'curpage', $curpage, 3); ?>
				
				<?php HH::print_radio_field('Order', 'order', $this->get_order_choices(), $order); ?>
			</div>
			<div class="form-group row">
				<?php HH::print_submit_buttons(); ?>
			</div>

			<?php foreach ($result as $i => $book) : ?>
				<?php if ($i % 2 == 0) echo '<div class="row">'; ?>
				<?php $this->print_book($book); ?>
				<?php if ($i % 2 == 1) echo '</div>'; ?>
			<?php endforeach; ?>

			<div class="form-group" style="display:block;clear:both">
				<?php HH::print_submit_buttons(); ?>
			</div>
		</form>

		<?php
	}

	public function action_view() {
		$id = $_GET['id'];
		$book = Model::factory('Book')->find_one($id);
		$thumbnails = $book->thumbnails();
		$pages = $book->pages();
		?>
		
		<dl class="dl-horizontal">
			<dt>Title</dt><dd><a href="<?php echo $book->url; ?>"><?php echo $book->title; ?></a></dd>
			<dt>Date</dt><dd><?php echo $book->date; ?></dd>
			<dt>Page</dt><dd><?php echo $book->pages; ?></dd>
			<dt>Tags</dt><dd><?php echo str_replace('#', ', ', trim($book->tags, '#')); ?></dd>
			<dd><a href="<?php echo $book->url; ?>">ORIGIN</a></dd>
		</dl>
		
		<?php
		HH::print_downloads($book->title, $thumbnails, $pages);
	}

}
