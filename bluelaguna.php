<?php
require_once "crawler.php";
extract($_POST);
?>
<html>
<body>
<form action="" method="post">
	Start url: <input type="text" name="start_url" value="<?=isset($start_url)?$start_url:''?>" />
	<input type="submit" />
</form>
<?php
$basename = 'http://bluelaguna.net';
if ($start_url) {
	$c = new Crawler($start_url);
	$c->go2linewhere('<iframe id="rectangle"');
	$c->close();
	$ledak = explode('<a href="', $c->curline);
	$big = array();
	for ($i=1; $i<count($ledak); ++$i) {
		$aurl = $basename . Crawler::cutuntil($ledak[$i], '"');
		echo "$aurl<br />";
		$c = new Crawler($aurl);
		$c->go2linewhere('<iframe id="rectangle"');
		$c->close();
		$ledak2 = explode('<a href="', $c->curline);
		for ($j=1; $j<count($ledak2); ++$j) {
			$burl = Crawler::cutuntil($ledak2[$j], '"');
			echo '<a href="'. htmlentities($burl) .'">'. basename($burl) . "</a><br />\n";
			
		}
		echo "<br />\n";
		flush();
	}
}
?>