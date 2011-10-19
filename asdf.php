<?php
require_once 'class/text.php';
require_once 'class/page.php';
// http://babes.echnie.nl/
// http://babes.echnie.nl/models/
function idol_sankaku() {
	$base = 'http://idol.sankakucomplex.com';
	$t = file_get_contents('idol-sankaku.parthtml');
	$T = new Text($t);
	$a = $T->extract_to_array('href="', '"');
	foreach ($a as $e) {
		$E = new Text($e);
		if ($E->contain('/post/show')) {
			$url = $base . $e;
			$P = new Page($url);
			$P->go_line('id="highres"');
			$img = $P->curr_line()->cut_between('href="', '"')->to_s();
			$P->reset_line();
			$P->go_line('id="post_old_tags"');
			$tag = $P->curr_line()->cut_between('value="', '"')->substr(0, 150)->to_s(); // max 100 karakter
			echo "<a href='$img'>$tag</a><br />\n";
		}
	}
}
// idol_sankaku();

// http://idol.sankakucomplex.com/post/index?commit=Search&tags=rating%3Ae%20uncensored&page=2
function idol_sankaku2($base_url, $from, $to) {
	$base = 'http://idol.sankakucomplex.com';
	for ($i=$from; $i<=$to; $i++) {
		$P = new Page($base_url . '&page=' . $i);
		$T = new Text($P->content());
		$a = $T->extract_to_array('href="', '"');
		foreach ($a as $e) {
			$E = new Text($e);
			if ($E->contain('/post/show')) {
				$url = $base . $e;
				$P = new Page($url);
				$P->go_line('id="highres"');
				$img = $P->curr_line()->cut_between('href="', '"')->to_s();
				$P->reset_line();
				$P->go_line('id="post_old_tags"');
				$tag = $P->curr_line()->cut_between('value="', '"')->substr(0, 150)->to_s(); // max 100 karakter
				echo "<a href='$img'>$tag</a><br />\n";
			}
		}
	}
}
// idol_sankaku2('http://idol.sankakucomplex.com/?tags=uncensored+sex++-vaginal&commit=Search', 1, 1);

// http://booru.nanochan.org/post/list/asaekkiga/
function booru_nanochan() {
	$base = 'http://booru.nanochan.org/post/list/asaekkiga/';
	for ($i=1; $i<=9; $i++) {
		$t = file_get_contents($base.$i);
		$T = new Text($t);
		$a = $T->extract_to_array("src='/t/", "'");
		foreach ($a as $e) {
			$e = str_replace('.t.', '.', $e);
			$name = basename($e);
			echo "<a href='http://booru.nanochan.org/i/$e'>$name</a><br />\n";
		}
		
	}
}
// booru_nanochan();