<?php
/*
Redflava.com Gallery

crawl all images and save those inside an SQLite database

table 'category'
| id | link | name | desc | parent |

*/
require_once 'class/page.php';
require_once 'class/text.php';
require_once 'class/db.php';

// global variables
class G {
	public static $base = 'http://www.redflava.com/gallery/';
	public static $db = null;
	public static $cat = null;
}
$dbpath = realpath('./sqlite/redflava.db3');
G::$db = new DB(array(
	'dsn' => 'sqlite:' . $dbpath,
	'username' => null,
	'password' => null,
));

// collect category structure
function stage_fill_category() {
	// http://www.redflava.com/gallery/
	if (!isset(G::$cat)) {
		$p = new Page('http://www.redflava.com/gallery/');
		$p->go_line('"maintable');
		$big = array();
		$small = array();
		do {
			if ($p->curr_line()->contain('class="catlink"')) {
				$matches = $p->curr_line()->regex_match('/class="catlink"><a href="([^"]+)">([^<]+)<\/a><\/span>([^<]*)/');
				if ($p->curr_line()->contain('<img src')) { // subkategori
					$small['child'][] = array(
						'link' => $matches[1],
						'name' => $matches[2],
						'desc' => $matches[3],
					);
				} else { // kategori
					if ($small) $big[] = $small;
					$small = array(
						'link' => $matches[1],
						'name' => $matches[2],
						'desc' => $matches[3],
					);
				}
			}
		} while (!$p->next_line()->contain('var gaJsHost'));
		G::$cat = $big;
		//var_export($big);
	} else {
		// G::$cat udah ada
	}
	
	// insert sqlite
	foreach (G::$cat as $cat) {
		$parent_id = G::$db->set(array(
			'link' => $cat['link'],
			'name' => $cat['name'],
			'desc' => $cat['desc'],
		))->from('category')->doInsert();
		if (isset($cat['child']) && $cat['child']) {
			foreach ($cat['child'] as $chit) {
				G::$db->set(array(
					'link' => $chit['link'],
					'name' => $chit['name'],
					'desc' => $chit['desc'],
					'parent' => $parent_id,
				))->from('category')->doInsert();
			}
		}
	}
}

function get_cat_tree() {
	if (G::$cat) return G::$cat;
	$raws = G::$db->select('*')->from('category')->order('id ASC')->doFetchAll();
	$arr = array();
	foreach ($raws as $row) {
		$temp = array(
			'id' => $row['id'],
			'link' => $row['link'],
			'name' => $row['name'],
			'desc' => $row['desc'],
		);
		if ($row['parent']) {
			$arr[$row['parent']]['child'][$row['id']] = $temp;
		} else {
			$arr[$row['id']] = $temp;
		}
	}
	G::$cat = $arr;
	return $arr;
}

function get_cat_leaves() {
	$cats = G::$cat ? G::$cat : get_cat_tree();
	$leaves = array();
	foreach ($cats as $cat) {
		if (isset($cat['child'])) {
			$leaves = array_merge($leaves, $cat['child']);
		} else {
			$leaves[] = $cat;
		}
	}
	return $leaves;
}

function stage_fill_album() {
	$cats = get_cat_tree();
	$big = array();
	foreach ($cats as $cat) { // iterate top level only
		$p = new Page(G::$base . $cat['link']);
		$p->go_line('"maintable');
		$curcatlink = '';
		$curcat = $cat;
		do {
			if ($p->curr_line()->contain('class="catlink"')) {
				$matches = $p->curr_line()->regex_match('/class="catlink"><a href="([^"]+)"/');
				$curcatlink = $matches[1];
				$curcat = G::$db->from('category')->where('link', $curcatlink)->doFetchRow();
			} else if ($p->curr_line()->contain('class="alblink')) {
				$info = array('category_id' => $curcat['id']);
				// do {
					// get info of 1 album
					// <span class="alblink"><a href="thumbnails.php?album=233">T-ara</a></span>
					$m = $p->curr_line()->regex_match('/href="([^"]+)">([^<]+)</');
					$info['link'] = $m[1];
					$info['name'] = $m[2];
					$p->go_line('class="albums');
					// <a href="thumbnails.php?album=233" class="albums"><img src="albums/userpics/10001/thumb_T-ara97.jpg" class="image" width="128" height="78" border="0" alt="T-ara97.jpg" /><br /></a>
					$m = $p->curr_line()->regex_match('/src="([^"]+)"/');
					$info['thumbnail'] = $m[1];
					$p->go_line('class="album_stat');
					// <p class="album_stat">133 files, last one added on Dec 28, 2010<br />Album viewed 310 times</p>
					$m = $p->curr_line()->regex_match('/>(.*)<\/p>/');
					$info['desc'] = $m[1];
				// } while (!$p->next_line()->contain('</table>'));
				$big[$curcat['id']][] = $info;
			}
		} while (!$p->next_line()->contain('var gaJsHost'));
	}
	// we have all albums
	file_put_contents('redflava.albums.export', var_export($big, true));
	// insert to database
	foreach ($big as $cat_id => $albums) {
		foreach ($albums as $album) {
			G::$db->set($album)->from('album')->doInsert();
		}
	}
}

function stage_fill_image() {
	$albums = G::$db->from('album')
		->where('id >', 1338) // skip to id 1339 // CHANGEME
		->order('id ASC')
	->doFetchAll();
	foreach ($albums as $album) {	
		$url = G::$base . $album['link'];
		$ada_next = true;
		$images = array();
		// multipage
		do {
			echo $url."\n";
			$p = new Page($url);
			$p->go_line('class="maintable');
			do {if ($p->curr_line()->contain('class="image')) {
				$m = $p->curr_line()->regex_match('/href="([^"]+)".+src="([^"]+)".+alt="([^"]+)".+title="(.*)$/');
				list($all, $href, $src, $alt, $title) = $m;
				$full = str_replace('/thumb_', '/', $src);
				/*
				while (!$p->next_line()->contain('"')) {
					$title .= $p->curr_line()->to_s();
				}
				$m = $p->curr_line()->regex_match('/([^"]*)"/');
				$title .= $m[1];
				*/
				$image = array(
					'album_id' => $album['id'],
					// 'link' => $href,
					// 'filename' => $alt,
					// 'thumbnail' => $src,
					'full' => $full,
					// 'desc' => $title,
				);
				// langsung simpan
				G::$db->set($image)->from('image')->doInsert();
				// $images[] = $image;
			}} while (!$p->next_line()->contain('<script'));
			//  masih ada page berikutnya?
			$p->go_line('title="Next"');
			if ($p->curr_line()->contain('title="Next"')) { // masih ada next
				$m = $p->curr_line()->regex_match('/href="([^"]+)"/');
				$url = G::$base . htmlspecialchars_decode($m[1]);
				// echo $url."\n";
			} else { // habis
				$ada_next = false;
			}
		} while ($ada_next);
		
		// insert to database
		/*
		foreach ($images as $image) {
			G::$db->set($image)->from('image')->doInsert();
		}
		*/
	}	
}

function stage_migrate_image() {
	// aslinya table image nyimpan id, album_id, link, filename, thumbnail, full, dan desc
	// padahal perlunya cuma album_id dan full, untuk itu pengen "dirampingkan"
	// dengan cara table lama (terlanjur didownload) direname (asumsi sudah) jadi `image_old`
	// dan dibuat table baru `image` yg isinya cuma 2 kolom album_id dan full (tanpa id)
	$old_images = G::$db->from('image_old')->doGet();
	while ($old_image = $old_images->fetch(PDO::FETCH_ASSOC)) {
	// foreach ($old_images as $old_image) {
		G::$db->from('image')->set(array(
			'album_id' => $old_image['album_id'],
			'full' => $old_image['full'],
		))->doInsert();
	}
}

function fetch_new_album($link) {
	
}

function stage_update() {
	// http://www.redflava.com/gallery/thumbnails.php?album=lastup&cat=0
	// akses halaman ini dan dari awal gambar2 yg belum ada di database, 
	// pivot sampe ketemu gambat yg sudah ada (asumsi setelahnya sudah ada semua)
	// Note: mungkin aja ada album yg belum ada sebelumnya
	// @TODO
	$url = 'http://www.redflava.com/gallery/thumbnails.php?album=lastup&cat=0';
	$hit_pivot = false; // sudah ketemu batas yg baru?
	while (!$hit_pivot) {
		// fetch page
		$p = new Page($url);
		$p->go_line('class="maintable');
		do {if ($p->curr_line()->contain('class="image')) {
			// fetch per item
			$m = $p->curr_line()->regex_match('/href="([^"]+)".+src="([^"]+)"/');
			list($all, $href, $src) = $m;
			$full = str_replace('/thumb_', '/', $src);
			// pastikan tidak ada di database
			echo "$full\n";
			$check = G::$db->from('image')->where('full', $full)->doFetchRow();
			if ($check) {
				$hit_pivot = true; // sudah nemu 1 yg ada di database, saatnya berhenti
				continue;
			}
			// now we get image full url and detail url
			
			// lets find out the album id
			$ap = new Page(G::$base . htmlspecialchars_decode($href));
			$ap->go_line('class="alblink"');
			$m = $ap->curr_line()->regex_match('/href="(thumb[^"]+)"/');
			$link = $m[1];
			$album = G::$db->from('album')->where('link', $link)->doFetchRow();
			if (!$album) { // wah album belum ada
				echo "ALBUM BARU!! $link\n";
				$album = fetch_new_album($link);
				continue; // skip dah
			}
			// lastly, insert to database
			G::$db->from('image')->set(array(
				'album_id' => $album['id'],
				'full' => $full,
			))->doInsert();
		}} while (!$hit_pivot && !$p->next_line()->contain('<script'));
		// fetch next page
		$p->go_line('title="Next"');
		if ($p->curr_line()->contain('title="Next"')) { // masih ada next
			$m = $p->curr_line()->regex_match('/href="([^"]+)"/');
			$url = G::$base . htmlspecialchars_decode($m[1]);
		} else { // ga ada next, pasti hit_pivot
			$hit_pivot = true; // regardless sebelumnya true/false
		}
	}
}

function stage_display() {
	// html interface untuk memilih album mana yg mau di-download
	?>
	<form method="post">
		Category: <select name="category_id">
			<?php foreach (get_cat_leaves() as $r) echo "<option value='{$r['id']}'>{$r['name']}</option>";?>
		</select>
		<input type="submit" value="Select Category" />
	</form>
	
	<?php if (isset($_REQUEST['category_id'])) : ?>
		<?php $category = G::$db->from('category')->where('id', $_REQUEST['category_id'])->doFetchRow() ?>
		Selected: <?php echo $category['name'] ?>
		<form method="post">
			<input type="checkbox" onclick="select_all_album(this)" />SELECT ALL<br />
			<input type="hidden" name="category_id" value="<?php echo $category['id'] ?>" />
			<table>
				<tr valign="top"><td>
			<?php $albums = G::$db->from('album')->where('category_id', $category['id'])->doFetchAll() ?>
			<?php $percolumn = (int)((count($albums)-1) / 5) + 1 ?>
			<?php $i=0; foreach ($albums as $r) : $i++ ?>
				<input type="checkbox" name="album_ids[]" value="<?php echo $r['id'] ?>" /> 
				<?php echo $r['name'] ?><br />
				<img src="<?php echo G::$base . $r['thumbnail'] ?>" />
				<br />
				<?php if (($i % $percolumn) == 0) echo '</td><td>' ?>
			<?php endforeach ?>
				</td></tr>
			</table>
			<input type="submit" value="Select Albums" />
		</form>
		
		<script type="text/javascript">
		function select_all_album(el) {
			var albums = document.getElementsByName("album_ids[]");
			for (var i in albums) {
				albums[i].checked = el.checked;
			}
		}
		</script>
	<?php endif ?>
	
	<?php if (isset($_REQUEST['album_ids'])) : ?>
		<table>
			<tr valign="top"><td>
		<?php foreach ($_REQUEST['album_ids'] as $album_id) {
			$album = G::$db->from('album')->where('id', $album_id)->doFetchRow();
			$images = G::$db->from('image')->where('album_id', $album['id'])->doFetchAll();
			$percolumn = (int)((count($images)-1) / 8) + 1;
			$i = 0;
			foreach ($images as $image) { $i++;
				$url = G::$base . $image['full'];
				echo "<a href='{$url}'>{$album['name']}</a><br />\n";
				if (($i % $percolumn) == 0) echo '</td><td>';
			}
			echo '</td></tr><tr valign="top"><td>';
		} ?>
			</td></tr>
		</table>
	<?php endif ?>
	<?php
}

function stage_debug() {
	// print_r(G::$db->select('*')->from('category')->doFetchAll());
	print_r(get_cat_leaves());
}

$stage = ($_REQUEST && isset($_REQUEST['stage'])) ? $_REQUEST['stage'] : '';
// $stage = 'update';
if (function_exists('stage_' . $stage)) {
	$func = 'stage_' . $stage;
	$func();
} else {
	?>
	Select mode:
	<form>
		<input type="radio" name="stage" value="display" />Display<br/>
		<input type="submit" value="Select Stage" />
	</form>
	<?php
}