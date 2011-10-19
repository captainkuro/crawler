<?php
require 'crawler.php';
//http://www.viraindo.com/
$site = 'http://www.viraindo.com/';
$c = new Crawler($site);
$c->go_to('WIDTH=273');
while ($line = $c->readline()) {
	if (Crawler::is_there($line, 'href="')) {
		$page = Crawler::extract($line, 'href="', '"');
		$ket = Crawler::extract($line, '">', '</a');
		$d = new Crawler($site.$page);
		$d->go_to('<img src="');
		$img = $d->getbetween('<img src="', '"');
		echo "<a href='$site$img'>$ket</a><br/>\n";flush();
		$d->close();
	} else if (Crawler::is_there($line, '<p></TD></TR>')) {
		break;
	}
}
$c->close();