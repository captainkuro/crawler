<?php
/*
Spider for hentaimangaonline.com

Halaman utama berisi daftar hentai manga sort by submitted descending
http://hentaimangaonline.com/category/hentai-manga/english-hentai-manga/

Di dalam satu halaman info manga, contoh
http://hentaimangaonline.com/read-nectar-ch-8-english-hentai-manga-online/
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

*/
include 'class/idiorm.php';
include 'class/paris.php';
include 'class/simple_html_dom.php';

class Hmanga extends Model {
	public static $thumb_pattern = "http://hentaimangaonline.com/wp-content/themes/snapshot/thumb.php?src=images/%s/%s.jpg&w=160&h=250&zc=1&q=90";
	public static $image_pattern = "http://hentaimangaonline.com/images/%s/%s.jpg";

	public function slug() {
		preg_match('/read-(.+)-hentai-manga-online/', $this->url, $m);
		return rawurldecode($m[1]);
	}

	public function thumbnails() {
		$id = $this->real_id;
		$n = min($this->pages, 8);
		$thumbs = array();
		for ($i=1; $i<=$n; $i++) {
			$thumbs[] = sprintf(self::$thumb_pattern, $id, $i);
		}
		return $thumbs;
	}

	public function pages() {
		$id = $this->real_id;
		$pages = array();
		for ($i=1; $i<=$this->pages; $i++) {
			$pages[] = sprintf(self::$image_pattern, $id, $i);
		}
		return $pages;
	}

	public function samples() {
		$id = $this->real_id;
		$thumbs = array(
			sprintf(self::$thumb_pattern, $id, 1),
			sprintf(self::$thumb_pattern, $id, 2),
			sprintf(self::$thumb_pattern, $id, 3),
		);
		return $thumbs;
	}
}

// Main program
class HentaiMangaOnline {
	public static $update = 'http://hentaimangaonline.com/category/hentai-manga/english-hentai-manga/';
	public static $base = 'http://hentaimangaonline.com';
	
	public static function create() {
		return new HentaiMangaOnline();
	}

	public function create_database() {
		ORM::get_db()->query('CREATE TABLE `hmanga` (
		  `id` integer NOT NULL CONSTRAINT pid PRIMARY KEY AUTOINCREMENT,
		  `url` varchar NOT NULL,
		  `real_id` int NOT NULL,
		  `title` varchar NOT NULL,
		  `date` varchar NOT NULL,
		  `description` text NOT NULL,
		  `pages` integer NOT NULL,
		  `gallery_url` varchar NOT NULL,
		  `tags` text NOT NULL
		)');
	}
	
	public function run() {
		// header
		$title = 'HentaiMangaOnline scraper';
		include '_header.php'; // loaded with bootstrap
		// DB
		$dbpath = './sqlite/hmo.db';
		$empty_database = false;
		if (!is_file($dbpath)) {
			touch($dbpath);
			$empty_database = true;
		}
		$dbpath = realpath($dbpath);
		ORM::configure('sqlite:' . $dbpath);
		if ($empty_database) $this->create_database();
		// process
		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
	?>
		<ul class="nav nav-tabs">
			<li><a href="?action=search">Search</a></li>
			<li><a href="?action=update">Update</a></li>
		</ul>
	<?php
		$method = 'action_'.$action;
		if (method_exists($this, $method)) {
			$this->$method();
		} else {
			echo 'Choose something';
		}
		// footer
		include '_footer.php';
	}

	public function action_all_pages() {
		$start = self::$update;
		$stop = 256;
		$pre_infos = array();

		for ($i=$stop; $i>=1; $i--) {
			//echo $start."\n";
			$p = new Page($start.($i>1 ? 'page/'.$i.'/':''));
			$chunk = array_reverse($this->extract_from_list($p));
			$pre_infos = array_merge($pre_infos, $chunk);
		}
		// Now we have complete books' links
		$complete_links = '<?php $links='.var_export($pre_infos, true).';';
		file_put_contents('hmo.links', $complete_links);
	}
	
	// initial database insert
	public function action_init() {
		include 'hmo.links';
		foreach ($links as $info) {
			echo $info['url']."<br>\n";flush();
			$p = new Page(self::$base.$info['url']);
			$data = $this->extract_from_page($p);
			$data = $info + $data;
			$this->add_hmanga($data);
		}
	}

	// get all hmanga info available in this list
	public function extract_from_list($p) {
		$chapters = array();
		echo "Grabbing ".$p->url()."<br/>\n";
		// grab all chapter in this page
		$html = new simple_html_dom();
		$html->load($p->content());
		$table = $html->find('table.table-condensed', 0);
		foreach ($table->find('tr') as $tr) {
			if (count($tr->find('td')) < 2) continue;
			$info = array();
			// date
			$td_date = $tr->find('td', 0);
			$date = trim($td_date->plaintext);
			if ($date == 'Today') {
				$date = date('Y-m-d');
			} else if ($date == 'Yesterday') {
				$time = new DateTime();
				$time->modify('-1 day');
				$date = $time->format('Y-m-d');
			} else if ($date == 'This Week') {
				$time = new DateTime();
				$time->modify('-7 day');
				$date = $time->format('Y-m-d');
			}
			$info['date'] = $date;
			// other
			$td_other = $tr->find('td', 1);
			$gallery = $td_other->find('a', 0)->href;
			$info['gallery_url'] = str_replace(self::$base, '', $gallery);

			$download = new Text($td_other->find('a', 1)->href);
			$info['real_id'] = $download->cut_after('id=')->to_s();

			$a_page = $td_other->find('a', 2);
			$url = $a_page->href;
			$info['url'] = str_replace(self::$base, '', $url);
			$info['title'] = html_entity_decode($a_page->title, ENT_COMPAT, 'UTF-8');

			$chapters[] = $info;
		}
		return $chapters;
	}
	
	// extract information from given Page object
	public function extract_from_page($p) {
		$ret = array();
		// description
		$p->go_line('Manga Info :<');
		$m = $p->curr_line()->regex_match('/Manga Info :(.*)$/');
		$also_has_fav = $p->curr_line()->contains('Add To Favorites');
		if ($m && $also_has_fav) {
			$description = $p->curr_line()->cut_between('Manga Info :', "<div class='watch-action'>");
			$description = $description->replace('<br>', "\n")->replace('<br/>', "\n")->replace('<br />', "\n");
			$ret['description'] = trim(html_entity_decode(strip_tags($description), ENT_COMPAT, 'UTF-8'));
			$p->reset_line();
		} else if ($m) {
			$part = $m[1];
			while (!$p->next_line()->contain('Add To Favorites')) {
				$part .= $p->curr_line()->to_s();
			}
			$description = new Text($part);
			$description = $description->replace('<br>', "\n")->replace('<br/>', "\n")->replace('<br />', "\n");
			$part = $description->cut_before("<div class='watch-action'>") . $description->cut_rafter('</div>');
			$ret['description'] = html_entity_decode(strip_tags($part), ENT_COMPAT, 'UTF-8');
			$p->reset_line();
		} else {
			$ret['description'] = '';
			$p->reset_line();
		}
		// tags
		$p->go_line('Tags: ');
		$ret['tags'] = array();
		if ($p->curr_line()->contain('Tags: ')) {
			$raw = $p->curr_line()->cut_until('<br/>');
			foreach ($raw->extract_to_array('">', '<') as $tag) {
				$ret['tags'][] = html_entity_decode($tag, ENT_COMPAT, 'UTF-8');
			}
			$ret['tags'] = '#'.implode('#', $ret['tags']).'#';
		} else {
			$ret['tags'] = '##';
			$p->reset_line();
		}
		
		$p->go_line('id="images"');
		// # images
		$m = $p->next_line()->regex_match('/Total No of Images in Gallery: (\d+)/');
		$ret['pages'] = $m ? $m[1] : 0;

		return $ret;
	}
	
	public function add_hmanga($data) {
		$hmanga = Model::factory('Hmanga')->create();
		$hmanga->hydrate($data);
		$hmanga->save();
	}
	
	public function is_already_exist($info) {
		$n = ORM::for_table('hmanga')->where('url', $info['url'])->count();
		return $n > 0;
	}
	
	public function action_update() {
		$start = self::$update;
		$page = 1;
		$stop = false;
		$pre_infos = array();
		while (!$stop) {
			$p = new Page($start.($page>1 ? 'page/'.$page.'/':''));
			$chunk = $this->extract_from_list($p);
			foreach ($chunk as $row) {
				if ($this->is_already_exist($row)) {
					$stop = true;
					break;
				} else {
					$pre_infos[] = $row;
				}
			}
			$page++;
		}
		$pre_infos = array_reverse($pre_infos);
		foreach ($pre_infos as $info) {
			echo $info['url']."<br>\n";flush();
			if ($this->is_already_exist($info)) continue;
			$p = new Page(self::$base.$info['url']);
			try {
				$data = $this->extract_from_page($p);
				$data = $info + $data;
				// echo '<pre>';print_r($data);exit;
				$this->add_hmanga($data);
			} catch (Exception $e) {
				echo '<pre>'.$e."</pre><br>\n";//die();
			}
				
		}
	}
	
	public function action_search() {
		$order_choices = array('date desc', 'date asc', 'title asc', 'title desc', 'id desc', 'id asc', 'pages asc', 'pages desc');
		$order = isset($_POST['order']) ? $_POST['order'] : 'date desc';

		$perpage = isset($_POST['perpage']) ? (int)$_POST['perpage'] : 20;
		$curpage = isset($_POST['curpage']) ? (int)$_POST['curpage'] : 1;
		if (isset($_POST['next'])) {
			$curpage++;
		} else if (isset($_POST['prev'])) {
			$curpage--;
		}
		if ($curpage < 1) $curpage = 1;

		$result = $this->search($perpage, $curpage, $order, @$_POST['any'], @$_POST['title']);
	?>
		<form class="form-horizontal" method="post">
			<div class="control-group">
				<div class="span6">
					<label class="control-label">Any</label>
					<div class="controls">
						<input type="text" name="any" value="<?php echo @$_POST['any']; ?>">
					</div>
				</div>
			
				<div class="span6">
					<label class="control-label">Title</label>
					<div class="controls">
						<input type="text" name="title" value="<?php echo @$_POST['title']; ?>">
					</div>
				</div>
			</div>
			<div class="control-group">
				<div class="span3">
					<label class="control-label">Per Page</label>
					<div class="controls">
						<input type="text" name="perpage" value="<?php echo $perpage; ?>" class="input-mini">
					</div>
				</div>
				
				<div class="span3">
					<label class="control-label">Page</label>
					<div class="controls">
						<input type="text" name="curpage" value="<?php echo $curpage; ?>" class="input-mini">
					</div>
				</div>
				
				<div class="span6">
					<label class="control-label">Order</label>
					<div class="controls">
						<?php foreach ($order_choices as $choice) : ?>
							<label class="radio inline">
								<input type="radio" name="order" value="<?php echo $choice;?>" <?php echo $order==$choice?'checked':''; ?>> <?php echo $choice; ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<div class="control-group">
				<div class="controls">
					<button type="submit" class="btn" name="search">Search</button>
					<button type="submit" class="btn" name="prev">&lt;&lt; Prev</button>
					<button type="submit" class="btn" name="next">Next &gt;&gt;</button>
				</div>
			</div>
		
			<?php foreach ($result as $hmanga) : ?>
				<div class="span6 result" style="height:350px">
					<?php echo "{$hmanga->title} | {$hmanga->pages} pages | {$hmanga->date}"; ?>
					<a href="?action=view&id=<?php echo $hmanga->id; ?>">VIEW</a>
					<a href="<?php echo HentaiMangaOnline::$base.$hmanga->url; ?>">ORIGIN</a>
					<br>
					<?php $samples = $hmanga->samples(); ?>
					<a href="?action=view&id=<?php echo $hmanga->id; ?>" title="<?php echo $hmanga->description; ?>">
						<?php foreach ($samples as $img) : ?>
							<img src="<?php echo $img;?>" alt="th" width="150" height="234">
						<?php endforeach; ?>
					</a>
					<?php echo str_replace('#', ', ', trim($hmanga->tags, '#')); ?>
				</div>
			<?php endforeach; ?>

			<div class="control-group" style="display:block;clear:both">
				<div class="controls">
					<button type="submit" class="btn" name="prev">&lt;&lt; Prev</button>
					<button type="submit" class="btn" name="next">Next &gt;&gt;</button>
				</div>
			</div>
		</form>
		<?php
	}

	public function search($perpage, $curpage, $order, $any, $title) {
		$q = Model::factory('Hmanga')
			->limit($perpage)
			->offset(($curpage-1) * $perpage);
		list($column, $direction) = explode(' ', $order);
		if ($direction == 'desc') {
			$q->order_by_desc($column);
		} else {
			$q->order_by_asc($column);
		}
		// filter
		$search_any = Text::parse_search_term($any);
		foreach ($search_any['include'] as $term) {
			$q->where_raw('(title LIKE ? OR description LIKE ? OR tags LIKE ?)', 
				array("%{$term}%", "%{$term}%", "%{$term}%"));
		}
		foreach ($search_any['exclude'] as $term) {
			$q->where_raw('(title NOT LIKE ? AND description NOT LIKE ? AND tags NOT LIKE ?)', 
				array("%{$term}%", "%{$term}%", "%{$term}%"));
		}

		$search_title = Text::parse_search_term($title);
		foreach ($search_title['include'] as $term) {
			$q->where_like('title', "%{$term}%");
		}
		foreach ($search_title['exclude'] as $term) {
			$q->where_not_like('title', "%{$term}%");
		}
		return $q->find_many();
	}
	
	public function action_view() {
		$id = $_GET['id'];
		$hmanga = Model::factory('Hmanga')->find_one($id);
		$thumbnails = $hmanga->thumbnails();
		$pages = $hmanga->pages();

		?>
		
		<dl class="dl-horizontal">
			<dt>Title</dt><dd><a href="<?php echo HentaiMangaOnline::$base.$hmanga->url; ?>"><?php echo $hmanga->title; ?></a></dd>
			<dt>Real ID</dt><dd><?php echo $hmanga->real_id; ?></dd>
			<dt>Date</dt><dd><?php echo $hmanga->date; ?></dd>
			<dt>Description</dt><dd><?php echo $hmanga->description; ?></dd>
			<dt>Page</dt><dd><?php echo $hmanga->pages; ?></dd>
			<dt>Tags</dt><dd><?php echo str_replace('#', ', ', trim($hmanga->tags, '#')); ?></dd>
			<dd><a href="<?php echo HentaiMangaOnline::$base.$hmanga->url; ?>">ORIGIN</a></dd>
		</dl>
		
		<ul class="thumbnails">
		<?php foreach ($thumbnails as $i => $th) : ?>
			<li>
				<a href="<?php echo $pages[$i]; ?>">
					<img src="<?php echo $th; ?>" alt="<?php echo $hmanga->title; ?>">
				</a>
			</li>
		<?php endforeach; ?>
		</ul>
		<?php for ($i=count($thumbnails), $n=count($pages); $i<$n; $i++) : ?>
			<a href="<?php echo $pages[$i]; ?>"><?php echo $hmanga->title; ?></a>
		<?php endfor; ?>

		<?php
	}
	
	public function action_test() {
		?>
		<form method="post">
			<textarea name="query"><?php echo @$_REQUEST['query']; ?></textarea>
			<button>Submit</button>
		</form>
		<?php
		if (isset($_REQUEST['query'])) {
			$query = $_REQUEST['query'];
			$result = ORM::for_table('hmanga')->raw_query($query, array())->find_many();
			echo "<pre>";
			echo $query.PHP_EOL;
			foreach ($result as $row) {
				print_r($row->as_array());
			}
			echo "</pre>";
		}
	}

	public function action_empty_tags() {
		$empties = Model::factory('Hmanga')
			->where('tags', '##')
			->find_many();
		foreach ($empties as $hmanga) {
			$p = new Page(HentaiMangaOnline::$base.$hmanga->url);
			echo $p->url().'<br>'.PHP_EOL;
			$new_info = $this->extract_from_page($p);
			echo $new_info['tags'].'<br><br>'.PHP_EOL;
			$hmanga->tags = $new_info['tags'];
			$hmanga->save();
		}
	}

	public function is_duplicate($hmanga) {
		$n = ORM::for_table('hmanga')
			->where('real_id', $hmanga->real_id)
			->where_not_equal('id', $hmanga->id)
			->count();
		return $n > 0;
	}

	public function action_remove_duplicate() {
		$all = Model::factory('Hmanga')
			->order_by_desc('id')
			->find_many();
		foreach ($all as $hmanga) {
			if ($this->is_duplicate($hmanga)) {
				echo $hmanga->id.PHP_EOL;
				$hmanga->delete();
			}
		}
	}
}
HentaiMangaOnline::create()->run();