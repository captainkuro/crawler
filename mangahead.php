<?php
require 'crawler.php';
extract($_POST);
?>
<html>
<body>
<form method="POST" action="">
	URL FOLDER: <input type="text" name="base" value="<?=@$base;?>"><br />
    Chapter: <input type="text" name="chapter" value="<?=@$chapter;?>"><br />
	Prefix: <input type="text" name="prefix" value="<?=@$prefix;?>"><br />
	<input type="submit">
</form>
<?
//http://mangahead.com/Manga-English-Scan/History-Strongest-Disciple-Kenichi/Historys-Strongest-Disciple-Kenichi-392-English-Scan
$sitename = "http://mangahead.com";
$pref = @$_POST['base'];
if (!Crawler::is_there($pref, '/index.php/')) {
	$pref = str_replace($sitename.'/Manga', $sitename.'/index.php/Manga', $pref);
}

if (@$base) {
	$finish = false;
	if (Crawler::is_there($pref, '?page=')) {
		$page = Crawler::cutafter($pref, '?page=');
		$pref = Crawler::cutuntil($pref, '?page=');
	} else {
		$page = 1;
	}
	while (!$finish) {
		echo "$base<br/>\n";flush();
		$c = new Crawler($base);
		$c->go2linewhere('mangaviewer_toppest_navig');
		if (Crawler::is_there($c->curline, '&nbsp;&nbsp;&rsaquo;')) {
			$finish = false;
			$base = $pref . '/?page=' . (++$page);
		} else {
			$finish = true;
		}
		$ledak = explode('<img src="', $c->curline);
		$c->close();
		for ($i=1; $i<count($ledak); ++$i) {
			$segm = $ledak[$i];
			$parturl = Crawler::cutuntil($segm, '"');
			$parturl = str_replace('index.php', 'mangas', $parturl);
			$parturl = str_replace('?action=thumb', '', $parturl);
			echo '<a href="'.$sitename.$parturl.'">'.$prefix.'-'.Crawler::n($chapter, 3).'-'.basename($parturl).'</a><br/>'."\n";flush();
		}
	}
}