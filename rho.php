<?php
/*
Spider for readhentaionline.com

Halaman utama berisi daftar hentai manga sort by submitted descending
http://readhentaionline.com/category/hentai-manga/

Di dalam satu halaman info manga, contoh
http://readhentaionline.com/read-mainin-hentai-manga-online/
terdapat:
	1. judul
	2. slug
	3. submitted date
	4. description
	5. tags
	6. thumbnails (max 8)
	7. no. of images
	
Image full size mengikuti pola sederhana, cukup simpan url 1 image full size,
sisanya tinggal for (i from 1 to #ofImages).jpg

CREATE TABLE `book` (
  `id` integer NOT NULL CONSTRAINT pid PRIMARY KEY AUTOINCREMENT,
  `url` varchar NOT NULL,
  `slug` varchar NOT NULL,
  `title` varchar NOT NULL,
  `submit_date` varchar NOT NULL,
  `description` text NOT NULL,
  `pages` integer NOT NULL,
  `gallery_url` varchar NOT NULL,
  `tags` text NOT NULL,
  `thumbs` text NOT NULL,
  `first_image` varchar NULL
);
*/
// include 'class/rb.php';
include 'class/idiorm.php';
include 'class/paris.php';
include_once 'class/text.php';
include_once 'class/page.php';

class Book extends Model {
	protected $_tags = null;
	protected $_thumbs = null;
	
	public function save() {
		if (is_array($this->tags)) {
			$this->tags = '#'.implode('#', $this->tags).'#';
		}
		if (is_array($this->thumbs)) {
			$this->thumbs = implode('#', $this->thumbs);
		}
		parent::save();
	}
	
	public function tags() {
		if (is_null($this->_tags)) {
			if (is_array($this->tags)) {
				$this->_tags = $this->tags;
			} else {
				$val = preg_replace('/(^#)|(#$)/', '', $this->tags);
				$this->_tags = explode('#', $val);
			}
		}
		return $this->_tags;
	}
	
	public function thumbnails() {
		if (is_null($this->_thumbs)) {
			if (is_array($this->thumbs)) {
				$this->_thumbs = $this->thumbs;
			} else {
				$this->_thumbs = explode('#', $this->thumbs);
			}
		}
		return $this->_thumbs;
	}
	
	public function image_links() {
		if (!$this->first_image) {
			$p = new Page($this->gallery_url);
			$t_content = new Text($p->content());
			$raw = $t_content->extract_to_array('src="', '"');
			// search for image
			foreach ($raw as $e) {
				if (preg_match('/1\.jpg$/', $e)) {
					$src = $e;
					break;
				}
			}
			if (!isset($src)) return array();
			$this->first_image = $src;
			$this->save();
		}
		$img_dir = dirname($this->first_image);
		for ($i=1; $i<=$this->pages; $i++) {
			echo "<a href='$img_dir/$i.jpg'>{$this->slug}</a><br/>\n";
		}
	}
}

class Readhentaionline {
	public $base = 'http://readhentaionline.com';
	public $db = null;
	
	public function run() {
		$dbpath = realpath('./sqlite/rho.db');
		ORM::configure('sqlite:' . $dbpath);
		
		$this->post = (object)$_POST;
		$stage = isset($_REQUEST['stage']) ? $_REQUEST['stage'] : '';
		$method = 'stage_'.$stage;
		if (method_exists($this, $method)) {
			$this->$method();
		} else {
			$this->_default();
		}
	}
	
	public function _default() {
		// print_r($this->extract_info('http://readhentaionline.com/read-musa-vol-2-hentai-manga-online/'));
		
		/**
		$urls = $this->grab_chapter_urls('http://readhentaionline.com/category/hentai-manga/');
		file_put_contents('rho.all_links', '<?php return '.var_export($urls, true).';');
		/**/
		
		/**
		$links = include 'rho.all_links';
		$n = count($links);
		for ($i=$n-1; $i>=0; --$i) {
			echo $i.' ';
			$info = $this->extract_info($links[$i]);
			$this->add_book($info);
		}
		/**/
		
		$this->stage_update();
	}
	
	public function extract_info($chapter_url) {
		// slug
		preg_match('/read-(.+)-hentai-manga-online/', $chapter_url, $m);
		$ret = array(
			'url' => $chapter_url,
			'slug' => rawurldecode($m[1]),
		);
		$p = new Page($chapter_url);
		// title
		$p->go_line('id="leftcontent"');
		$p->go_line('<h2>');
		$m = $p->curr_line()->regex_match('/<h2>Read (.*) Hentai Manga Online<\/h2>/');
		$ret['title'] = html_entity_decode($m[1], ENT_COMPAT, 'UTF-8');
		// submit date
		$p->go_line('class="date"');
		$m = $p->curr_line()->regex_match('/<\/strong> (.*)<\/p>/');
		$ret['submit_date'] = date('Y-m-d', strtotime($m[1]));
		// description
		$p->go_line('Manga Info :<');
		$m = $p->curr_line()->regex_match('/Manga Info :(.*)$/');
		$part = $m[1];
		while (strpos($part, '</p>') === false) {
			$part .= $p->next_line()->to_s();
		}
		$ret['description'] = html_entity_decode(strip_tags($part), ENT_COMPAT, 'UTF-8');
		// tags
		$p->go_line('<!-- /post -->');
		$p->next_line(2);
		$tags = $p->curr_line()->extract_to_array('">', '</');
		$ret['tags'] = array_map('html_entity_decode', $tags, array_fill(0, count($tags), ENT_COMPAT), array_fill(0, count($tags), 'UTF-8'));
		// # images
		$p->go_line('id="gallery"');
		$url = $this->base . $p->next_line()->dup()
			->cut_between('href="', '"')
		->to_s();
		$m = $p->curr_line()->regex_match('/Total No of Images in Gallery: (\d+)/');
		$ret['pages'] = $m ? $m[1] : 0;
		// thumbnails
		$ret['thumbs'] = $p->curr_line()->extract_to_array('src="', '"');
		// gallery url
		$image_url = $this->base . $p->curr_line()->dup()
			->cut_between('href="', '"')
		->to_s();
		$ret['gallery_url'] = $image_url;
		// skip first image url, for faster crawling
		$ret['first_image'] = null;
		return $ret;
	}
	
	public function grab_chapter_urls($start_page_url, $check_database = false) {
		$p = new Page($start_page_url);
		// check if there are more pages
		$p->go_line('id="postnav"');
		$p->next_line(2);
		if ($p->curr_line()->exist("class='pages'")) {
			$m = $p->curr_line()->regex_match("/'>Page 1 of (\d+)<\//");
			$tot_pages = $m[1];
		} else {
			$tot_pages = 1;
		}
		$chapters = array();
		for ($i=1; $i<=$tot_pages; $i++) {
			$p = new Page($start_page_url . (($i==1) ? '' : ('page/'.$i.'/')));
			echo $p->url()."\n";
			// grab all chapter in this page
			$t_content = new Text($p->content());
			$raw = array_unique($t_content->extract_to_array('href="', '"'));
			foreach ($raw as $e) {
				if (preg_match('/^http:\/\/readhentaionline\.com\/read-[^\/]*-hentai-manga-online\/$/', $e)) {
					if ($check_database) {
						if ($this->url_already_exist($e)) return $chapters;
					}
					$chapters[] = $e;
				}
			}
		}
		return $chapters;
	}
	
	public function add_book($info) {
		$book = Model::factory('Book')->create();
		$book->hydrate($info);
		$book->save();
	}
	
	public function add_or_edit_book($url, $info) {
		$book = Model::factory('Book')->where('url', $url)->find_one();
		if (!$book) {
			$book = Model::factory('Book')->create();
		}
		foreach ($info as $k => $v) {
			$book->$k = $v;
		}
		$book->save();
	}
	
	public function url_already_exist($url) {
		$n = ORM::for_table('book')->where('url', $url)->count();
		return $n > 0;
	}
	
	public function stage_update() {
		$update_url = 'http://readhentaionline.com/category/hentai-manga/';
		$links = $this->grab_chapter_urls($update_url, true);
		$n = count($links);
		for ($i=$n-1; $i>=0; --$i) {
			echo "Saving {$links[$i]}\n";
			$info = $this->extract_info($links[$i]);
			$this->add_book($info);
		}
	}
}
$a = new Readhentaionline();
$a->run();