<?php
require_once('crawler.php');//class Crawler

$base = 'http://gravure.ecchi-squad.net/images/gravure/';
$folders = array();

$craw = new Crawler($base);
$craw->go2linewhere('<img src="/icons/folder.gif"');
while (strpos($craw->curline, '</pre>') === false) {
	$folders[] = $craw->getbetween('<a href="', '"');
	$craw->readline();
}
$craw->close();
//print_r($folders);
foreach ($folders as $folder) {
	unset($craw);
	$craw = new Crawler($base.$folder);
	$files = array();
	
	$craw->go2linewhere('<img src="/icons/image2.gif"');
	while (strpos($craw->curline, '</pre>') === false) {
		$files[] = $craw->getbetween('<a href="', '"');
		$craw->readline();
	}
	$craw->close();
	$fold = substr($folder, 0, strlen($folder)-1);
	foreach ($files as $file) {
		echo "<a href=\"{$base}{$folder}{$file}\">{$fold}</a><br />\n";
	}
	flush();
}