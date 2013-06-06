<?php
/*
Spider for Fakku.net

hanya yg English
komik terdiri atas 2: 
	manga http://www.fakku.net/manga/english
	doujinshi http://www.fakku.net/doujinshi/english
sebenarnya keduanya sama:
	title
	series
	artist
	date
	desc
	tags
	url
	thumbs (null first, concatenated filename)
	pattern (null first, full image url pattern) e.g. http://cdn.fakku.net/8041E1/c/manga/t/tokubetsunakiminiainotewo_e/images/%s.jpg
	
pada halaman list ada 2 thumbnail kecil:
	langsung ambil dari thumbnail lengkap

pada halaman read online ada 2 informasi penting: 
	url semua thumbnail
		dalam bentuk json
	url semua full image
		dalam bentuk pattern

cdn.fakku.net/8041E1/t/images/manga/p/penisclub_e/thumbs/%5B1655%5D%202361%20-%20Penis%20Club%20(English)%20001.thumb.jpg
cdn.fakku.net/8041E1/c/manga/p/penisclub_e/images/001.jpg
cdn.fakku.net/8041E1/t/images/manga/t/theworldisyours_e/thumbs/001.gif
cdn.fakku.net/8041E1/c/manga/t/theworldisyours_e/images/001.jpg
*/
include 'class/idiorm.php';
include 'class/paris.php';
include 'class/simple_html_dom.php';

class Hmanga extends Model {
	public function count() {
		return substr_count($this->thumbs, '#')+1;
	}

	public function thumb_src($filename) {
		$pattern = new Text($this->pattern);
		// hack
		$hack_replace = 'http://www.fakku.net/';
		$thumb_pre = $pattern
			->replace('/images/', '/thumbs/')
			->replace('/c/manga/', '/t/images/manga/')
			// hack
			->replace('http://cdn.fakku.net/8041E1/t/', $hack_replace)
			->replace('http://c.fakku.net/', $hack_replace)
			->replace('http://img.fakku.net/', $hack_replace)
			->dirname()
			->to_s();
		return $thumb_pre.'/'.$filename;
	}

	public function src($i) {
		$padded = str_pad($i, 3, '0', STR_PAD_LEFT);
		return sprintf($this->pattern, $padded);
	}

	public function thumbnails() {
		if (!$this->thumbs) {
			$this->get_detail();
		}
		$thumbnails = array();
		$filenames = explode('#', $this->thumbs);
		foreach ($filenames as $filename) {
			$thumbnails[] = $this->thumb_src($filename);
		}
		return $thumbnails;
	}

	public function pages() {
		if (!$this->thumbs) {
			$this->get_detail();
		}
		$pages = array();
		for ($i=1, $n=$this->count(); $i<=$n; $i++) {
			$pages[] = $this->src($i);
		}
		return $pages;
	}

	// Fill thumbs and pattern
	public function get_detail() {
		$p = new Page(Fakku::$base . $this->url . '/read');
		$content = new Text($p->content());
		
		if ($content->contain('var data = {')) {
			$p->go_line('var data = {');
			$json = $p->curr_line()->dup()->cut_between(' = ', ';')->to_s();
			$obj = json_decode($json);
			$js_thumbs = $obj->thumbs;
		} else if ($content->contain('var data={')) {
			$p->go_line('var data={');
			$json = $p->curr_line()->dup()->cut_between('data=', ';')->to_s();
			$obj = json_decode($json);
			$js_thumbs = $obj->thumbs;
		} else if ($content->contain('window.params.thumbs')) {
			$p->go_line('window.params.thumbs');
			$json = $p->curr_line()->cut_between('=', ';')->to_s();
			$js_thumbs = json_decode($json);
		} else {
			throw new Exception('where is thumbs?');
		}

		$thumbs = array();
		foreach ($js_thumbs as $tpath) {
			$thumbs[] = basename($tpath);
		}
		$this->thumbs = implode('#', $thumbs);
		
		// grab full image pattern
		$p->go_line('function imgpath(');
		$p->go_line('return \'');
		if ($p->curr_line()->contain('return \'')) {
			$imgpath = $p->curr_line()->dup()->cut_between("return '", "';")->to_s();
			$imgpath = str_replace("' + x + '", '%s', $imgpath);
		} else {
			$p->reset_line();
			$p->go_line('function imgpath(');
			$p->go_line('return\'');
			$imgpath = $p->curr_line()->dup()->cut_between("return'", "';")->to_s();
			$imgpath = str_replace("'+x+'", '%s', $imgpath);
		}
		$this->pattern = $imgpath;
		
		$this->save();
	}

	public function samples() {
		$thumbs = $this->thumbnails();
		return array($thumbs[0], $thumbs[1]);
	}
}

// Main program
class Fakku {
	public static $cdn = 'http://cdn.fakku.net/8041E1/t/';
	public static $base = 'http://www.fakku.net';
	
	public static function create() {
		return new Fakku();
	}

	public static function cdn_to_src($cdn) {
		$sample = new Text($cdn);
		if ($sample->contain('http://1-ps.googleusercontent.com/h/www.fakku.net/')) {
			$src = $sample->replace('http://1-ps.googleusercontent.com/h/www.fakku.net/', 'http://cdn.fakku.net/8041E1/t/images/')
				->replace(',P20', '%20')
				->replace('/x,', '/%')
				->replace(',', '%')
				->cut_before('.pagespeed.');
			return $src->to_s();
		} else {
			return $cdn;
		}
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
			array('http://www.fakku.net/manga/english', 392),
			array('http://www.fakku.net/doujinshi/english', 303)
		);
		foreach ($data as $dd) {
			$starting_url = $dd[0];
			for ($page=$dd[1]; $page>=1; $page--) { // grab from last page
				$p = new Page($starting_url . ($page > 1 ? '/page/'.$page : ''));
				echo $p->url()."<br>\n";
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
		// echo $p->content();
		$content = $html->find('#content', 0);
		foreach ($content->find('div.content-row') as $row) {
			try {
				$item = array();
				/*
				$sample1 = $row->find('img.cover', 0);
				$sample2 = $row->find('img.sample', 0);
				$attributes = array('pagespeed_high_res_src', 'pagespeed_lazy_src', 'src');
				foreach ($attributes as $attr) {
					if ($sample1->$attr) {
						$item['sample'] = array(
							self::cdn_to_src($sample1->$attr), 
							self::cdn_to_src($sample2->$attr),
						);
						break;
					}
				}
				*/
				$title = $row->find('h2 a', 0);
				$item['url'] = rawurldecode($title->href);
				$item['title'] = html_entity_decode($title->plaintext, ENT_COMPAT, 'UTF-8');
				
				$series = $row->find('div.left', 0)->find('a', 0);
				if (!$series) { // malformed
					echo 'Cancelled '.$item['url'].' fail parsing $series'."<br>\n";
					continue;
					// throw new Exception('fail parsing $series');
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
					$item['tags'][] = basename($a->href);
				}
				$item['tags'] = '#'.implode('#', $item['tags']).'#';

				$infos[] = $item;
			} catch (Exception $e) {
				echo 'Cancelled '.$item['url'].' '.$e."<br>\n";
				exit;
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
				echo $p->url()."<br>\n";
				$infos = $this->extract_from_page($p);
				// if (strpos($p->content(), 'base64')) {print_r($p->content());print_r($infos);exit;}//debug
				// if (strpos($p->content(), 'ps.googleusercontent.com')) {print_r($p->content());print_r($infos);exit;}//debug
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
		$order_choices = array('date desc', 'date asc', 'title asc', 'title desc', 'id desc', 'id asc');

		$perpage = isset($_POST['perpage']) ? (int)$_POST['perpage'] : 20;
		$order = isset($_POST['order']) ? $_POST['order'] : 'date desc';
		$curpage = isset($_POST['curpage']) ? (int)$_POST['curpage'] : 1;
		if (isset($_POST['next'])) {
			$curpage++;
		} else if (isset($_POST['prev'])) {
			$curpage--;
		}
		if ($curpage < 1) $curpage = 1;
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
			
	<?php
		$q = Model::factory('Hmanga')
			->limit($perpage)
			->offset(($curpage-1) * $perpage);
		foreach ($order_choices as $choice) {
			if ($order == $choice) {
				list($column, $direction) = explode(' ', $choice);
				if ($direction == 'desc') {
					$q->order_by_desc($column);
				} else {
					$q->order_by_asc($column);
				}
			}
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
				<?php $samples = $hmanga->samples(); ?>
				<a href="?action=view&id=<?php echo $hmanga->id; ?>" title="<?php echo $hmanga->desc; ?>">
					<img src="<?php echo $samples[0];?>" alt="th" width="100" height="140">
					<img src="<?php echo $samples[1];?>" alt="th" width="100" height="140">
				</a>

				<dl class="dl-horizontal result">
					<dt>Title</dt><dd><a href="?action=view&id=<?php echo $hmanga->id; ?>"><?php echo $hmanga->title; ?></a></dd>
					<dt>Series</dt><dd><?php echo $hmanga->series; ?></dd>
					<dt>Artist</dt><dd><?php echo $hmanga->artist; ?></dd>
					<dt>Date</dt><dd><?php echo $hmanga->date; ?></dd>
					<dt>Page</dt><dd><?php echo $hmanga->count(); ?></dd>
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
		$thumbnails = $hmanga->thumbnails();
		$pages = $hmanga->pages();
		?>
		
		<dl class="dl-horizontal">
			<dt>Title</dt><dd><a href="<?php echo Fakku::$base.$hmanga->url; ?>"><?php echo $hmanga->title; ?></a></dd>
			<dt>Series</dt><dd><?php echo $hmanga->series; ?></dd>
			<dt>Artist</dt><dd><?php echo $hmanga->artist; ?></dd>
			<dt>Date</dt><dd><?php echo $hmanga->date; ?></dd>
			<dt>Description</dt><dd><?php echo $hmanga->desc; ?></dd>
			<dt>Page</dt><dd><?php echo $hmanga->count(); ?></dd>
			<dt>Tags</dt><dd><?php echo str_replace('#', ' ', $hmanga->tags); ?></dd>
			<dd><a href="<?php echo Fakku::$base.$hmanga->url; ?>">ORIGIN</a></dd>
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