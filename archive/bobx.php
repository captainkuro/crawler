<?php
require_once "crawler.php";

$base_url = 'http://www.bobx.com/';
extract($_POST);
// http://www.bobx.com/av-idol/akiho-yoshizawa/series-akiho-yoshizawa-0-10-10.html
?>
<html>
<body>
	<form action="" method="post">
		Start url: <input type="text" name="start_url" value="<?=isset($start_url)?$start_url:''?>" />
		<input type="submit" value="Submit" />
	</form>
<?php
if ($start_url) {
	// parse url
	preg_match('/(.*\D\-)(\d+)(\-.*)/', $start_url, $matches);
	$pra = $matches[1];
	$i = $matches[2];
	$pasca = $matches[3];
	$masih = false;
	do {
		$masih = false;
		$c = new Crawler($start_url, array(
			'use_curl' => true,
			'agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13',
		));
		
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, '<img src="/thumbnail/')) {
				$text = Crawler::extract($line, '<img src="', '"');
				$text = str_replace('/thumbnail', '', $text);
				$text = str_replace('-preview', '', $text);
				$file = basename($text);
				echo "<a href='{$base_url}{$text}'>{$file}</a><br />\n";flush();
				$masih = true; // Masih ada harapan next page
			} else if (Crawler::is_there($line, 'FULL NAVI:')) {
				break;
			}
		}
		
		$c->close();
		
		$i += 100;
		$start_url = $pra . $i. $pasca;
		//echo $start_url;
	} while ($masih);
}
/*
	for ($i=1; $i<=1; $i++) {
		$craw = new Crawler('./a'.$i.'.htm');
		//retrieve array of pages
		//echo the first image url
		while (!feof($craw->stream)) {
			$craw->go2linewhere('<img src="/thumbnail/idol');
			//$craw->go2linewhere('<img src="/thumbnail/av-idol');
			$text = $craw->getbetween('<img src="', '"');
			$text = str_replace('/thumbnail', '', $text);
			$text = str_replace('-preview', '', $text);
			$file = basename($text);
			echo "<a href='{$base_url}{$text}'>{$file}</a><br />\n";
			flush();
		}
		$craw->close();
	}
	*/
?>
</body>
</html>