<?php
require_once 'crawler.php';
// script isinya quick and dirty spidering kecil2an

$targets = array(
	'wallpaper' => 'http://www.comicgirls.net/pictures/wallpapers/1.html',
	'images' => 'http://www.comicgirls.net/pictures/images/1.html',
	'fanart' => 'http://www.comicgirls.net/pictures/sketches__fanart/1.html',
	'coverart' => 'http://www.comicgirls.net/pictures/coverart/1.html',
	//'gamegirl' => 'http://www.comicgirls.net/pictures/gamegirls/1.html',
);

if (is_file('comicgirls.net.out')) {
	require 'comicgirls.net.out';
	foreach ($a as $k => $list) {
		$txt = '';
		foreach ($list as $e) {
			preg_match('/=[^\\/]+\\/([^&]+)&/', $e, $match);
			$name = $match[1];
			$txt .= "<a href='$e'>$name</a><br />\n";
		}
		file_put_contents('asdf_' . $k . '.html', $txt);
	}
	exit;
}
// http://www.comicgirls.net/thumb.php?photo=categorie01/Madrox_Rog0.JPG&max_size=110
// http://www.comicgirls.net/thumb.php?photo=categorie01/Madrox_Rog0.JPG&max_size=6000&thumb=NO
// Cookie 
$base = 'http://www.comicgirls.net';
$imgs = array();
foreach ($targets as $k => $url) {
	$imgs[$k] = array();
	do {
		echo "$url<br />\n";
		$c = new Crawler($url);
		// Apakah ada next?
		$next = false;
		$c->go_to('>Navigation');
		while (!Crawler::is_there($line = $c->readline(), '<i>(')) {
			if (Crawler::is_there($line, '>Next<')) {
				$next = true; 
				$url = $base . Crawler::extract($line, "href='", "'");
				break;
			}
		}
		// Grab the gallery
		$c->go_to("'catThumb'");
		while (!Crawler::is_there($line = $c->readline(), '</table>')) {
			if (Crawler::is_there($line, 'src=')) {
				$raw = $base . html_entity_decode(Crawler::extract($line, "src='", "'"));
				$new = preg_replace('/&max_size=.*$/', '&max_size=6000&thumb=NO', $raw);
				$imgs[$k][] = $new;
			}
		}
		$c->close();
	} while ($next);
}

var_export($imgs);