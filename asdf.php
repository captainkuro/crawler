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

function vote_delamibrand($id) {
	$url = 'http://v2web.delamibrands.com/ss2011/vote.php?id='.$id;
	$data = array(
		'email' => Text::random_email(),
		'id' => $id,
		'submit' => 'Vote',
	);
	$p = new Page($url, array(
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $data,
		'become_firefox' => true,
		CURLOPT_REFERER => $url,
	));
	// echo '<pre>'.$p->content().'</pre>';
}
function do_vote_delamibrand() {
	$i = 1;
	$pool_id = array(1,2,7,8,11,12,15,16,17,18,20,21,22,23,24,25,27,28,29,30,31,32,33,37,39,40,41,42,44,46,47);
	while (true) {
		foreach ($pool_id as $id) {
			echo $id.'-'.$i++.' ';
			vote_delamibrand($id);
			sleep(5);
			vote_delamibrand(46);
			sleep(5);
		}
	}
}
// do_vote_delamibrand();

class Ippo {
	// grab list of volume->chapters from 
	// http://en.wikipedia.org/wiki/List_of_Hajime_no_Ippo_chapters
	public function grab_volume_chapters() {
		$p = new Page('http://en.wikipedia.org/wiki/List_of_Hajime_no_Ippo_chapters');
		$list = array();
		while (!$p->end_of_line()) {
			try {
				$p->go_line('Main article:');
			} catch (Exception $e) {
				break;
			}
			$href = 'http://en.wikipedia.org'.$p->curr_line()->dup()->cut_between('href="', '"')->to_s();
			$p2 = new Page($href);
			while (!$p2->end_of_line()) {
				try {
					$p2->go_line('<td id="vol');
				} catch (Exception $e) {
					break;
				}
				$vol = $p2->curr_line()->dup()->cut_between('">', '<')->to_s();
				do { if ($p2->curr_line()->contain('<li>Round ')) {
					$last_chapter = $p2->curr_line()->dup()->cut_between('Round ', ':')->to_s();
				}} while (!$p2->next_line()->contain('</table>'));
				$list[$vol] = $last_chapter;
				echo "v $vol c $last_chapter <br/>\n";
			}
			$p->next_line();
		}
		return $list;
	}
	
	// open folder of pages, parse filename, move to corresponding volumes
	public function move_pages_to_volumes() {
		
	}
}
$i = new Ippo();
