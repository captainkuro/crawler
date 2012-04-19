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
			$p->go_line('Main article:');
			if ($p->end_of_line()) break;
			$href = 'http://en.wikipedia.org'.$p->curr_line()->dup()->cut_between('href="', '"')->to_s();
			$p2 = new Page($href);
			while (!$p2->end_of_line()) {
				try {
					$p2->go_line('<td id="vol');
					$vol = $p2->curr_line()->dup()->cut_between('">', '<')->to_s();
					do { if ($p2->curr_line()->contain('<li>Round ')) {
						$last_chapter = $p2->curr_line()->dup()->cut_between('Round ', ':')->to_s();
					}} while (!$p2->next_line()->contain('</table>'));
					$list[$vol] = $last_chapter;
					// echo "v $vol c $last_chapter <br/>\n";
				} catch (Exception $e) {
					break;
				}
			}
			$p->next_line();
		}
		return $list;
	}
	
	public function upgrade_volume_list($list) {
		$more = array();
		$prev = 1;
		foreach ($list as $k => $v) {
			$more[$k] = array(
				'from' => $prev,
				'to' => (int)$v,
			);
			$prev = $v+1;
		}
		return $more;
	}
	
	public function cache_volume_list() {
		return array ( 1 => array ( 'from' => 1, 'to' => 7, ), 2 => array ( 'from' => 8, 'to' => 15, ), 3 => array ( 'from' => 16, 'to' => 24, ), 4 => array ( 'from' => 25, 'to' => 33, ), 5 => array ( 'from' => 34, 'to' => 42, ), 6 => array ( 'from' => 43, 'to' => 51, ), 7 => array ( 'from' => 52, 'to' => 60, ), 8 => array ( 'from' => 61, 'to' => 69, ), 9 => array ( 'from' => 70, 'to' => 78, ), 10 => array ( 'from' => 79, 'to' => 87, ), 11 => array ( 'from' => 88, 'to' => 96, ), 12 => array ( 'from' => 97, 'to' => 105, ), 13 => array ( 'from' => 106, 'to' => 114, ), 14 => array ( 'from' => 115, 'to' => 123, ), 15 => array ( 'from' => 124, 'to' => 132, ), 16 => array ( 'from' => 133, 'to' => 141, ), 17 => array ( 'from' => 142, 'to' => 150, ), 18 => array ( 'from' => 151, 'to' => 159, ), 19 => array ( 'from' => 160, 'to' => 169, ), 20 => array ( 'from' => 170, 'to' => 178, ), 21 => array ( 'from' => 179, 'to' => 187, ), 22 => array ( 'from' => 188, 'to' => 196, ), 23 => array ( 'from' => 197, 'to' => 205, ), 24 => array ( 'from' => 206, 'to' => 214, ), 25 => array ( 'from' => 215, 'to' => 223, ), 26 => array ( 'from' => 224, 'to' => 232, ), 27 => array ( 'from' => 233, 'to' => 241, ), 28 => array ( 'from' => 242, 'to' => 250, ), 29 => array ( 'from' => 251, 'to' => 259, ), 30 => array ( 'from' => 260, 'to' => 268, ), 31 => array ( 'from' => 269, 'to' => 277, ), 32 => array ( 'from' => 278, 'to' => 287, ), 33 => array ( 'from' => 288, 'to' => 296, ), 34 => array ( 'from' => 297, 'to' => 306, ), 35 => array ( 'from' => 307, 'to' => 315, ), 36 => array ( 'from' => 316, 'to' => 324, ), 37 => array ( 'from' => 325, 'to' => 334, ), 38 => array ( 'from' => 335, 'to' => 343, ), 39 => array ( 'from' => 344, 'to' => 352, ), 40 => array ( 'from' => 353, 'to' => 361, ), 41 => array ( 'from' => 362, 'to' => 370, ), 42 => array ( 'from' => 371, 'to' => 379, ), 43 => array ( 'from' => 380, 'to' => 388, ), 44 => array ( 'from' => 389, 'to' => 397, ), 45 => array ( 'from' => 398, 'to' => 406, ), 46 => array ( 'from' => 407, 'to' => 415, ), 47 => array ( 'from' => 416, 'to' => 424, ), 48 => array ( 'from' => 425, 'to' => 433, ), 49 => array ( 'from' => 434, 'to' => 442, ), 50 => array ( 'from' => 443, 'to' => 452, ), 51 => array ( 'from' => 453, 'to' => 462, ), 52 => array ( 'from' => 463, 'to' => 472, ), 53 => array ( 'from' => 473, 'to' => 482, ), 54 => array ( 'from' => 483, 'to' => 492, ), 55 => array ( 'from' => 493, 'to' => 502, ), 56 => array ( 'from' => 503, 'to' => 512, ), 57 => array ( 'from' => 513, 'to' => 522, ), 58 => array ( 'from' => 523, 'to' => 532, ), 59 => array ( 'from' => 533, 'to' => 541, ), 60 => array ( 'from' => 542, 'to' => 551, ), 61 => array ( 'from' => 552, 'to' => 561, ), 62 => array ( 'from' => 562, 'to' => 572, ), 63 => array ( 'from' => 573, 'to' => 583, ), 64 => array ( 'from' => 584, 'to' => 594, ), 65 => array ( 'from' => 595, 'to' => 605, ), 66 => array ( 'from' => 606, 'to' => 616, ), 67 => array ( 'from' => 617, 'to' => 627, ), 68 => array ( 'from' => 628, 'to' => 637, ), 69 => array ( 'from' => 638, 'to' => 648, ), 70 => array ( 'from' => 649, 'to' => 658, ), 71 => array ( 'from' => 659, 'to' => 669, ), 72 => array ( 'from' => 670, 'to' => 679, ), 73 => array ( 'from' => 680, 'to' => 691, ), 74 => array ( 'from' => 692, 'to' => 703, ), 75 => array ( 'from' => 704, 'to' => 713, ), 76 => array ( 'from' => 714, 'to' => 723, ), 77 => array ( 'from' => 724, 'to' => 734, ), 78 => array ( 'from' => 735, 'to' => 745, ), 79 => array ( 'from' => 746, 'to' => 756, ), 80 => array ( 'from' => 757, 'to' => 767, ), 81 => array ( 'from' => 768, 'to' => 777, ), 82 => array ( 'from' => 778, 'to' => 787, ), 83 => array ( 'from' => 788, 'to' => 798, ), 84 => array ( 'from' => 799, 'to' => 806, ), 85 => array ( 'from' => 807, 'to' => 816, ), 86 => array ( 'from' => 817, 'to' => 826, ), 87 => array ( 'from' => 827, 'to' => 836, ), 88 => array ( 'from' => 837, 'to' => 847, ), 89 => array ( 'from' => 848, 'to' => 857, ), 90 => array ( 'from' => 858, 'to' => 868, ), 91 => array ( 'from' => 869, 'to' => 882, ), 92 => array ( 'from' => 883, 'to' => 892, ), 93 => array ( 'from' => 893, 'to' => 901, ), 94 => array ( 'from' => 902, 'to' => 912, ), 95 => array ( 'from' => 913, 'to' => 923, ), 96 => array ( 'from' => 924, 'to' => 933, ), 97 => array ( 'from' => 934, 'to' => 944, ), 98 => array ( 'from' => 945, 'to' => 956, ), );
	}
	
	// open folder of pages, parse filename, move to corresponding volumes
	public function move_pages_to_volumes($list) {
		$path = 'D:\temp\manga\hajime\\';
		$cur_vol = 1; // ubah seperlunya
		$cur_pages = array();
		foreach (scandir($path) as $fname) {
			if (preg_match('/-(\d{3})-/', $fname, $m)) {
				$chap = (int)$m[1];
				if ($chap >= $list[$cur_vol]['from'] && $chap <= $list[$cur_vol]['to']) {
					$cur_pages[] = $fname;
				} elseif ($chap > $list[$cur_vol]['to']) {
					// make dir
					$vname = 'Vol '.Text::create($cur_vol)->pad(2)->to_s();
					if (!is_dir($path.$vname)) mkdir($path.$vname);
					foreach ($cur_pages as $p) {
						rename($path.$p, $path.$vname.'/'.$p);
					}
					$cur_vol++;
					$cur_pages = array($fname);
				}
			}
		}
	}
	
	public function create_batch_zip() {
		$path = 'D:\temp\manga\hajime\\';
		$bat = 'cd "'.$path.'"'.PHP_EOL;
		foreach (scandir($path) as $dname) {
			if (is_dir($path.$dname) && $dname != '.' && $dname != '..') {
				$bat .= '7z a "'.$dname.'.zip" ".\\'.$dname.'\*" -tzip -mx0'.PHP_EOL;
			}
		}
		return $bat;
	}
	
	public function expand_list($list) {
		$result = array();
		foreach ($list as $k => $v) {
			$result[$k] = array(
				'from' => $v[0],
				'to' => $v[1],
			);
		}
		return $result;
	}
}
/**
$i = new Ippo();
$list = $i->cache_volume_list();
$i->move_pages_to_volumes($list);
/**/

/**/
require_once 'class/manga_crawler.php';
// katekyo hitman reborn
$list = array(
	1=>array(1,7), 2=>array(8,16), 3=>array(17,25), 4=>array(26,33), 5=>array(34,42), 
	6=>array(43,51), 7=>array(52,60), 8=>array(61,70), 9=>array(71,79), 10=>array(80,89),
	11=>array(90,98), 12=>array(99,107), 13=>array(108,116), 14=>array(117,125), 15=>array(126,134),
	16=>array(135,143), 17=>array(144,153), 18=>array(154,164), 19=>array(165,174), 20=>array(175,185),
	21=>array(186,195), 22=>array(196,205), 23=>array(206,215), 24=>array(216,226), 25=>array(227,237),
	26=>array(238,247), 27=>array(248,258), 28=>array(259,268), 29=>array(269,278), 30=>array(279,288),
	31=>array(289,299),
);
Manga_Crawler::move_pages_to_volumes('D:\temp\Katekyo Hitman Reborn\\', $list, 14);
/**/