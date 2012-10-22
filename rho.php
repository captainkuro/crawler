<?php
/*
Spider for readhentaionline.com
Now http://hentaimangaonline.com/

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
sekarang thumbs menyimpan id dari manga
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
		/*
		if (is_null($this->_thumbs)) {
			if (is_array($this->thumbs)) {
				$this->_thumbs = $this->thumbs;
			} else {
				$this->_thumbs = explode('#', $this->thumbs);
			}
		}
		return $this->_thumbs;
		*/
		// http://hentaimangaonline.com/wp-content/themes/snapshot/thumb.php?src=images/80564/a.jpg&w=160&h=250&zc=1&q=90
		$id = $this->thumbs;
		$n = min(8, $this->pages);
		$thumbs = array();
		for ($i=1; $i<=$n; $i++) {
			$thumbs[] = "http://hentaimangaonline.com/wp-content/themes/snapshot/thumb.php?src=images/$id/$i.jpg&w=160&h=250&zc=1&q=90";
		}
		return $thumbs;
	}
	
	public function image_links() {
		if (!$this->first_image) {
			$p = new Page($this->gallery_url);
			$t_content = new Text($p->content());
			$raw = $t_content->extract_to_array('src="', '"');
			// search for image
			foreach ($raw as $e) {
				if (preg_match('/\d\.jpg$/', $e)) {
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

class Post_Data {
	protected $_;
	
	public function __construct() {
		$this->_ = $_POST;
	}
	
	public function __get($k) {
		return isset($this->_[$k]) ? $this->_[$k] : null;
	}
	
	public function __set($k, $v) {
		return $this->_[$k] = $v;
	}
}

class Readhentaionline {
	public $base = 'http://hentaimangaonline.com';
	public $db = null;
	
	public function create_database() {
		ORM::get_db()->query('CREATE TABLE `book` (
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
		)');
	}
	
	public function run() {
		echo "<html><head><meta charset='utf-8'></head><body>";
		$dbpath = './sqlite/rho.db';
		$empty_database = false;
		if (!is_file($dbpath)) {
			touch($dbpath);
			$empty_database = true;
		}
		$dbpath = realpath($dbpath);
		ORM::configure('sqlite:' . $dbpath);
		if ($empty_database) $this->create_database();
		
		$this->post = new Post_Data();
		$stage = isset($_REQUEST['stage']) ? $_REQUEST['stage'] : '';
		$method = 'stage_'.$stage;
		if (method_exists($this, $method)) {
			$this->$method();
		} else {
			$this->_default();
		}
		echo "</body></html>";
	}
	
	public function _default() {
		// print_r($this->extract_info('http://readhentaionline.com/read-a-corner-of-absolute-zero-hentai-manga-online/'));
		
		/**
		$urls = $this->grab_chapter_urls('http://readhentaionline.com/category/hentai-manga/');
		file_put_contents('rho.all_links', '<?php return '.var_export($urls, true).';');
		/**/
		
		// recover
		/**
		$links = include 'rho.all_links';
		$links = array_reverse(array_unique($links));
		foreach ($links as $link) { //if (!$this->url_already_exist($link)) {
			echo $link." <br/>\n";
			$info = $this->extract_info($link);
			$this->add_book($info);
		}//}
		/**/
		
		// fix other mangas taken as tags
		/**
		$q = Model::factory('Book')->where_gt('pages', 0)->order_by_asc('id');
		foreach ($q->find_many() as $b) {
			echo 'Searching '.$b->id." {$b->slug}<br/>\n";
			$p = new Page($b->url);
			if (strpos($p->content(), '<h3>Other Chapters') !== false) {
				echo "Hit {$b->slug}<br/>\n";
				$data = $this->extract_info($b->url);
				$b->tags = $data['tags'];
				$b->save();
			}
		}
		/**/
	}
	
	// because html recently changed
	public function extract_info($chapter_url) {
		// slug
		preg_match('/read-(.+)-hentai-manga-online/', $chapter_url, $m);
		$ret = array(
			'url' => $chapter_url,
			'slug' => rawurldecode($m[1]),
		);
		$p1 = new Page($chapter_url);
		// $p = new Page();
		// $p->fetch_text(str_replace('</', "\n", $p1->content()));
		$p = $p1;
		// title
		$p->go_line('id="leftcontent"');
		$p->go_line('<h2>');
		$m = $p->curr_line()->regex_match('/<h2>Read (.*) Hentai Manga Online/');
		$ret['title'] = html_entity_decode($m[1], ENT_COMPAT, 'UTF-8');
		// submit date
		$p->go_line('class="date"');
		$m = $p->curr_line()->regex_match('/strong> (.*)</');
		$ret['submit_date'] = date('Y-m-d', strtotime($m[1]));
		// description
		$p->go_line('Manga Info :<');
		$m = $p->curr_line()->regex_match('/Manga Info :(.*)$/');
		if ($m) {
			$part = $m[1];
			while (strpos($part, 'p>') === false) {
				$part .= $p->next_line()->to_s();
			}
			$ret['description'] = html_entity_decode(strip_tags($part), ENT_COMPAT, 'UTF-8');
		} else {
			$ret['description'] = '';
			$p->reset_line();
			$p->go_line('Author\'s Description');
		}
		// tags
		$p->go_line('<!-- /post -->');
		$p->next_line(2);
		$ret['tags'] = array();
		$raw = $p->curr_line()->dup()->cut_until('<br/>');
		foreach ($raw->extract_to_array('">', '<') as $tag) {
			$ret['tags'][] = html_entity_decode($tag, ENT_COMPAT, 'UTF-8');
		}
		/*
		do { if ($p->curr_line()->contain('href')) {
			$m = $p->curr_line()->regex_match('/">(.*)$/');
			$ret['tags'][] = html_entity_decode($m[1], ENT_COMPAT, 'UTF-8');
		}} while (!$p->next_line()->contain('<br/>'));
		*/
		$p->go_line('id="gallery"');
		// gallery url
		$url = $this->base . $p->next_line()->dup()
			->cut_between('<h2><a href="', '"')
		->to_s();
		$ret['gallery_url'] = $url;
		// # images
		$m = $p->curr_line()->regex_match('/Total No of Images in Gallery: (\d+)/');
		$ret['pages'] = $m ? $m[1] : 0;
		// thumbnails
		// $ret['thumbs'] = $p->curr_line()->extract_to_array('src="', '"');
		// do {
			// $ret['thumbs'][] = $p->curr_line()->dup()->cut_between('src="', '"')->to_s();
		// } while ($p->next_line()->contain('src="'));
		// KINI thumbs MENYIMPAN ID
		$p->reset_line();
		$p->go_line("rel='shortlink'");
		$ret['thumbs'] = $p->curr_line()->dup()->cut_between('?p=', "'")->to_s();
		
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
		if (isset($_GET['limitpage'])) {
			$tot_pages = $_GET['limitpage'];
		}
		for ($i=1; $i<=$tot_pages; $i++) {
			$p = new Page($start_page_url . (($i==1) ? '' : ('page/'.$i.'/')));
			echo "Grabbing ".$p->url()."<br/>\n";
			// grab all chapter in this page
			$t_content = new Text($p->content());
			$raw = array_unique($t_content->extract_to_array('href="', '"'));
			foreach ($raw as $e) {
				if (preg_match('/^http:\/\/hentaimangaonline\.com\/read-[^\/]*-hentai-manga-online\/$/', $e)) {
					if ($check_database) {
						if ($this->url_already_exist($e)) return array_unique($chapters);
					}
					$chapters[] = $e;
				}
			}
			// return $chapters;//DEBUG
		}
		return (array_unique($chapters));
	}
	
	public function add_book($info) {
		$book = Model::factory('Book')->create();
		$book->hydrate($info);
		$book->save();
		return $book;
	}
	
	public function add_or_edit_book($url, $info) {
		$book = Model::factory('Book')->where('url', $url)->find_one();
		if (!$book) {
			$book = Model::factory('Book')->create();
		}
		$book->set_from_array($info);
		$book->save();
		return $book;
	}
	
	public function url_already_exist($url) {
		$n = ORM::for_table('book')->where('url', $url)->count();
		return $n > 0;
	}
	
	public function stage_update() {
		// $update_url = $this->base;
		$update_url = 'http://hentaimangaonline.com/category/hentai-manga/english-hentai-manga/';
		$links = $this->grab_chapter_urls($update_url, true);
		$n = count($links);
		// echo '<pre>';print_r($links);exit;//DEBUG
		foreach ($links as $l) {
			echo "Saving {$l}<br/>\n";
			try {
				$info = $this->extract_info($l);
				$this->add_book($info);
			} catch (Exception $e) {
				echo "Cancelled {$l}: <br/><pre>{$e}</pre>\n";
			}
		}
	}
	
	public function stage_search() {
		echo "<form method='post'>";
		// search result
		if ($_POST) {
			if (!$this->post->page) $this->post->page = 1;
			if (!$this->post->perpage) $this->post->perpage = 20;
			$q = Model::factory('Book')->where_gt('pages', 0);
			if ($this->post->find) {
				$q
					->where_raw('(title LIKE ? OR description LIKE ? OR tags LIKE ?)', 
					array("%{$this->post->find}%", "%{$this->post->find}%", "%{$this->post->find}%"))
				;
			}
			if ($this->post->without) {
				$q->where_not_like('tags', "%{$this->post->without}%");
			}
			if ($this->post->order_asc) {
				$q->order_by_asc($this->post->order_asc);
			}
			if ($this->post->order_desc) {
				$q->order_by_desc($this->post->order_desc);
			}
			$q->limit($this->post->perpage)->offset(($this->post->page-1) * $this->post->perpage);
			$books = $q->find_many();
			
			foreach ($books as $b) {
				if (!$b->thumbs) {
					$info = $this->extract_info($b->url);
					$b = $this->add_or_edit_book($b->url, $info);
				}
			?>
				<p>
					<?php echo "{$b->title} | {$b->pages} pages | {$b->submit_date}"; ?> | 
					<a href="?stage=download&id=<?php echo $b->id; ?>" target="_blank">Download</a>
					<a href="<?php echo $b->url; ?>" target="_blank">Origin</a>
					<a href="?stage=respider&id=<?php echo $b->id; ?>" target="_blank">Re-spider</a>
					<br/>
					<table border="1"><tr><?php $i=0; foreach ($b->tags() as $t) {
						if ($i>0 && ($i%7)==0) echo '</tr><tr>';
						echo "<td>$t</td>";
					$i++; } ?></tr></table>
					<?php foreach ($b->thumbnails() as $t) {
						echo "<img src='{$t}' alt='{$b->slug}' width=160 height=250 />";
					} ?>
				</p>
			<?php
			}
			// previous and next
			?>
			&lt;&lt; <input type="submit" name="page" value="<?php echo $this->post->page-1; ?>" />
			<input type="submit" name="page" value="<?php echo $this->post->page+1; ?>" /> &gt;&gt;
			<?php
		}
		// search form
		?>
		<div>
			Find: <input type="text" name="find" value="<?php echo $this->post->find; ?>" /><br/>
			Without: <input type="text" name="without" value="<?php echo $this->post->without; ?>" /><br/>
			Per Page: <input type="text" name="perpage" value="<?php echo $this->post->perpage; ?>" /><br/>
			Order (ASC): <input type="text" name="order_asc" value="<?php echo $this->post->order_asc; ?>" /><br/>
			Order (DESC): <input type="text" name="order_desc" value="<?php echo $this->post->order_desc; ?>" /><br/>
			<input type="submit" name="page" value="1" />
		</div>
		<?php
		echo "</form>";
	}
	
	public function stage_download() {
		$b = Model::factory("Book")->find_one($_REQUEST['id']);
		$b->image_links();
		?>
		<button type="button" onclick="change_ext()">Change PNG</button>
		<script type="text/javascript">
		function change_ext() {
			var list = document.getElementsByTagName("a");
			for (var i in list) {
				list[i].href = list[i].href.replace(/\.jpg$/, '.png');
			}
		}
		</script>
		<?php
	}
	
	public function stage_respider() {
		$b = Model::factory("Book")->find_one($_REQUEST['id']);
		$info = $this->extract_info($b->url);
		$this->add_or_edit_book($b->url, $info);
	}
	
	public function stage_fillblanks() {
		$update_url = $this->base;
		$links = $this->grab_chapter_urls($update_url);
		foreach ($links as $link) {
			if (!$this->url_already_exist($link)) {
				echo "Saving {$link}<br/>\n";
				$info = $this->extract_info($link);
				$this->add_book($info);
			}
		}
	}
	
	// karena ada perubahan domain dari readhentaionline.com jadi hentaimangaonline.com
	// bikin jadi tidak tergantung nama domain, kah?
	public function stage_convert_domain() {
		$old_domain = 'http://readhentaionline.com/';
		$new_domain = 'http://hentaimangaonline.com/';
		$all = Model::factory('Book')->find_many();
		// strip domain name from url
		foreach ($all as $book) {
			$book->url = str_replace($old_domain, $new_domain, $book->url);
			$book->gallery_url = str_replace($old_domain, $new_domain, $book->gallery_url);
			$book->save();
		}
	}
	
	// all rho cache gone, so we need to retrieve the new thumbnails
	public function stage_respider_all() {
		// error: 6562, 7640, 12673
		$all = Model::factory('Book')->where_gt('id', 7640)->find_many();
		foreach ($all as $b) {
			echo $b->id.' '.$b->url."<br>\n";
			$info = $this->extract_info($b->url);
			$b->set_from_array($info);
			$b->save();
		}
	}
}
$a = new Readhentaionline();
$a->run();