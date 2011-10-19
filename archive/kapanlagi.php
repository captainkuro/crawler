<?php
require_once "crawler.php";
extract($_POST);
?>
<html>
<body>
	<form action="" method="post">
		Starting URL: <input type="text" name="start_url" value="<?=isset($start_url)?$start_url:''?>" />
		<input type="submit" value="Submit" />
	</form>
<?php
$masih = true;
while ($masih) {	
	echo "$start_url<br/>\n";flush();
	$craw = new Crawler($start_url);
	//first get the pictures
	$craw->go2linewhere('class="photogallery-celeb"');
	$craw->readline();
	$line = $craw->curline;
	//echo $line;
	//echo '<br />HOI<br />';
	$ledakan = explode('<img src="', $line);
	for ($i=1; $i<count($ledakan); $i++) {
		$imgurl = str_replace('/t/', '/', Crawler::cutuntil($ledakan[$i], '"'));
		$file = basename($imgurl);
		echo "<a href='{$imgurl}'>{$file}</a><br />\n";
	}
	//then check the next link
	$craw->go_to('class="arrow">&#187;</a>');
	$url = $craw->getbetweenlast('<a href="', '"');
	if ($url == '#') {
		$masih = false;
	} else {
		$start_url = dirname($start_url.'a').'/'.$url;
	}
	/*
	$craw->go2lineor(array('<span class="global_pref_next_no_link">&#187;', 'class="global_pref_next">&#187;'));
	if (strpos($craw->curline, '<span class="global_pref_next_no_link">&#187;') !== false) {
		$masih = false;
	} else {
		$start_url = dirname($start_url.'a').'/'.$craw->getbetweenlast('<a href="', '"');
	}
	*/
	$craw->close();
	unset($craw);
	flush();
}
?>
</body>
</html>