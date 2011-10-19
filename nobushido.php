<?php
/*
ini untuk crawling webcomic
http://noneedforbushido.com/2002/comic/1/
supply starting chapter, crawl sampe abis

*/
require_once 'class/page.php';
require_once 'class/text.php';
$start = 'http://noneedforbushido.com/2002/comic/1/';
$next = true;
while ($next) {
	$p = new Page($start);
	$p->go_line('class="comic-item');
	$src = $p->curr_line()->dup()
		->cut_between('src="', '"')->to_s();
	$n = Text::create(basename($start))->pad(3,0)->to_s();
	$year = Text::create($start)->cut_between('.com/', '/')->to_s();
	$text = "$year-comic$n";
	echo "<a href='$src'>$text</a><br />\n";
	// determine $next
	$p->go_line('class="next-comic-link');
	if ($p->curr_line()->contain('current-comic')) $next = false;
	$start = $p->curr_line()->dup()
		->cut_between('href="', '"')->to_s();
}