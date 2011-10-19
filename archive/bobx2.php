<?php
require_once "crawler.php";
extract($_POST);

$base_url = 'http://www.bobx.com/';

function crawl_one($url) {
	global $base_url;
	$hasil = '';
	echo 'url:'.$url.'<br />';flush();
	$craw = new Crawler($url, true);
	//retrieve array of pages
	//echo the first image url
	while (!feof($craw->stream)) {
		$craw->go2linewhere('<img src="/idol');
		$text = $craw->getbetween('<img src="', '"');
		$text = str_replace('-preview', '', $text);
		$file = basename($text);
		//$hasil .= "<a href='{$base_url}{$text}'>{$file}</a><br />\n";
		echo "<a href='{$base_url}{$text}'>{$file}</a><br />\n";
		flush();
	}
	$craw->close();
	return $hasil;
}

/*EXPERIMENT
$f = fopen('./bobx-list.txt', 'r');
$a = array();
while ($l = fgets($f)) {
	if (trim($l)) {
		$a[] = trim($l);
	}
}
fclose($f);
$a = array_unique($a);
foreach ($a as $v) {
	echo "$v\n";
}
exit();
*/

$start = 'http://www.bobx.com/idol/';

$c = new Crawler($start, true);
$c->parse_http_header();
$malas = true;
$prev = $link = '';
while ($line = $c->readline()) {
	if (strpos($line, '|') !== false) {
		//get 1 page
		$prev = $link;
		$link = Crawler::extractlast($line, 'HREF="', '/"');
		
		if ($link == 'sumire-sakurai') $malas = false;
		$pagen = Crawler::extract($line, '"red">', '<');
		$gal = "$start$link/gallery-$link-0-1-$pagen.html";
		if (!$malas) crawl_one($gal);
		flush();
	} else if (strpos($line, '</BODY>') !== false) {
		break;
	}
}
$c->close();