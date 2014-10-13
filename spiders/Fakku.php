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

class Log {
	public static function add($text) {
		file_put_contents('fakku-'.date('Y-m-d').'.log', $text, FILE_APPEND);
	}
}

class Hmanga extends Model {
	public function count() {
		return substr_count($this->thumbs, '#')+1;
	}

	public function thumb_src($filename) {
		$pattern = new Text($this->pattern);
		// hack
		// $hack_replace = 'http://c.fakku.net/';
		$hack_replace = 't.fakku.net/';
		$thumb_pre = $pattern
			->replace('/images/', '/thumbs/')
			// hack
			->replace('t.fakku.net/thumbs/', $hack_replace.'images/')
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
		} else if ($content->contain('This content has been disabled.')) {
			return;
			$js_thumbs = array();
		} else if ($content->contain('This content is not available in your country')) {
			return;
			$js_thumbs = array();
		} else if ($content->contain('Content does not exist')) {
			return;
			$js_thumbs = array();
		} else {
			echo $p->url();
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
		$imgpath = str_replace("https://", 'http://', $imgpath);
		$this->pattern = $imgpath;

		// update date
		// $date_page = new Page(Fakku::$base . $this->url);
		// $h = new simple_html_dom();
		// $h->load($date_page->content());
		// $date = $h->find('div.small', 0)
		// 	->find('div.right', 0)
		// 	->find('b', 0);
		// $this->date = date('Y-m-d', strtotime($date->plaintext));
		
		$this->save();
	}

	public function samples() {
		$thumbs = $this->thumbnails();
		return array($thumbs[0], $thumbs[1]);
	}
}

// Main program
class Fakku implements Spider{
	public static $cdn = 'http://cdn.fakku.net/8041E1/t/';
	public static $base = 'https://www.fakku.net';
	
	public function get_title() {
		return 'Fakku scraper';
	}

	public function get_db_path() {
		return './sqlite/fakku.db';
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
	
	// initial database insert/update
	public function action_init() {
		// hardcoded page number for quick'n'dirty
		$data = array(
			array('http://www.fakku.net/manga/english', 0),
			array('http://www.fakku.net/doujinshi/english', 14)
		);
		foreach ($data as $dd) {
			$starting_url = $dd[0];
			for ($page=$dd[1]; $page>=1; $page--) { // grab from last page
				$p = new Page($starting_url . ($page > 1 ? '/page/'.$page : ''));
				echo $p->url()."<br>\n";
				$infos = $this->extract_from_page($p);
				$infos = array_reverse($infos);
				foreach ($infos as $info) {
					echo $info['url']."<br>\n";
					$this->edit_hmanga($info); // change to add/edit
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
				
				$series = $row->find('div.right', 0)->find('a', 0);
				if (!$series) { // malformed
					throw new Exception($item['url'] . ' fail parsing $series');
					echo 'Cancelled '.$item['url'].' fail parsing $series'."<br>\n";
					continue;
				}
				$item['series'] = html_entity_decode($series->plaintext, ENT_COMPAT, 'UTF-8');
				
				// $artist = $row->find('div.left', 1)->find('a', 0);
				$artists = array();
				$artist_els = $row->find('div.right', 1)->find('a');
				foreach ($artist_els as $el) {
					$artists[] = html_entity_decode($el->plaintext, ENT_COMPAT, 'UTF-8');
				}
				$item['artist'] = implode(', ', $artists);
				
				$date = $row->find('.content-time', 0);
				$item['date'] = date('Y-m-d', strtotime($date->plaintext));
				
				// find description
				$right = null;
				foreach ($row->find('div.left') as $left) {
					if ($left->plaintext === 'Description') {
						$right = $left->next_sibling();
					}
				}
				if (!$right) {
					throw new Exception($item['url'] . 'fail parsing $desription');
					echo 'Cancelled '.$item['url'].' fail parsing $description'."<br>\n";
					continue;
				}
				$item['desc'] = trim($right->plaintext);
				
				$tags = $row->find('div.tags', 0);
				$item['tags'] = array();
				if ($tags) foreach ($tags->find('a') as $a) {
					if ($a->plaintext != '...') {
						$item['tags'][] = basename($a->href);
					}
				}
				$THRESHOLD = 7;
				if (count($item['tags']) >= $THRESHOLD) {
					// there may be another tags
					$item['tags'] = $this->grab_all_tags($item['url']);
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

	private function grab_all_tags($url) {
		$real_url = self::$base . $url;
		$p = new Page($real_url);
		$html = new simple_html_dom();
		$html->load($p->content());

		$tags = $html->find('div.tags', 0);
		$result = array();
		foreach ($tags->find('a') as $a) {
			if ($a->plaintext != '+') {
				$result[] = basename($a->href);
			}
		}
		return $result;
	}
	
	public function add_hmanga($data) {
		$hmanga = Model::factory('Hmanga')->create();
		$hmanga->hydrate($data);
		$hmanga->save();
	}

	public function edit_hmanga($data) {
		$hmanga = ORM::for_table('hmanga')->where('url', $data['url'])->find_one();
		if (!$hmanga) {
			echo "Not found?";
			Log::add($data['url']."\nNew manga!!!\n".json_encode($data)."\n");
			$this->add_hmanga($data);
			return;
		}
		$updated = FALSE;
		foreach ($data as $key => $value) {
			if ($hmanga->$key != $value) {
				Log::add($data['url']." $key\nOld:{$hmanga->$key};\nNew:{$value};\n");
				$updated = TRUE;
				$hmanga->$key = $value;
			}
		}
		if ($updated) $hmanga->save();
	}
	
	public function action_update() {
		foreach (array(
			'https://www.fakku.net/manga/english', 
			'https://www.fakku.net/doujinshi/english'
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
			// echo '<pre>';print_r($to_add);continue;
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
		<form class="form-horizontal" method="post" role="form">
			<div class="form-group row">
				<?php HH::print_form_field('Any', 'any', @$_POST['any']); ?>
			
				<?php HH::print_form_field('Artist', 'artist', @$_POST['artist']); ?>
			</div>
			<div class="form-group row">
				<?php HH::print_form_field('Title', 'title', @$_POST['title']); ?>
				
				<?php HH::print_form_field('Desc', 'desc', @$_POST['desc']); ?>
			</div>
			<div class="form-group row">
				<?php HH::print_form_field('Series', 'series', @$_POST['series']); ?>
			
				<?php HH::print_form_field('Tags', 'tags', @$_POST['tags']); ?>
			</div>
			<div class="form-group row">
				<?php HH::print_form_field('Items', 'perpage', $perpage, 3); ?>
				
				<?php HH::print_form_field('Page', 'curpage', $curpage, 3); ?>
				
				<?php HH::print_radio_field('Order', 'order', $order_choices, $order); ?>
			</div>
			<div class="form-group row">
				<div class="controls">
					<button type="submit" class="btn btn-primary" name="search">Search</button>
					<button type="submit" class="btn btn-info" name="prev">&lt;&lt; Prev</button>
					<button type="submit" class="btn btn-info" name="next">Next &gt;&gt;</button>
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
		<?php foreach ($result as $i => $hmanga) : ?>
			<?php if ($i % 2 == 0) echo '<div class="row">'; ?>
			<div class="col-md-6 result">
				<?php $samples = $hmanga->samples(); ?>
				<a href="<?php echo HH::url($this, "action=view&id={$hmanga->id}"); ?>" title="<?php echo $hmanga->desc; ?>">
					<img src="<?php echo $samples[0];?>" alt="th" width="100" height="140">
					<img src="<?php echo $samples[1];?>" alt="th" width="100" height="140">
				</a>

				<dl class="dl-horizontal result">
					<dt>Title</dt><dd><a href="<?php echo HH::url($this, "action=view&id={$hmanga->id}"); ?>"><?php echo $hmanga->title; ?></a></dd>
					<dt>Series</dt><dd><?php echo $hmanga->series; ?></dd>
					<dt>Artist</dt><dd><?php echo $hmanga->artist; ?></dd>
					<dt>Date</dt><dd><?php echo $hmanga->date; ?></dd>
					<dt>Page</dt><dd><?php echo $hmanga->count(); ?></dd>
					<dt>Tags</dt><dd><?php echo str_replace('#', ' ', $hmanga->tags); ?></dd>
					<dt><a href="<?php echo HH::url($this, "action=view&id={$hmanga->id}"); ?>">VIEW</a></dt>
					<dd>
						<a href="<?php echo Fakku::$base.$hmanga->url; ?>">ORIGIN</a>
						<a href="<?php echo HH::url("action=dump&id={$hmanga->id}"); ?>">DUMP</a>
					</dd>
				</dl>
			</div>
			<?php if ($i % 2 == 1) echo '</div>'; ?>
		<?php endforeach; ?>
		
		<div class="form-group" style="display:block;clear:both">
			<div class="controls">
				<button type="submit" class="btn btn-info" name="prev">&lt;&lt; Prev</button>
				<button type="submit" class="btn btn-info" name="next">Next &gt;&gt;</button>
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
		
		<?php
		HH::print_downloads($hmanga->title, $thumbnails, $pages);
	}

	public function action_dump() {
		$id = $_GET['id'];
		$hmanga = Model::factory('Hmanga')->find_one($id);
		echo '<pre>';
		print_r($hmanga->as_array());
	}

	public function is_already_exist($info) {
		$n = ORM::for_table('hmanga')->where('url', $info['url'])->count();
		return $n > 0;
	}
	
	public function action_test() {
		$p = new Page('http://www.fakku.net/doujinshi/english');
		$infos = $this->extract_from_page($p);
		echo '<pre>';
		print_r($infos);
		Log::add(json_encode($infos));
	}
}
