<?php
/*
Support: (yg H semua)
fakku.net
gallery.hentaifromhell.net
lu.scio.us
imagefap.com
doujin-moe.us
bobx.com
imagearn.com
etc
*/
require_once "crawler.php";
extract($_POST);

function imagefap_realm($base) {
	//http://www.imagefap.com/pictures/2525382/Highly-Erotic-Nude-Models-HQ-Picture
	$sitename = "http://www.imagefap.com";
	$finish = false;
	$firstbase = $base;
	$dir = basename($base);
	$i = 1;
	while (!$finish) {
		$c = new Crawler($base);
		echo $base."<br/>";
		$c->go_to(array('<table style=', ':: next ::'));
		if (Crawler::is_there($c->curline, ':: next ::')) {
			$finish = false;
			$urld = Crawler::extract($c->curline, 'href="', '"');
			$base = $firstbase.html_entity_decode($urld);
			$c->go_to('<table style=');
		} else {
			$finish = true;
		}
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, 'border=0')) {
				$img = Crawler::extract($line, 'src="', '"');
				$img = str_replace('/thumb/', '/full/', $img);
				$filename = basename($img);
				$ext = Crawler::cutfromlast($filename, '.');
				$text = Crawler::n($i++, 4);
				echo "<a href='$img'>$dir</a><br/>\n";
			} else if (Crawler::is_there($line, '</form>')) {
				break;
			}
		}
		$c->close();
	}
}

function doujinmoe_realm($start_url) {
	// http://www.doujin-moe.us/phpgraphy/index.php?dir=Artists%2FAmatarou%2FDaisy%20%28English%29
	// http://www.doujin-moe.us/phpgraphy/pictures/Artists/Amatarou/Daisy%20(English)/.thumbs/lr_Daisy%20-%20001.jpg
	// http://www.doujin-moe.us/phpgraphy/pictures/Artists/Amatarou/Daisy%20(English)/Daisy%20-%20001.jpg
	$base = 'http://www.doujin-moe.us/phpgraphy/';
	$c = new Crawler($start_url);
	$c->go_to('"slideshow_start_count"');
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, '"slideshow_path"')) {
			// pictures/Artists/Amatarou/Daisy (English)/.thumbs/lr_Daisy - 001.jpg
			$raw = Crawler::extract($line, '">', '</'); 
			// $raw = str_replace('/.thumbs/lr_', '/', $raw); // HQ
			$img = $base . $raw;
			$text = basename(dirname(dirname($raw)));
			// $text = basename($img);
			echo "<a href='$img'>$text</a><br/>\n";
		} else if (Crawler::is_there($line, '<table')) {
			break;
		}
	}
	$c->close();
}

function bobx_realm($start_url) {
	// http://www.bobx.com/av-idol/akiho-yoshizawa/series-akiho-yoshizawa-0-10-10.html
	$base_url = 'http://www.bobx.com/';
	// parse url
	preg_match('/(.*\D\-)(\d+)(\-.*)/', $start_url, $matches);
	$pra = $matches[1];
	$i = $matches[2];
	$pasca = $matches[3];
	$masih = false;
	do {
		$masih = false;
		echo "$start_url<br/>\n";
		$c = new Crawler($start_url, array(
			'use_curl' => true,
			'agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13',
		));
		
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, '<img src="/thumbnail/')) {
				$text = Crawler::extract($line, '<img src="', '"');
				$text = str_replace('/thumbnail', '', $text);
				$text = str_replace('-preview', '', $text);
				$file = basename($text);
				echo "<a href='{$base_url}{$text}'>{$file}</a><br />\n";
				$masih = true; // Masih ada harapan next page
			} else if (Crawler::is_there($line, 'FULL NAVI:')) {
				break;
			}
		}
		
		$c->close();
		
		$i += 100;
		$start_url = $pra . $i. $pasca;
		//echo $start_url;
	} while ($masih);
}

function imagearn_realm($base) {
	// http://imagearn.com/gallery.php?id=102868
	// http://thumbs2.imagearn.com/05072010/4099544.jpg
	// http://img.imagearn.com/imags/05072010/4099544.jpg
	$sitename = "http://imagearn.com";
	$c = new Crawler($base);
	$c->go_to('class="galleries"');
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, 'src="')) {
			$img = Crawler::extract($line, 'src="', '"');
			$img = str_replace('thumbs2.', 'img.', $img);
			$img = str_replace('.com/', '.com/imags/', $img);
			$name = basename($img);
			echo "<a href='$img'>$name</a><br />\n";flush();
		} else if (Crawler::is_there($line, 'class="clear"')) {
			break;
		}
	}
	$c->close();
}

function ichan_realm($start_url) {
	// http://ichan.org/l/res/33005.html
	// <a href="http://c3a56840.linkbucks.com/url/http://ichan.org/l/src/128001023914.jpg">
	$c = new Crawler($start_url);
	$i = 0;
	$c->go_to('class="filesize"');
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, '<a href') && Crawler::is_there($line, 'http://ichan.org/') && Crawler::is_there($line, '/src/')) {
			$raw = Crawler::extract($line, '"', '"');
			$img = preg_replace('/http:\/\/[\w\.]+\/url\//', '', $raw);
			$text = Crawler::n(++$i, 3) . '.jpg';
			echo "<a href='$img'>$text</a><br/>\n";
		} else if (Crawler::is_there($line, '"footerbg"')) {
			break;
		}
	}
	$c->close();
}

function hbrowse_realm($start_url) {
	// http://www.hbrowse.com/10570
	// http://www.hbrowse.com/thumbnails/10570/c00000
	// http://www.hbrowse.com/data/10570/c99999/19.jpg
	// http://www.hbrowse.com/data/10570/c99999/zzz/19.jpg
	$c = new Crawler($start_url);
	// ambil title
	$c->go_to('>Title<');
	$title = trim(Crawler::cutfrom1($c->curline, ': '));
	$c->go_to('class="listEntry"');
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, '/thumbnails/')) {
			$link = Crawler::extract($line, 'class="thumbLink" href="', '"');
			echo "$link<br/>\n";
			$x = new Crawler($link);
			$x->go_to('id="main"');
			while ($line = $x->readline()) {
				if (Crawler::is_there($line, '/zzz/')) {
					$raw = Crawler::extract($line, 'src="', '"');
					$img = str_replace('/zzz/', '/', $raw);
					//$text = basename($link) . '-' . basename($img);
					$text = $title;
					echo "<a href='$img'>$text</a><br/>\n";
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
}

function doujintoshokan_realm($start_url) {
	// http://www.doujintoshokan.com/series/Naburi
	// http://www.doujintoshokan.com/read/Naburi/Desudesu/v._2
	$opt = array(
		'use_curl' => true,
		'agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13',
		'cookie' => 'filter=0; mt_sessionhash=1be0760d9c45201308fe98665f5d520f; mt_lastvisit=1295635463; mt_lastactivity=0; __utma=51216956.198413083.1295637122.1295637122.1295637122.1; __utmb=51216956.3.10.1295637122; __utmc=51216956; __utmz=51216956.1295637122.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none)',
	);
	$base = 'http://www.doujintoshokan.com';
	$title = basename($start_url);
	$c = new Crawler($start_url, $opt);
	// Get list of chapters
	$chs = array();
	$chaptertext = array();
	$c->go_to("class='cont_mid'");
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, 'oLoc("')) {
			$chs[] = Crawler::extract($line, 'oLoc("', '"');
			$line = $c->readline();
			$line = $c->readline();
			$chaptertext[] = Crawler::extract($line, '">', '</a>');
		} else if (Crawler::is_there($line, '</tr></table>')) {	
			break;
		}
	}
	$c->close();
	$chs = array_reverse($chs);
	$chaptertext = array_reverse($chaptertext);
	
	// Open each of them
	foreach ($chs as $i => $url) {
		$subtitle = $chaptertext[$i];
		$c = new Crawler($base . $url, $opt);
		$c->go_to('class="headerSelect"');
		$c->readline();
		$pages = Crawler::extract_to_array($c->curline, 'value="', '"');
		$c->close();
		
		foreach ($pages as $page) {
			$c = new Crawler($base . $page, $opt);
			$c->go_to('id="readerPage"');
			$img = Crawler::extract($c->curline, 'src="', '"');
			// $text = basename($img);
			$text = $subtitle;
			echo "<a href='$img'>$text</a><br />\n";
			$c->close();
		}
		
	}
}

// http://www.hentairules.net/gal/_2010/isao_majimeya_pack_of_6_works.html
function hentairules_realm($url) {
	$raw = file_get_contents($url);
	$hrefs = Crawler::extract_to_array($raw, 'href="', '"');
	foreach ($hrefs as $i => $href) {
		$link = str_replace('http://www.hentairules.net/gal/re.php?redir=', '', $href);
		echo "<a href='$link'>$i</a><br />\n";
	}
}

// http://gallery.ryuutama.com/view.php?manga=386
function ryuutama_realm($url) {
	$base = 'http://gallery.ryuutama.com/';
	$api = "http://gallery.ryuutama.com/api.php?grab=manga&id=%s&page=1&cache=%s";
	$c = new Crawler($url);
	$c->go_to('manga = "');
	preg_match('/manga = "([^"]+)"/', $c->curline, $m);
	$id = $m[1];
	$c->go_to('total_pages = "');
	preg_match('/total_pages = "([^"]+)"/', $c->curline, $m);
	$n = $m[1];
	$c->close();
	// obtain all images
	$c = new Crawler(sprintf($api, $id, $n));
	$images = explode(' ', $c->curline);
	$c->close();
	foreach ($images as $src) {
		$name = basename(dirname($src));
		echo "<a href='$base$src'>$name</a><br />\n";
	}
}

// http://www.animephile.com/hentai/full-metal-panic.html?lzkfile=Full+Metal+Panic!%2FFull+Metal%2F
function animephile_realm($url) {
	$base = 'http://www.animephile.com';
	$name = basename($url);
	$name = Crawler::cutuntil($name, '.');
	if (strpos($url, '/hentai-doujinshi/')) {
		$c = new Crawler($url);
		$c->go_to('id="mainimage"');
		preg_match('/"viewerLabel"> of (\d+)<\//', $c->curline, $m);
		$max = $m[1];
		for ($i=1; $i<=$max; $i++) {
			$c = new Crawler($url.'?page='.$i);
			$c->go_to('id="mainimage"');
			// current image
			preg_match('/id="mainimage" src="([^"]+)"/', $c->curline, $m);
			$r = $m[1];
			echo "<a href=\"$base$r\">$name</a><br />\n";
		}
	} else {
		$c = new Crawler($url);
		$c->go_to('id="gallery"');
		$raw = Crawler::extract_to_array($c->curline, 'src="', '"');
		foreach ($raw as $r) {
			$r = str_replace('/thumbs/', '/', $r);
			$name = basename(dirname($r));
			echo "<a href=\"$r\">$name</a><br />\n";
		}
	}
}

// http://thedoujin.com/index.php?page=post&s=list&tags=parent:715616
// http://www.thedoujin.com/thumbnails/462/thumbnail_ebe3a4ff53cf36125308e5760a72ae1567d37576.jpg?715616
// http://img1.thedoujin.com//images/462/ebe3a4ff53cf36125308e5760a72ae1567d37576.jpg?715616

function thedoujin_realm($url) {
	$c = new Crawler($url);
	$c->go_to('class="content"');
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, 'src=')) {
			$thumb = Crawler::extract($line, 'src="', '"');
			$img = str_replace('/www.', '/img1.', $thumb);
			$img = str_replace('/thumbnails/', '/images/', $img);

			$img = str_replace('/thumbnail_', '/', $img);
			$name = basename($img);
			echo "<a href='$img'>$name</a><br />\n";
		} else if (Crawler::is_there($line, 'id="paginator"')) {
			break;
		}
	}
	$c->close();
}

function old_hfh_realm($url) {
	$name = basename($url);
	$c = new Crawler($url);
	$exp = Crawler::extract_to_array($c->curline, 'href="', '"');
	foreach ($exp as $e) {
		$img = preg_replace('/^.*redirect\.html\?/', '', $e);
		echo "<a href='$img'>$name</a><br/>\n";
	}
}

function rule34($url) {
	$text = rawurldecode(basename(dirname($url)));
	$site = 'http://rule34.paheal.net';
	$continue = true;
	while ($continue) {
		echo "$url<br/>";
		$c = new Crawler($url);
		$c->go_to("id='Navigationleft'");
		$c->readline();
		// $c->readline();
		$line = $c->curline;
		if (preg_match('/<a href="([^\'"]+)">Next/', $line, $m)) {
			$url = $site . $m[1];
		} else {
			$continue = false;
		}
		$c->go_to("id='Imagesmain'");
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, '>Image Only<')) {
				$href = Crawler::extract($line, "href='", "'");
				echo "<a href='$href'>$text</a><br/>\n";
			} else if (Crawler::is_there($line, 'id="footer"')) {
				break;
			}
		}
	}
	
}

function sankakucomplex($url) {
	if (strpos($url, '/idol.')) {
		$base = 'http://idol.sankakucomplex.com';
	} else {
		$base = 'http://chan.sankakucomplex.com';
	}
	$page = 1;
	do {
		if (isset($_GET['limit'])) {
			if ($page > $_GET['limit']) break;
		}
		$purl = $url.'&page='.$page;
		$P = new Page($purl);
		$T = new Text($P->content());
		$a = $T->extract_to_array('href="', '"');
		foreach ($a as $i => $e) {
			$E = new Text($e);
			if (!$E->contain('/post/show')) {
				unset($a[$i]);
			}
		}
		if (!count($a)) break;
		foreach ($a as $i => $e) {
			$E = new Text($e);
			$kurl = $base . $e;
			$P = new Page($kurl);
			$P->go_line('id="lowres"');
			if ($P->end_of_line()) {
				$P->reset_line();
				$P->go_line('id="highres"');
			}
			$img = $P->curr_line()->cut_between('href="', '"')->to_s();
			$P->reset_line();
			$P->go_line('id="post_old_tags"');
			$tag = $P->curr_line()->cut_between('value="', '"')->substr(0, 150)->to_s(); // max 100 karakter
			echo "<a href='$img'>$tag</a><br />\n";
		}
		$page++;
	} while (true);
}

function readhentaionline($url) {
// http://readhentaionline.com/read-gohoushi-ayanami-san-hentai-manga-online/
	$base = 'http://readhentaionline.com';
	$chunk = basename($url);
	preg_match('/read-(.+)-hentai-manga-online/', $chunk, $m);
	$title = $m[1];
	
	$p = new Page($url);
	$p->go_line('id="gallery"');
	$url = $base . $p->next_line()->dup()
		->cut_between('href="', '"')
	->to_s();
	$m = $p->curr_line()->regex_match('/Total No of Images in Gallery: (\d+)/');
	$pages = $m[1];
	
	$p = new Page($url);
	$t_content = new Text($p->content());
	$raw = $t_content->extract_to_array('src="', '"');
	// search for image
	foreach ($raw as $e) {
		if (preg_match('/1\.jpg$/', $e)) {
			$src = $e;
			break;
		}
	}
	if (!isset($src)) throw new Exception('Image not found');
	$img_dir = dirname($src);
	for ($i=1; $i<=$pages; $i++) {
		echo "<a href='$img_dir/$i.jpg'>$title</a><br/>\n";
	}
}

?>
<html>
<body>
	I support many H sites
	<form action="" method="post">
		Start url: <input type="text" name="start_url" value="<?=isset($start_url)?$start_url:''?>" />
		<input type="submit" value="Submit" />
	</form>
<?php 
if ($_POST) {
	$parsed = parse_url($start_url);
	// based on the $start_url, call appropriate function
	if (preg_match('/fakku\.net/', $start_url)) {
		require_once('class/spider_fakku.php'); 
		$s = new Spider_Fakku($start_url);
		$s->go();
	} else if (preg_match('/lu\.scio\.us/', $start_url)) {
		require_once 'class/spider_luscious.php';
		$s = new Spider_Luscious($start_url);
		$s->go();
	} else if (preg_match('/gallery\.hentaifromhell\.net.*hfhgallery/', $start_url)) {
		require_once 'class/spider_hfhgallery1.php';
		$s = new Spider_Hfhgallery1($start_url);
		$s->go();
	} else if (preg_match('/gallery1?\.hentaifromhell\.net.*hfh/', $start_url)) {
		require_once 'class/spider_hfhgallery1.php';
		$s = new Spider_Hfhgallery1($start_url);
		$s->go();
	} else if (preg_match('/gallery\.hentaifromhell\.net.*level=album/', $start_url)) {
		require_once 'class/spider_hfhgallery2.php';
		$s = new Spider_Hfhgallery2($start_url);
		$s->go();
	} else if (preg_match('/hentairules\.net\/gal\//', $start_url)) {
		hentairules_realm($start_url);
	} else if (preg_match('/hentaifromhell\.net/', $start_url)) {
		old_hfh_realm($start_url);
	} else {
		// Simple mapping host => function
		$map = array(
			'imagefap.com' => 'imagefap_realm',
			'doujin-moe.us' => 'doujinmoe_realm',
			'bobx.com' => 'bobx_realm',
			'imagearn.com' => 'imagearn_realm',
			'ichan.org' => 'ichan_realm',
			'hbrowse.com' => 'hbrowse_realm',
			'doujintoshokan.com' => 'doujintoshokan_realm',
			'gallery.ryuutama.com' => 'ryuutama_realm',
			'animephile.com' => 'animephile_realm',
			'thedoujin.com' => 'thedoujin_realm',
			'rule34.paheal.net' => 'rule34',
			'sankakucomplex.com' => 'sankakucomplex',
			'readhentaionline.com' => 'readhentaionline',
		);
		$found = false;
		foreach ($map as $host => $func) {
			if (Crawler::is_there($parsed['host'], $host)) {
				$func($start_url);
				$found = true;
				break;
			}
		}
		if (!$found) echo 'Not supported yet';
	}
}
?>
</body>
</html>