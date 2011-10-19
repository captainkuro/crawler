<?php
// class autoloading in index.php

// http://hentaifromhell.net/category/doujins
class G {
	public static $base = 'http://hentaifromhell.net';
	public static $db = null;
}
$dbpath = realpath('./sqlite/hfh.db3');
G::$db = new DB(array(
	'dsn' => 'sqlite:' . $dbpath,
	'username' => null,
	'password' => null,
));
/*
table 'album'
id | title | link | date | desc | thumbnail | genre | page | author | gallery

table 'image'
album_id | link
*/

// me-prepend G::$base
function linkify($link) {
	if (strpos($link, 'http://') !== 0) { // tidak ada protocol?
		if (strpos($link, '/') === 0) { // diawali slash?
			return G::$base . $link;
		} else {
			return G::$base .'/'. $link;
		}
	} else {
		return $link;
	}
}

// harus ada class="titPosts", tanda bukan ads
function filter_ads($val) {
	return strpos($val, 'class="titPosts"') !== false;
}

// menerima raw HTML suatu post, mengembalikan beragam informasi
function extract_desc($desc) {
	$Tdesc = new Text($desc);
	$stripped = strip_tags($desc);
	$Tstripped = new Text($stripped);
	// date berada di dalam <h3>..</h3>
	$date = $Tdesc->dup()->cut_between('<h3>', '</h3>')->to_s();
	$pdate = date('Y-m-d', strtotime($date));
	// title contohnya >Kamyla</a></h1>
	$m = $Tdesc->regex_match('/>([^<]+)<\/a><\/h1>/');
	$ptitle = $m[1];
	// link contohnya <h1 id="h1_link"><a href="http://hentaifromhell.net/doujins/hot-tails-complete-special"
	$m = $Tdesc->regex_match('/"h1_link"><a href="([^"]+)"/');
	$plink = $m[1];
	// [optional] page contoh a, 171 pages. B
	if ($m = $Tstripped->regex_match('/(\d+)[\s\w]+pages/i')) {
		$ppage = (int)$m[1];
	} else {
		$ppage = null;
	}
	// [optional] author contoh s. By Jamming.
	if ($m = $Tstripped->regex_match('/\WBy\s+([^\.]+)\./i')) {
		$pauthor = $m[1];
	} else {
		$pauthor = null;
	}
	// [optional] genre contoh Genre: Hentai, M/F, b
	if ($m = $Tstripped->regex_match('/Genre\s*:\s*([^\.]+)\./i')) {
		$raw = $m[1];
		$expl = explode(',', $raw);
		$pgenre = $expl;
		$pgenre = array_map('trim', $pgenre);
		$pgenre = array_map('strtolower', $pgenre);
		// flaten
		$pgenre = '#'.implode('#', $pgenre).'#';
	} else {
		$pgenre = null;
	}
	// [optional] thumbnail
	if ($m = $Tdesc->regex_match('/src="([^"]+wp-content\/uploads[^"]+)"/')) {
		$pthumbnail = $m[1];
	} else {
		$pthumbnail = null;
	}
	
	return array(
		'title' => $ptitle,
		'link' => $plink,
		'date' => $pdate,
		'desc' => $desc,
		'page' => $ppage,
		'author' => $pauthor,
		'genre' => $pgenre,
		'thumbnail' => $pthumbnail,
	);
}

// Fill table with initial data
function stage_awal() {
	// crawl backwards 
	// http://hentaifromhell.net/category/doujins/page/645
	for ($i=646; $i>=1; $i--) {
		echo "$i..<br />\n";
		$p = new Page('http://hentaifromhell.net/category/doujins/page/' . $i);
		// $p = new Page('file:///d:/temp/yarget/doujins-1.htm'); // parse offline file
		$l = explode('<div class="POSTS">', $p->content());
		array_shift($l);
		$last_i = count($l) - 1;
		$l[$last_i] = substr($l[$last_i], 0, strpos($l[$last_i], '<div class="POSTS"'));
		$l = array_filter($l, 'filter_ads');
		// print_r($l);
		foreach ($l as $el) {
			$info = extract_desc($el);
			// print_r($info);
			echo $info['link']."<br />\n";
			G::$db->from('album')->set($info)->doInsert();
		}
		// $clean = array_map('strip_tags', $l);
		// print_r($clean);
		// exit;//debug purpose only
	}
}

// Retrieve all rows and insert each gallery link
function stage_fill_gallery() {
	// what about all-chapter gallery?
}

// Iterate through all albums, fetch all images/links to database
function stage_retrieve_image() {
}

// Access most recent posts, insert complete post (album, gallery, until images per post)
function stage_update() {
}

$stage = ($_REQUEST && isset($_REQUEST['stage'])) ? $_REQUEST['stage'] : '';
$stage = 'awal';
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