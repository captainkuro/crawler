<?php
/*
Crawl (not really) entire hbrowse.com site
open all title exist, 
parse all the data, insert to table 'hbrowse.book'

UPDATE 16 Oct 2011
migrasi database sqlite

CREATE TABLE IF NOT EXISTS `book` (
  `id` integer NOT NULL CONSTRAINT pid PRIMARY KEY AUTOINCREMENT,
  `id_web` integer NOT NULL,
  `title` text NOT NULL,
  `artist` text NOT NULL,
  `origin` text NOT NULL,
  `length` integer NOT NULL,
  `added` text NOT NULL,
  `link` text NOT NULL,
  `pic_1` text NOT NULL,
  `pic_all` text NOT NULL,
  `genre` text NOT NULL,
  `type` text NOT NULL,
  `setting` text NOT NULL,
  `fetish` text NOT NULL,
  `role` text NOT NULL,
  `relationship` text NOT NULL,
  `male_body` text NOT NULL,
  `female_body` text NOT NULL,
  `grouping` text NOT NULL,
  `scene` text NOT NULL,
  `position` text NOT NULL
  
);

CREATE TABLE IF NOT EXISTS `reference` (
  `id` text NOT NULL,
  `val` text NOT NULL,
  PRIMARY KEY (`id`)
);
*/
require_once 'crawler.php';

class G {
	public static $db = null;
}
$dbpath = realpath('./sqlite/hbrowse.db');
G::$db = new DB(array(
	'dsn' => 'sqlite:' . $dbpath,
	'username' => null,
	'password' => null,
));

// $db = mysql_connect('localhost', 'root', 'rootpassword');
// mysql_select_db('hbrowse', $db);

function col_explode($val) {
	$val = preg_replace('/(^#)|(#$)/', '', $val);
	return explode('#', $val);
}

// First col only
function arr_to_flat($arr) {
	$flat = array();
	foreach ($arr as $row) $flat[] = current($row);
	return $flat;
}

function thumb_url($url) {
	return str_replace('c00001/', 'c00001/zzz/', $url);
}

function big_url($url) {
	return str_replace('/zzz/', '/', $url);
}

function get_ref() {
	global $db;
	
	$res = array();
	$query = "SELECT * FROM `reference` WHERE 1";
	$res_query = G::$db->execute($query);
	while ($row = $res_query->fetch(PDO::FETCH_ASSOC)) {
		$row['val'] = preg_replace('/(^#)|(#$)/', '', $row['val']);
		$res[$row['id']] = explode('#', $row['val']);
	}
	return $res;
}

// Return 1 row where $by = $val
function get_book($by, $val) {
	$query = sprintf("SELECT * FROM book WHERE $by=%s", G::$db->escape_value($val));
	$res = G::$db->execute($query);
	if ($raw = $res->fetch(PDO::FETCH_ASSOC)) {
		foreach ($raw as $k => $v) {
			if ($v[0] == '#') {
				$raw[$k] = col_explode($v);
			}
		}
		return $raw;
	} else {
		return null;
	}
}

function all_book($order = 'title ASC') {
	$query = "SELECT * FROM book ORDER BY $order";
	$res = G::$db->execute($query);
	if ($res) {
		$result = array();
		while ($row = $res->fetch(PDO::FETCH_ASSOC)) $result[] = $row;
		return $result;
	} else {
		return array();
	}
}

function page_to_array($link) {
	$data = array(
		'link' => dirname($link),
		'id_web' => basename(dirname($link)),
		'added' => date('Y-m-d H:i:s'),
	);
	$c = new Crawler($link);
	// Ambil gambar di hal ini -> pic_1
	$c->go_to('class="pageImage"');
	$c->readline();
	$data['pic_1'] = Crawler::extract($c->curline, 'src="', '"');
	// Ambil title, artist, length, origin, etc etc...
	$c->go_to('id="pageMain"');
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, '</strong>')) {
			$key = Crawler::extract($line, '<strong>', '</strong>');
			$arkey = str_replace(' ', '_', strtolower($key));
			switch ($key) {
				case 'Title':
					$data['title'] = trim(Crawler::cutafter($line, ':'));
					break;
				case 'Artist':
					$l = Crawler::extract($line, 'href="', '"');
					$data['artist'] = basename($l);
					break;
				case 'Length':
					$data['length'] = trim(Crawler::extract($line, ':', 'page'));
					break;
				case 'Origin':
					$l = Crawler::extract($line, 'href="', '"');
					$data['origin'] = basename($l);
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
					$raw = Crawler::extract_to_array($line, 'href="', '"');
					$raw2 = array_map('basename', $raw);
					$data[$arkey] = '#' . implode('#',$raw2) . '#';
					break;
			}
		} else if (Crawler::is_there($line, 'class="listEntry"')) {
			break;
		}
	}
	$c->close();
	return $data;
}

function insert_array_to_database($arr) {
	G::$db->from('book')->do_insert($arr);
}

function update_book($id, $book) {
	G::$db->from('book')->set($book)->where('id', $id)->do_update();
}

function page_all_thumbnail($link) {
	$thumbs = array();
	$c = new Crawler($link);
	$c->go_to('class="listEntry"');
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, '/thumbnails/')) {
			$link = Crawler::extract($line, 'class="thumbLink" href="', '"');
			//echo "$link<br/>\n";
			$x = new Crawler($link);
			$x->go_to('id="main"');
			while ($line = $x->readline()) {
				if (Crawler::is_there($line, '/zzz/')) {
					$raw = Crawler::extract($line, 'src="', '"');
					//$img = str_replace('/zzz/', '/', $raw);
					$img = $raw;
					$thumbs[] = $img;
				} else if (Crawler::is_there($line, '</table>')) {
					break;
				}
			}
			$x->close();
		} else if (Crawler::is_there($line, '</table>')) {
			break;
		}
	}
	$c->close();
	return $thumbs;
}

//print_r(get_ref());
$stage = 'search';
if ($_REQUEST && @$_REQUEST['stage']) $stage = $_REQUEST['stage'];

switch ($stage) {
	case 'build': // Building reference
		$raw = file_get_contents('advance.htm');
		$names = Crawler::extract_to_array($raw, 'name="', '"');
		$names = array_unique($names);
		$ref = array();
		foreach ($names as $name) {
			if (Crawler::is_there($name, '_')) {
				$key = Crawler::cutuntil($name, '_');
				$val = Crawler::cutafter($name, '_');
				$ref[$key][] = $val;
			}
		}
		//var_export($ref);
		foreach ($ref as $key => $val) {
			$data = array(
				'id' => $key,
				'val' => '#' . implode('#', $val) . '#',
			);
			G::$db->from('reference')->do_insert($data);
		}
		break;
	case 'init': // Database kosong, insert semua data
		$start = 'http://www.hbrowse.com/browse/title/date/ASC';
		$stop = false;
		$links = array();
		
		while (!$stop) {
			//echo $start."\n";
			$c = new Crawler($start);
			$c->go_to('id="main"');
			while ($line = $c->readline()) {
				if (Crawler::is_there($line, 'class="readLink"')) {
					$href = Crawler::extract($line, 'href="', '"');
					$links[] = $href;
				} else if (Crawler::is_there($line, 'Next 50 Results')) {
					break;
				}
			}
			// Cek ada next/tidak
			if (Crawler::is_there($line, '>Next 50 Results<')) {
				$arr = Crawler::extract_to_array($line, 'href="', '"');
				$start = end($arr);
			} else {
				$stop = true;
			}
			$c->close();
		}
		// Now we have complete books' links
		var_export($links);
		break;
	case 'init2': // Terusan init
		include 'hbrowse.init'; // assign $links
		foreach ($links as $link) {
			echo $link."\n";flush(); // http://www.hbrowse.com/10001/c00001
			// Cek dah ada di DB belum
			$query = sprintf("SELECT * FROM book WHERE link=%s", G::$db->escape_value($link));
			$res = G::$db->execute($query);
			if (!$res->fetch()) {
				$data = page_to_array($link);
				//print_r($data);
				// Masukkan ke database
				insert_array_to_database($data);
			}
			//exit; // Testing, 1 aja dulu
		}
		break;
	case 'init2allpic': // After all initial data inserted, ambil nilai untuk pic_all (thumbnails)
		$query = "SELECT id,link FROM book";
		$result = G::$db->execute($query);
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			echo $row['id'];
			$thumbs = page_all_thumbnail($row['link']);
			echo $row['link'];
			$query = sprintf("UPDATE book SET pic_all=%s WHERE id={$row['id']}",
				G::$db->escape_value('#' . implode('#', $thumbs) . '#')
			);
			G::$db->execute($query);
			echo "\n";
			//exit; // Testing, 1 aja dulu
		}
		break;
	case 'update': // Database sudah ada, parse cukup title/link/id yg belum ada di database
		$start = 'http://www.hbrowse.com/browse/title/date/DESC';
		$stop = false;
		$links = array();
		
		while (!$stop) {
			$c = new Crawler($start);
			$c->go_to('id="main"');
			while ($line = $c->readline()) {
				if (Crawler::is_there($line, 'class="browseDescription"')) {
					$arr = Crawler::extract_to_array($line, 'href="', '"');
					$href = end($arr);
					if (get_book('link', dirname($href))) {
						$stop = true;
						break;
					}
					$links[] = $href;
				} else if (Crawler::is_there($line, 'Next 50 Results')) {
					break;
				}
			}
			// Cek ada next/tidak
			if (Crawler::is_there($line, '>Next 50 Results<')) {
				$arr = Crawler::extract_to_array($line, 'href="', '"');
				$start = end($arr);
			} else {
				$stop = true;
			}
			$c->close();
		}
		$links = array_reverse($links);
		// $links berisi link2 yg siap dimasukkan
		foreach ($links as $link) {
			echo $link."\n";flush(); // http://www.hbrowse.com/10001/c00001
			// Cek dah ada di DB belum
			$data = page_to_array($link);
			// print_r($data);exit;
			$data['pic_all'] = '#' . implode('#', page_all_thumbnail($link)) . '#';
			// Masukkan ke database
			insert_array_to_database($data);
			//exit; // Testing, 1 aja dulu
		}
		break;
	case 'search': // Database sudah ada, search isinya
		if ($_POST) {
			// Build condition query
			$where = '1';
			if ($_POST['title']) {
				$where .= sprintf(" AND `title` LIKE %s", G::$db->escape_value('%'.$_POST['title'].'%'));
			}
			if ($_POST['artist']) {
				$where .= sprintf(" AND `artist` LIKE %s", G::$db->escape_value('%'.$_POST['artist'].'%'));
			}
			if ($_POST['origin']) {
				$where .= sprintf(" AND `origin` LIKE %s", G::$db->escape_value('%'.$_POST['origin'].'%'));
			}
			if ($_POST['exact_artist']) {
				$where .= sprintf(" AND `artist` = %s", G::$db->escape_value($_POST['exact_artist']));
			}
			if ($_POST['exact_origin']) {
				$where .= sprintf(" AND `origin` = %s", G::$db->escape_value($_POST['exact_origin']));
			}
			if (@$_POST['include']) {
				foreach ($_POST['include'] as $key => $vals) {
					foreach ($vals as $val) {
						$where .= sprintf(" AND `$key` LIKE %s", G::$db->escape_value('%#'.$val.'#%'));
					}
				}
			}
			if (@$_POST['exclude']) {
				foreach ($_POST['exclude'] as $key => $vals) {
					foreach ($vals as $val) {
						$where .= sprintf(" AND `$key` NOT LIKE %s", G::$db->escape_value('%#'.$val.'#%'));
					}
				}
			}
			$order = $_REQUEST['order'] ? $_REQUEST['order'] : false;
			if ($order) $where .= ' ORDER BY ' . $order;
			if ($_POST['limit']) $where .= ' LIMIT ' . $_POST['limit'];
			$query = "SELECT * FROM book WHERE " . $where;
			echo $query;
			$result = G::$db->execute($query)->fetchAll();
			$count = count($result);
			$percolumn = (int)(($count-1) / 3) + 1;
			$i = 0;
			?>
			Result: <?php echo $count ?>
			<table>
				<tr valign="top"><td>
				<?php foreach ($result as $book) : $i++ ?>
					<img src="<?php echo thumb_url($book['pic_1']) ?>" align="top"/>
					<a href="?stage=display&amp;id=<?php echo $book['id'] ?>"><?php echo $book['title'].' | '.$book['artist'] ?></a><br/>
					<?php if ($i % $percolumn == 0) : ?>
				</td><td>
					<?php endif ?>
				<?php endforeach ?>
				</td></tr>
			</table>
			<?php
		}

		$ref = get_ref();
		$query = "SELECT DISTINCT artist FROM book ORDER BY artist ASC";
		$artists = G::$db->execute($query)->fetchAll();
		$artists = arr_to_flat($artists);
		$query = "SELECT DISTINCT origin FROM book ORDER BY origin ASC";
		$origins = G::$db->execute($query)->fetchAll();
		$origins = arr_to_flat($origins);
		?>
		<form method="post">
			Title: <input name="title" type="text" value="<?php echo @$_REQUEST['title']; ?>" /><br/>
			Artist: <input name="artist" type="text" value="<?php echo @$_REQUEST['artist']; ?>" /><br/>
			Origin: <input name="origin" type="text" value="<?php echo @$_REQUEST['origin']; ?>" /><br/>
			Limit: <input name="limit" type="text" value="<?php echo @$_REQUEST['limit']; ?>" /><br />
			Order: <input name="order" type="text" value="<?php echo @$_REQUEST['order'] ?>" /><br />
			Exact Artist: <select name="exact_artist"><option value="">-</option><option><?php echo implode('</option><option>', $artists) ?></option></select>
			Exact Origin: <select name="exact_origin"><option value="">-</option><option><?php echo implode('</option><option>', $origins) ?></option></select>
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
		break;
	case 'display': // Display 1 title
		$id = $_REQUEST['id'];
		if ($id) {
			$book = get_book('id', $id);
			$book['pic_1'] = "<img src='".thumb_url($book['pic_1'])."' />";
			if (true || $_REQUEST['thumb']) {
				$i = 0;
				?>
				<table>
					<tr>
					<?php foreach ($book['pic_all'] as $pic) : $i++ ?>
						<td><img src="<?php echo $pic ?>" /></td>
						<?php if ($i % 10 == 0) : ?>
					</tr><tr>
						<?php endif ?>
					<?php endforeach ?>
					</tr>
				</table>
				<?php
			}
			unset($book['pic_all']);
			?>
			<a href="?stage=crawl&amp;id=<?php echo $id ?>">Crawl</a>
			<a href="?stage=display&amp;thumb=1&amp;id=<?php echo $id ?>">All Thumbs</a>
			<pre><?php print_r($book) ?></pre>
			<?php
		} else {
			$order = $_REQUEST['order'] ? $_REQUEST['order'] : 'title ASC';
			$books = all_book($order);
			$count = count($books);
			$percolumn = (int)($count / 3) + 1;
			$i = 0;
			?>
			<table>
				<tr valign="top"><td>
				<?php foreach ($books as $book) : $i++ ?>
					<a href="?stage=display&amp;id=<?php echo $book['id'] ?>"><?php echo $book['title'].' | '.$book['artist'] ?></a><br/>
					<?php if ($i % $percolumn == 0) : ?>
				</td><td>
					<?php endif ?>
				<?php endforeach ?>
				</td></tr>
			</table>
			<?php
		}
		break;
	case 'crawl': // Crawl 1 title
		$id = $_REQUEST['id'];
		if ($id) {
			$book = get_book('id', $id);
			foreach ($book['pic_all'] as $pic) {
				$pic = big_url($pic);
				?><a href="<?php echo $pic ?>"><?php echo $book['title'] ?></a><br/><?php
			}
		} else {
			$books = all_book();
			$i = 0;
			?>
			<table>
				<tr valign="top"><td>
				<?php foreach ($books as $book) : $i++ ?>
					<a href="?stage=crawl&amp;id=<?php echo $book['id'] ?>"><?php echo $book['title'].' | '.$book['artist'] ?></a><br/>
					<?php if ($i % 500 == 0) : ?>
				</td><td>
					<?php endif ?>
				<?php endforeach ?>
				</td></tr>
			</table>
			<?php
		}
		break;
	case 'recrawl': // Sudah pernah dicrawl, minta di-refresh
		$id = $_REQUEST['id'];
		if ($id) {
			$book = get_book('id', $id);
			$link = $book['link'] . '/c00001';
			$data = page_to_array($link);
			$data['pic_all'] = '#' . implode('#', page_all_thumbnail($link)) . '#';
			unset($data['added']);
			// Masukkan ke database
			update_book($id, $data);
			echo '<pre>'.print_r($data, true).'</pre>';
		} else { ?>
			<form action="" method="post">
				ID: <input type="text" name="id" />
				<input type="submit" />
			</form>
		<?php }
		break;
	case 'migrate':
		// pindahin dari mysql ke sqlite
		/*
		$sql = "SELECT * FROM book";
		$result = mysql_query($sql);
		while ($row = mysql_fetch_assoc($result)) {
			G::$db->from('book')->do_insert($row);
		}
		*/
		break;
	default: // Display HTML pilihan $stage
		?>
		<form>
			<input type="radio" name="stage" value="search" />Search<br/>
			<input type="radio" name="stage" value="display" />Display<br/>
			<input type="radio" name="stage" value="crawl" />Crawl<br/>
			<input type="submit" value="Select Stage" />
		</form>
		<?php
}
// mysql_close($db);