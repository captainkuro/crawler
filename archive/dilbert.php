<?php
require_once "crawler.php";

$start_date = '2009-03-10';
$base_url = 'http://www.dilbert.com';
$middle_url = '/strips/comic/';
extract($_GET);
$selesai = false;

$url = $base_url.$middle_url.$start_date;
while (!$selesai) {
	$ada_next = false;
	
	$c = new Crawler($url);
	echo "URL is $url<br />\n";flush();
	$c->go2lineor(array('STR_Content', 'STR_Prev'));
	//echo "go2lineor selesai\n";flush();
	if ($c->strpos('STR_Prev') !== false) {	//masih ada next
		$ada_next = true;
		$url = $base_url.$c->getbetween('<a href="', '"');
		$c->go2linewhere('STR_Content');
	} else {
		$ada_next = false;
		$selesai = true;
	}
	$c->readline();
	$img = $c->getbetween('<img src="', '"');
	echo "<a href='$base_url$img'>$start_date</a><br />\n";
	$start_date = Crawler::extract($url, 'comic/', '/');
	$c->close();
	echo "Closed\n";
	flush();
}