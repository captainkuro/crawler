<?php
/*
Spider for Hbrowse.com

open all title exist, 
parse all the data, insert to table 'hbrowse.book'

/zzz/

*/

class Hmanga extends Model {

	public function compress($urls) {
		$prefix = '###';
		$result = array();
		foreach ($urls as $url) {
			if (strpos($url, $prefix) !== 0) {
				$prefix = dirname($url);
				$result[] = $prefix;
			}
			$result[] = basename($url);
		}
		return implode('#', $result);
	}

	public function decompress($compressed) {
		$raw = explode('#', $compressed);
		$result = array();
		$prefix = '';
		foreach ($raw as $segment) {
			if (strpos($segment, '/') !== FALSE) {
				$prefix = $segment;
			} else {
				$result[] = $prefix.'/'.$segment;
			}
		}
		return $result;
	}

	public function thumbnails() {
		$this->fill_detail();
		return $this->decompress($this->pic_all);
	}

	public function pages() {
		$thumbs = $this->thumbnails();
		$pages = array();
		foreach ($thumbs as $t) {
			$pages[] = str_replace('/zzz/', '/', $t);
		}
		return $pages;
	}

	// Fill pic_all
	public function fill_detail() {
		if ($this->pic_all) return;

		$thumbs = array();
		$p = new Page($this->link);
		$p->go_line('id="chapters"');
		do {
			$line = $p->curr_line();
			if ($line->contain('/thumbnails/') /*&& strpos($line, '.jpg') === false*/) {
				$link = $line->cut_between('href="', '"');
				$x = new Page($link);
				$x->go_line('id="main"');
				do {
					$line = $x->curr_line();
					if ($line->contain('/zzz/')) {
						$raw = $line->cut_between('src="', '"');
						//$img = str_replace('/zzz/', '/', $raw);
						$img = $raw;
						$thumbs[] = $img;
					}
				} while (!$x->next_line()->contain('</table>'));
			}
		} while (!$p->next_line()->contain('</table>'));
		// pic_all: pre#f1#f2#...
		$this->pic_all = $this->compress($thumbs);
		$this->save();
	}

	public function samples() {
		$patterns = array(
			'http://www.hbrowse.com/thumbnails/%s_1.jpg',
			'http://www.hbrowse.com/thumbnails/%s_2.jpg',
		);
		$thumbs = array();
		foreach ($patterns as $pattern) {
			$thumbs[] = sprintf($pattern, $this->id_web);
		}
		return $thumbs;
	}

	public function details() {
		$info = $this->as_array();
		unset($info['pic_1']);
		unset($info['pic_all']);
		foreach ($info as $k => $v) {
			if ($v[0] == '#') {
				$info[$k] = explode('#', trim($v, '#'));
			}
		}
		return $info;
	}
}

// Main program
class Hbrowse implements Spider {
	public static $update = 'http://www.hbrowse.com/browse/title/date/DESC';
	public static $base = 'http://www.hbrowse.com';
	
	public function get_title() {
		return 'Hbrowse scraper';
	}

	public function get_db_path() {
		return './sqlite/hbrowse.db';
	}

	public function create_database() {
		ORM::get_db()->query('CREATE TABLE `hmanga` (
		  `id` integer NOT NULL CONSTRAINT pid PRIMARY KEY AUTOINCREMENT,
		  `id_web` integer NOT NULL,
		  `title` varchar NOT NULL,
		  `artist` varchar NOT NULL,
		  `origin` varchar NOT NULL,
		  `length` integer NOT NULL,
		  `added` varchar NOT NULL,
		  `link` varchar NOT NULL,
		  `pic_1` varchar NOT NULL,
		  `pic_all` text NULL,
		  `genre` varchar NOT NULL,
		  `type` varchar NOT NULL,
		  `setting` varchar NOT NULL,
		  `fetish` varchar NOT NULL,
		  `role` varchar NOT NULL,
		  `relationship` varchar NOT NULL,
		  `male_body` varchar NOT NULL,
		  `female_body` varchar NOT NULL,
		  `grouping` varchar NOT NULL,
		  `scene` varchar NOT NULL,
		  `position` varchar NOT NULL
		)');

		ORM::get_db()->query('CREATE TABLE `reference` (
		  `id` varchar NOT NULL,
		  `val` varchar NOT NULL,
		  PRIMARY KEY (`id`)
		)');
	}
	
	public function action_fill_reference() {
		$p = new Page('http://www.hbrowse.com/advance');
		$raw = new Text($p->content());
		$names = $raw->extract_to_array('name="', '"');
		$names = array_unique($names);
		$ref = array();
		
		foreach ($names as $raw_name) {
			$name = new Text($raw_name);
			if ($name->contain('_')) {
				$key = $name->cut_before('_')->to_s();
				$val = $name->cut_after('_')->to_s();
				$ref[$key][] = $val;
			}
		}
		// insert
		foreach ($ref as $key => $val) {
			$reference = ORM::for_table('reference')->create();
			$reference->id = $key;
			$reference->val = '#' . implode('#', $val) . '#';
			$reference->save();
		}
	}

	public function get_ref() {
		$res = array();
		$refs = ORM::for_table('reference')->find_many();
		
		foreach ($refs as $row) {
			$res[$row->id] = explode('#', trim($row->val, '#'));
		}
		return $res;
	}


	public function action_all_pages() {
		$start = Hbrowse::$update;
		$stop = false;
		$links = array();
		
		while (!$stop) {
			//echo $start."\n";
			$p = new Page($start);
			$p->go_line('id="main"');
			do {
				$line = $p->curr_line();
				if ($line->contain('class="browseDescription"')) {
					$arr = $line->extract_to_array('href="', '"');
					$href = rtrim(end($arr), '/');
					$links[] = $href;
					echo "$href<br>\n";
				}
			} while (!$p->next_line()->contain('Next 50 Results'));
			// Cek ada next/tidak
			if ($line->contain('>Next 50 Results<')) {
				$arr = $line->extract_to_array('href="', '"');
				$start = end($arr);
			} else {
				$stop = true;
			}
		}
		// Now we have complete books' links
		$links = array_unique($links);
		$complete_links = '<?php $links='.var_export($links, true).';';
		file_put_contents('hbrowse.links', $complete_links);
	}

	// initial database insert
	public function action_init() {
		include 'hbrowse.links';
		foreach ($links as $link) {
			echo $link."<br>\n";flush(); // http://www.hbrowse.com/10001/c00001
			$p = new Page($link);
			$data = $this->extract_from_page($p);
			$this->add_hmanga($data);
		}
	}
	
	// extract information from given Page object
	public function extract_from_page($p) {
		$data = array(
			'link' => dirname($p->url()),
			'id_web' => basename(dirname($p->url())),
			'added' => date('Y-m-d H:i:s'),
		);
		// Ambil gambar di hal ini -> pic_1
		// echo $p->content();exit;
		$p->go_line('class="pageImage"');
		$data['pic_1'] = $p->next_line()->cut_between('src="', '"')->to_s();
		// Ambil title, artist, length, origin, etc etc...
		$p->go_line('id="pageMain"');
		do {
			$line = $p->curr_line();
			if ($line->contain('</strong>')) {
				$key = $line->cut_between('<strong>', '</strong>');
				$arkey = $key->strtolower()->replace(' ', '_')->to_s();
				switch ($key->to_s()) {
					case 'Title':
						$data['title'] = $line->cut_between('listLong">', '</')->to_s();
						break;
					case 'Artist':
						$data['artist'] = $line->cut_between('href="', '"')->basename()->to_s();
						break;
					case 'Length':
						$data['length'] = $line->cut_between('listLong">', 'page')->trim()->to_s();
						break;
					case 'Origin':
						$data['origin'] = $line->cut_between('href="', '"')->basename()->to_s();
						break;
					case 'Genre':
					case 'Type':
					case 'Setting':
					case 'Fetish':
					case 'Role':
					case 'Relationship':
					case 'Male Body':
					case 'Female Body':
					case 'Grouping':
					case 'Scene':
					case 'Position':
						$raw = $line->extract_to_array('href="', '"');
						$raw2 = array_map('basename', $raw);
						$data[$arkey] = '#' . implode('#',$raw2) . '#';
						break;
				}
			}
		} while (!$p->next_line()->contain('id="chapters"'));
		return $data;
	}
	
	public function add_hmanga($data) {
		$hmanga = Model::factory('Hmanga')->create();
		$hmanga->hydrate($data);
		$hmanga->save();
	}

	public function is_already_exist($href) {
		$link = dirname($href);
		$hmanga = Model::factory('Hmanga')->where('link', $link)->find_one();
		return (bool)$hmanga;
	}
	
	public function action_update() {
		$start = Hbrowse::$update;
		$stop = false;
		$links = array();
		
		while (!$stop) {
			$p = new Page($start);
			$p->go_line('id="main"');
			do {
				$line = $p->curr_line();
				if ($line->contain('class="browseDescription"')) {
					$arr = $line->extract_to_array('href="', '"');
					$href = rtrim(end($arr), '/');
					if ($this->is_already_exist($href)) {
						$stop = true;
						break;
					}
					$links[] = $href;
				}
			} while (!$p->next_line()->contain('Next 50 Results'));

			$line = $p->curr_line();
			// Cek ada next/tidak
			if ($line->contain('>Next 50 Results<')) {
				$arr = $line->extract_to_array('href="', '"');
				$start = end($arr);
			} else {
				$stop = true;
			}
		}
		$links = array_reverse(array_unique($links));
		// $links berisi link2 yg siap dimasukkan
		foreach ($links as $link) {
			echo $link."<br>\n";flush(); // http://www.hbrowse.com/10001/c00001
			// Cek dah ada di DB belum
			$p = new Page($link);
			$data = $this->extract_from_page($p);
			// Masukkan ke database
			$this->add_hmanga($data);
		}
	}
	
	public function action_view() {
		$id = $_REQUEST['id'];
		$hmanga = Model::factory('Hmanga')->find_one($id);
		$thumbnails = $hmanga->thumbnails();
		$pages = $hmanga->pages();

		HH::print_downloads($hmanga->title, $thumbnails, $pages, 'width:100px;height:150px');
		?>
		
		<pre><?php print_r($hmanga->details()) ?></pre>

		<?php
	}
	
	public function action_search() {
		$search = array(
			'any' => explode(' ', @$_REQUEST['any']),
			'title' => explode(' ', @$_REQUEST['title']),
			'artist' => explode(' ', @$_REQUEST['artist']),
			'origin' => explode(' ', @$_REQUEST['origin']),
			'tags' => explode(' ', @$_REQUEST['tags']),
			'include' => @$_REQUEST['include'],
			'exclude' => @$_REQUEST['exclude'],
		);

		$order_choices = array('added desc', 'added asc', 'title asc', 'title desc', 'id desc', 'id asc', 'length asc', 'length desc');
		$order = isset($_REQUEST['order']) ? $_REQUEST['order'] : 'added desc';

		$perpage = isset($_REQUEST['perpage']) ? $_REQUEST['perpage'] : 20;
		$curpage = isset($_REQUEST['curpage']) ? $_REQUEST['curpage'] : 1;

		if (isset($_REQUEST['next'])) {
			$curpage++;
		} else if (isset($_REQUEST['prev'])) {
			$curpage--;
		}
		if ($curpage < 1) $curpage = 1;
		
		?>
		<form method="post" class="form-horizontal">
			<div class="form-group row">
				<?php HH::print_form_field('Any', 'any', @$_REQUEST['any']); ?>
			
				<?php HH::print_form_field('Title', 'title', @$_REQUEST['title']); ?>
			</div>
			<div class="form-group row">
				<?php HH::print_form_field('Artist', 'artist', @$_REQUEST['artist']); ?>

				<?php HH::print_form_field('Origin', 'origin', @$_REQUEST['origin']); ?>
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
		foreach ($search['origin'] as $term) { if ($term) {
			$q->where_like('origin', "%{$term}%");
		}}
		foreach ($search['artist'] as $term) { if ($term) {
			$q->where_like('artist', "%{$term}%");
		}}
		foreach ($search['any'] as $term) { if ($term) {
			$q->where_raw('(title LIKE ? OR origin LIKE ? OR artist LIKE ?)', 
			array("%{$term}%", "%{$term}%", "%{$term}%"));
		}}
		if (@$_REQUEST['include']) { 
			foreach ($_REQUEST['include'] as $key => $vals) {
				foreach ($vals as $val) {
					$q->where_like($key, "%#{$val}#%");
				}
			}
		}
		if (@$_REQUEST['exclude']) {
			foreach ($_REQUEST['exclude'] as $key => $vals) {
				foreach ($vals as $val) {
					$q->where_not_like($key, "%#{$val}#%");
				}
			}
		}
		$result = $q->find_many();
		
		$ref = $this->get_ref();
		?>
			<?php foreach ($result as $i => $hmanga) : ?>
				<?php if ($i % 2 == 0) echo '<div class="row">'; ?>
				<div class="col-md-6 result">
					<?php $samples = $hmanga->samples(); ?>
					<a href="<?php echo HH::url($this, "action=view&id={$hmanga->id}"); ?>">
						<img src="<?php echo $samples[0];?>" alt="th">
						<img src="<?php echo $samples[1];?>" alt="th">
					</a>

					<dl class="dl-horizontal result">
						<dt>Title</dt><dd><a href="<?php echo HH::url($this, "action=view&id={$hmanga->id}"); ?>"><?php echo $hmanga->title; ?></a></dd>
						<dt>Origin</dt><dd><?php echo $hmanga->origin; ?></dd>
						<dt>Artist</dt><dd><?php echo $hmanga->artist; ?></dd>
						<dt>Date</dt><dd><?php echo $hmanga->added; ?></dd>
						<dt>Page</dt><dd><?php echo $hmanga->length; ?></dd>
						<dt>Tags</dt><dd><?php echo str_replace('#', ' ', $hmanga->type); ?></dd>
						<dt><a href="<?php echo HH::url($this, "action=view&id={$hmanga->id}"); ?>">VIEW</a></dt>
						<dd><a href="<?php echo $hmanga->link; ?>">ORIGIN</a></dd>
					</dl>
				</div>
				<?php if ($i % 2 == 1) echo '</div>'; ?>
			<?php endforeach; ?>
			
			<div class="form-group row" style="display:block;clear:both">
				<div class="controls">
					<button type="submit" class="btn btn-primary" name="search">Search</button>
					<button type="submit" class="btn btn-info" name="prev">&lt;&lt; Prev</button>
					<button type="submit" class="btn btn-info" name="next">Next &gt;&gt;</button>
				</div>
			</div>

			<table border="2">
				<tr valign="top">
				<?php $i = 0 ?>
				<?php foreach ($ref as $key => $vals) : $i++ ?>
					<td>
					<table>
						<tr>
							<th><?php echo $key ?></th>
							<th>V</th>
							<th>X</th>
						</tr>
						<?php foreach ($vals as $val) : ?>
							<tr>
								<td><?php echo $val ?></td>
								<td><input type="checkbox" name="include[<?php echo $key ?>][]" value="<?php echo $val ?>" <?php if (in_array($val, (array)@$_REQUEST['include'][$key])) echo 'checked'; ?>/></td>
								<td><input type="checkbox" name="exclude[<?php echo $key ?>][]" value="<?php echo $val ?>" <?php if (in_array($val, (array)@$_REQUEST['exclude'][$key])) echo 'checked'; ?>/></td>
							</tr>
						<?php endforeach ?>
					</table>
					</td>
					<?php if ($i % 6 == 0) : ?>
				</tr>
				<tr valign="top">
					<?php endif ?>
				<?php endforeach ?>
				</tr>
			</table>
			<input type="submit" value="search" />
		</form>
		<?php
	}
	
	public function action_test() {
		$hmanga = Model::factory('Hmanga')->find_one(1);
		//debug
		print_r($hmanga->details());
		print_r($hmanga->thumbnails());
		print_r($hmanga->pages());
		exit;
	}
}
