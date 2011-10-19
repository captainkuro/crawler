<?php
require_once "crawler.php";
extract($_POST);
function crawl_1_page($url) {
	echo "URL2 $url <br/>\n";flush();
	$dirname = html_entity_decode(Crawler::cutfromlast1(substr($url, 0, strlen($url)-1), '/'));
	$hasil = array();
	$c = new Crawler($url);
	$c->go_to('<div class="entry">');
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, "href='")) {
			$img = Crawler::extract($line, "href='", "'");
			echo "<a href='$img'>$dirname</a><br/>\n"; flush();
		} else if (Crawler::is_there($line, 'href="')) {
			$img = Crawler::extract($line, 'href="', '"');
			echo "<a href='$img'>$dirname</a><br/>\n"; flush();
		} else if (Crawler::is_there($line, '</div>')) {
			break;
		}
	}
	$c->close();
}
?>
<html>
<body>
	<form action="" method="post">
		Starting URL: <input type="text" name="start_url" value="<?php echo isset($start_url)?$start_url:''?>" />
		<input type="submit" value="Submit" />
	</form>
<?php
flush();
// http://reallycuteasians.com/tag/korean/
// http://koreaftw.com/category/korean/
if ($start_url) {
	$hasil = array();

	echo "URL $start_url <br/>\n";flush();
	$c = new Crawler($start_url);
	$c->go_to('<table');
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, '<h2 class="title">')) {
			$hasil[] = Crawler::extract($line, '<a href="', '"');
		} else if (Crawler::is_there($line, '<div class="wp-pagenavi">')) {
			break;
		}
	}
	$line = $c->readline();
	preg_match('/Page \\d+ of (\\d+)/', $line, $matches);
	$n = $matches[1];
	$c->close();
	for ($i=2; $i<=$n; $i++) {
		$ppp = $start_url . 'page/' . $i;
		echo "URL $ppp <br/>\n";flush();
		$c = new Crawler($ppp);
		$c->go_to('<table');
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, '<h2 class="title">')) {
				$hasil[] = Crawler::extract($line, '<a href="', '"');
			} else if (Crawler::is_there($line, '<div class="wp-pagenavi">')) {
				break;
			}
		}
		$c->close();
	}
	foreach ($hasil as $uri) {
		crawl_1_page($uri);
	}
}
?>
</body>
</html>