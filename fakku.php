<?php
/*
Spider for Fakku.net

hanya yg English
komik terdiri atas 2: 
	manga http://www.fakku.net/manga/english
	doujinshi http://www.fakku.net/doujinshi/english
sebenarnya keduanya sama:
	judul
	series
	artist
	date
	desc
	tags
	sample (2 urls)
	url
	thumbs (null first, concatenated extension only)
	pattern (null first, full image url pattern) e.g. http://cdn.fakku.net/8041E1/c/manga/t/tokubetsunakiminiainotewo_e/images/?.jpg
	
pada halaman list ada 2 thumbnail kecil:
	http://cdn.fakku.net/8041E1/t/manga/m/mugitoazukouhenfixed_e/thumbs/[691] 2362 - Mugi to Azu Kouhen fixed (English) 001.thumb.jpg
	http://cdn.fakku.net/8041E1/t/manga/m/mugitoazukouhenfixed_e/thumbs/[691] 2362 - Mugi to Azu Kouhen fixed (English) 008.thumb.png
	atau
	http://cdn.fakku.net/8041E1/t/manga/e/eldersister_e/cover.gif
	http://cdn.fakku.net/8041E1/t/manga/e/eldersister_e/sample.gif

pada halaman read online ada 2 informasi penting: 
	url semua thumbnail
		dalam bentuk json
	url semua full image
		dalam bentuk pattern
*/
include 'class/idiorm.php';
include 'class/paris.php';
include_once 'class/text.php';
include_once 'class/page.php';
include 'class/simple_html_dom.php';

class Hmanga extends Model {
	public function save() {
		if (is_array($this->tags)) {
			$this->tags = '#'.implode('#', $this->tags).'#';
		}
		if (is_array($this->sample)) {
			$this->sample = implode('#', $this->sample);
		}
		if (is_array($this->thumbs)) {
			$this->thumbs = implode('#', $this->thumbs);
		}
		parent::save();
	}
	
	// how many pages are there
	public function count() {
		return substr_count($this->thumbs, '#')+1;
	}
	
	// versi lama, nama thumbnail image hanya berupa 001.gif
	// url sample tidak mengandung /thumbs/
	public function is_type_1() {
		$sample = current(explode('#', $this->sample));
		return strpos($sample, '/thumbs/') === false;
	}
	
	// nama thumbnail image hanya berupa 001.jpg
	// url sample mengandung /thumbs/ tapi filename tidak mengandung .thumb.
	public function is_type_2() {
		$sample = current(explode('#', $this->sample));
		return strpos($sample, '/thumbs/') !== false 
			&& strpos(basename($sample), '.thumb.') === false;
	}
	
	// versi terbaru, nama thumbnail image berupa [ARTIST] SERIES - TITLE (English) 001.thumb.jpg
	// url sample mengandung /thumbs/ DAN filename mengandung .thumb.
	public function is_type_3() {
		$sample = current(explode('#', $this->sample));
		return strpos($sample, '/thumbs/') !== false
			&& strpos(basename($sample), '.thumb.') !== false;
	}
	
	// from thumbs and pages generate all thumbnail image urls
	public function thumbnails() {
		$thumbnails = array();
		$sample = current(explode('#', $this->sample));
		if ($this->is_type_1()) {
			$pre = dirname($sample) . '/thumbs/';
			$post = '';
		} else if ($this->is_type_2()) {
			$pre = substr($sample, 0, -7);
			$post = '';
		} else { // type 3
			$pre = substr($sample, 0, -13);
			$pre = str_replace('/t/manga/', '/t/images/manga/', $pre);
			$post = '.thumb';
		}
		$ext = explode('#', $this->thumbs);
		for ($i=1, $n=$this->count(); $i<=$n; $i++) {
			$val = str_pad($i, 3, '0', STR_PAD_LEFT);
			$thumbnails[] = $pre . $val . $post . '.' . $ext[$i-1];
		}
		return $thumbnails;
	}
	
	// from thumbs and pages generate all full image urls
	public function pages() {
		$pages = array();
		for ($i=1, $n=$this->count(); $i<=$n; $i++) {
			$val = str_pad($i, 3, '0', STR_PAD_LEFT);
			$pages[] = str_replace('?', $val, $this->pattern);
		}
		return $pages;
	}
	
	// get detailed information thumbs & pattern
	public function get_detail() {
		$p = new Page(Fakku::$base . $this->url . '/read');
		// grab thumbs extension
		$p->go_line('var data = {');
		$json = $p->curr_line()->dup()->cut_between(' = ', ';')->to_s();
		$obj = json_decode($json);
		$thumbs = array();
		foreach ($obj->thumbs as $tpath) {
			$thumbs[] = substr(basename($tpath), -3);
		}
		$this->thumbs = $thumbs;
		
		// grab full image pattern
		$p->go_line('function imgpath(');
		$p->go_line('return \'');
		$imgpath = $p->curr_line()->dup()->cut_between("return '", "';")->to_s();
		$imgpath = str_replace("' + x + '", '?', $imgpath);
		$this->pattern = $imgpath;
		
		$this->save();
	}
}

// Main program
class Fakku {
	public static $cdn = 'http://cdn.fakku.net/8041E1/t/';
	public static $base = 'http://www.fakku.net';
	
	public static function create() {
		return new Fakku();
	}
	
	public function create_database() {
		ORM::get_db()->query('CREATE TABLE `hmanga` (
			`id` integer NOT NULL CONSTRAINT pid PRIMARY KEY AUTOINCREMENT,
			`title` varchar NOT NULL,
			`series` varchar NOT NULL,
			`artist` varchar NOT NULL,
			`date` varchar NOT NULL,
			`desc` varchar NULL,
			`tags` varchar NULL,
			`sample` text NOT NULL,
			`url` varchar NOT NULL,
			`thumbs` text NULL,
			`pattern` varchar NULL
		)');
	}
	
	public function run() {
		// header
		$title = 'Fakku scraper';
		include '_header.php'; // loaded with bootstrap
		// DB
		$dbpath = './sqlite/fakku.db';
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
	
	// initial database insert
	public function action_init() {
		// hardcoded page number for quick'n'dirty
		$data = array(
			array('http://www.fakku.net/manga/english', 343),
			array('http://www.fakku.net/doujinshi/english', 258)
		);
		foreach ($data as $dd) {
			$starting_url = $dd[0];
			for ($page=$dd[1]; $page>=1; $page--) { // grab from last page
				$p = new Page($starting_url . ($page > 1 ? '/page/'.$page : ''));
				$infos = $this->extract_from_page($p);
				$infos = array_reverse($infos);
				foreach ($infos as $info) {
					echo $info['title']."<br>\n";
					$this->add_hmanga($info);
				}
			}
		}
	}
	
	// extract information from given Page object
	public function extract_from_page($p) {
		$infos = array();
		$html = new simple_html_dom();
		$html->load($p->content());
		$content = $html->find('#content', 0);
		foreach ($content->find('div.content-row') as $row) {
			try {
				$item = array();
				
				$sample1 = $row->find('img.cover', 0);
				$sample2 = $row->find('img.sample', 0);
				$item['sample'] = array($sample1->src, $sample2->src);
				
				$title = $row->find('h2 a', 0);
				$item['url'] = rawurldecode($title->href);
				$item['title'] = html_entity_decode($title->plaintext, ENT_COMPAT, 'UTF-8');
				
				$series = $row->find('div.left', 0)->find('a', 0);
				if (!$series) { // malformed
					echo 'Cancelled '.$item['url']."<br>";
					continue;
				}
				$item['series'] = html_entity_decode($series->plaintext, ENT_COMPAT, 'UTF-8');
				
				$artist = $row->find('div.left', 1)->find('a', 0);
				$item['artist'] = html_entity_decode($artist->plaintext, ENT_COMPAT, 'UTF-8');
				
				$date = $row->find('div.small', 0)->find('div.right', 0)->find('b', 0);
				$item['date'] = date('Y-m-d', strtotime($date->plaintext));
				
				$desc = $row->find('div.short', 0);
				$item['desc'] = trim($desc->plaintext);
				
				$tags = $row->find('div.short', 1);
				$item['tags'] = array();
				foreach ($tags->find('a') as $a) {
					$item['tags'][] = $a->plaintext;
				}
				$infos[] = $item;
			} catch (Exception $e) {
				echo 'Cancelled '.$item['url'];
				continue;
			}
		}
		return $infos;
	}
	
	public function add_hmanga($data) {
		$hmanga = Model::factory('Hmanga')->create();
		$hmanga->hydrate($data);
		$hmanga->save();
	}
	
	public function action_update() {
		foreach (array(
			'http://www.fakku.net/manga/english', 
			'http://www.fakku.net/doujinshi/english'
		) as $starting_url) {
			$page = 1;
			$next = true;
			$to_add = array();
			while ($next) {
				$p = new Page($starting_url . ($page > 1 ? '/page/'.$page : ''));
				$infos = $this->extract_from_page($p);
				foreach ($infos as $k => $info) {
					if ($this->is_already_exist($info)) {
						unset($infos[$k]);
						$next = false;
					}
				}
				$to_add = array_merge($to_add, $infos);
				$page++;
			}
			$to_add = array_reverse($to_add);
			// save
			foreach ($to_add as $info) {
				echo $info['url']."<br>\n";
				$this->add_hmanga($info);
			}
		}
	}
	
	public function action_search() {
		$search = array(
			'any' => explode(' ', @$_POST['any']),
			'artist' => explode(' ', @$_POST['artist']),
			'title' => explode(' ', @$_POST['title']),
			'desc' => explode(' ', @$_POST['desc']),
			'series' => explode(' ', @$_POST['series']),
			'tags' => explode(' ', @$_POST['tags']),
		);
		$perpage = isset($_POST['perpage']) ? (int)$_POST['perpage'] : 20;
		$order = isset($_POST['order']) ? $_POST['order'] : 'date desc';
		$curpage = isset($_POST['curpage']) ? (int)$_POST['curpage'] : 1;
		if (isset($_POST['next'])) {
			$curpage++;
		} else if (isset($_POST['prev'])) {
			$curpage--;
		}
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
					<label class="control-label">Artist</label>
					<div class="controls">
						<input type="text" name="artist" value="<?php echo @$_POST['artist']; ?>">
					</div>
				</div>
			</div>
			<div class="control-group">
				<div class="span6">
					<label class="control-label">Title</label>
					<div class="controls">
						<input type="text" name="title" value="<?php echo @$_POST['title']; ?>">
					</div>
				</div>
			
				<div class="span6">
					<label class="control-label">Desc</label>
					<div class="controls">
						<input type="text" name="desc" value="<?php echo @$_POST['desc']; ?>">
					</div>
				</div>
			</div>
			<div class="control-group">
				<div class="span6">
					<label class="control-label">Series</label>
					<div class="controls">
						<input type="text" name="series" value="<?php echo @$_POST['series']; ?>">
					</div>
				</div>
			
				<div class="span6">
					<label class="control-label">Tags</label>
					<div class="controls">
						<input type="text" name="tags" value="<?php echo @$_POST['tags']; ?>">
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
						<label class="radio inline">
							<input type="radio" name="order" value="date desc" <?php echo $order=='date desc'?'checked':''; ?>> date DESC
						</label>
						<label class="radio inline">
							<input type="radio" name="order" value="date asc" <?php echo $order=='date asc'?'checked':''; ?>> date ASC
						</label>
						<label class="radio inline">
							<input type="radio" name="order" value="title asc" <?php echo $order=='title asc'?'checked':''; ?>> title ASC
						</label>
						<label class="radio inline">
							<input type="radio" name="order" value="title desc" <?php echo $order=='title desc'?'checked':''; ?>> title DESC
						</label>
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
			
	<?php
		$q = Model::factory('Hmanga')
			->limit($perpage)
			->offset(($curpage-1) * $perpage);
		switch ($order) {
			default:
			case 'date desc': $q->order_by_desc('date'); break;
			case 'date asc': $q->order_by_asc('date'); break;
			case 'title asc': $q->order_by_asc('title'); break;
			case 'title desc': $q->order_by_desc('title'); break;
		}
		// filter
		foreach ($search['title'] as $term) { if ($term) {
			$q->where_like('title', "%{$term}%");
		}}
		foreach ($search['series'] as $term) { if ($term) {
			$q->where_like('series', "%{$term}%");
		}}
		foreach ($search['artist'] as $term) { if ($term) {
			$q->where_like('artist', "%{$term}%");
		}}
		foreach ($search['desc'] as $term) { if ($term) {
			$q->where_like('desc', "%{$term}%");
		}}
		foreach ($search['tags'] as $term) { if ($term) {
			$q->where_like('tags', "%#{$term}#%");
		}}
		foreach ($search['any'] as $term) { if ($term) {
			$q->where_raw('(title LIKE ? OR series LIKE ? OR artist LIKE ? OR desc LIKE ? OR tags LIKE ?)', 
			array("%{$term}%", "%{$term}%", "%{$term}%", "%{$term}%", "%{$term}%"));
		}}
		$result = $q->find_many();
	?>
		<?php foreach ($result as $hmanga) : ?>
			<div class="span6 result">
				<?php $samples = explode('#', $hmanga->sample); ?>
				<a href="?action=view&id=<?php echo $hmanga->id; ?>">
					<img src="<?php echo $samples[0];?>" alt="th">
					<img src="<?php echo $samples[1];?>" alt="th">
				</a>

				<dl class="dl-horizontal result">
					<dt>Title</dt><dd><a href="?action=view&id=<?php echo $hmanga->id; ?>"><?php echo $hmanga->title; ?></a></dd>
					<dt>Series</dt><dd><?php echo $hmanga->series; ?></dd>
					<dt>Artist</dt><dd><?php echo $hmanga->artist; ?></dd>
					<dt>Date</dt><dd><?php echo $hmanga->date; ?></dd>
					<dt>Tags</dt><dd><?php echo str_replace('#', ' ', $hmanga->tags); ?></dd>
					<dt><a href="?action=view&id=<?php echo $hmanga->id; ?>">VIEW</a></dt>
					<dd><a href="<?php echo Fakku::$base.$hmanga->url; ?>">ORIGIN</a></dd>
				</dl>
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
	
	public function action_view() {
		$id = $_GET['id'];
		$hmanga = Model::factory('Hmanga')->find_one($id);
		if (!$hmanga->thumbs || !$hmanga->pattern) {
			$hmanga->get_detail();
		}
		?>
		
		<dl class="dl-horizontal">
			<dt>Title</dt><dd><a href="<?php echo Fakku::$base.$hmanga->url; ?>"><?php echo $hmanga->title; ?></a></dd>
			<dt>Series</dt><dd><?php echo $hmanga->series; ?></dd>
			<dt>Artist</dt><dd><?php echo $hmanga->artist; ?></dd>
			<dt>Date</dt><dd><?php echo $hmanga->date; ?></dd>
			<dt>Description</dt><dd><?php echo $hmanga->desc; ?></dd>
			<dt>Tags</dt><dd><?php echo str_replace('#', ' ', $hmanga->tags); ?></dd>
			<dd><a href="<?php echo Fakku::$base.$hmanga->url; ?>">ORIGIN</a></dd>
		</dl>
		
		<ul class="thumbnails">
		<?php foreach ($hmanga->thumbnails() as $th) : ?>
			<li><img src="<?php echo $th; ?>" alt="th"></li>
		<?php endforeach; ?>
		</ul>
		
		<?php foreach ($hmanga->pages() as $pg) : ?>
			<a href="<?php echo $pg; ?>"><?php echo $hmanga->title; ?></a>
		<?php endforeach; ?>
		
		<?php
	}
	
	public function is_already_exist($info) {
		$n = ORM::for_table('hmanga')->where('url', $info['url'])->count();
		return $n > 0;
	}
	
	public function action_test() {
		
	}
}
Fakku::create()->run();