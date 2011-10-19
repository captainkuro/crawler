<html>
<body>
<?php
require_once "crawler.php";
$istart = 1;
$ifinish = 2;
$start = 'http://blog.sina.com.cn/s/article_sort_1264425925_10001_';
$fromC = 0;
extract($_GET);
extract($_POST);
$bigC = 0;
for ($i=$istart; $i<=$ifinish; $i++) {
	$bigC = $i*1000;
	$turl = $start . $i . '.html';
	echo $i, $turl, "<br />\n";
	flush();
	$c = new Crawler($turl);
	if ($c->stream) {
		$lines = $c->getalllineswhere('>>');
		$c->close();
        unset($c);
		//echo '$lines:', htmlspecialchars(print_r($lines, true)), '<br />';
		flush();
		foreach ($lines as $line) {
			$bigC++;
			if ($bigC >= $fromC) {
				$link = Crawler::extract($line, 'href="', '"');
				echo 'Opening ', $bigC, ' ', $link, '<br />';flush();
				$c = new Crawler($link);
				if ($c->stream) {
                    $c->go2linewhere('time SG_txtc');
                    $time = $c->getbetween('>(', ')<');
					$blines = $c->getalllineswhere('/orignal/');
					$c->close();
                    unset($c);
					//echo '$blines:', htmlspecialchars(print_r($blines, true)),'<br />';
					flush();
					foreach ($blines as $bline) {
                        if (strpos($bline, 'url=') === false) {
                            $blink = Crawler::extract($bline, 'HREF="', '"');
                        } else if (strpos($bline, 'url=') !== false) {
                            $blink = Crawler::extract($bline, 'url=', '"');
                        }
						$blink = str_replace('&amp;690', '', $blink);
						echo '<a href="', $blink, '">', $time, "</a><br />\n";
						//echo '<a href="', $blink, '">', Crawler::n($bigC, 3), "</a><br />\n";
						flush();
					}
					flush();
				}
			}
		}
	}
	flush();
}
?>
</body>
</html>