<?php
require_once "crawler.php";

$base = 'http://www.ez-wallpaper.org';

$berhenti = 0;
$url = $base;
while (!$berhenti) {
	echo "\nURL:$url\n";
	$c = new Crawler($url);
	$c->readline();
	while ($line = $c->readline()) {
		if ($c->strpos('nodeTitle') !== false) {
			$href = $c->getbetween('<a href="', '"');
			$c2 = new Crawler($base.$href);
			$c2->go2linewhere('pageTitle');
			$title = $c2->getbetween('>', '<');
			$c2->go2linewhere('node_images');
			$ledak = explode('<a href="', $c2->curline);
			for ($i=1; $i<count($ledak); $i++) {
				$ahref = substr($ledak[$i], 0, strpos($ledak[$i], '"'));
				echo "<a href='$ahref'>$title</a><br />\n";
			}
			//echo $c2->curline;
			$c2->close();
		} else if ($c->strpos('Go to next page') !== false) {
			echo "\nADA NEXT\n";
			$url = $base.$c->getbetweenlast('</span><a href="', '"');
			break;
		} else if ($c->strpos('Go to previous page') !== false) {
			echo "\nADA PREVIOUS TANPA NEXT\n";
			$berhenti = true;
			break;
		}
	}
	$c->close();
	unset($c);
	flush();
}