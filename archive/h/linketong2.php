http://photo.blog.sina.com.cn/u/1264425925/page1
<html>
<body>
<?php
require_once "crawler.php";
$istart = 1;
$ifinish = 12;
$start = 'http://photo.blog.sina.com.cn/u/1264425925/page';
$fromC = 0;
extract($_GET);
extract($_POST);
$bigC = 0;
for ($i=$istart; $i<=$ifinish; $i++) {
	$bigC = $i*1000;
	$turl = $start . $i;
	echo $i, $turl, "<br />\n";
	flush();
	$c = new Crawler($turl);
	if ($c->stream) {
		$lines = $c->getalllineswhere('pt_border');
		$c->close();
		//echo '$lines:', htmlspecialchars(print_r($lines, true)), '<br />';
		flush();
		foreach ($lines as $line) {
			$bigC++;
			if ($bigC >= $fromC) {
				$link = Crawler::extract($line, '<a href="', '"');
				$imgurl = str_replace('photo.blog.sina.com.cn', 'static10.photo.sina.com.cn', $link);
				$imgurl = str_replace('/photo/', '/orignal/', $imgurl);
				
				echo "<a href='$imgurl'>".basename($imgurl)."</a><br/>\n";
			}
		}
	}
	flush();
}
?>
</body>
</html>