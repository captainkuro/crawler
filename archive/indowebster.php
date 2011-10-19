<?php
require_once "crawler.php";
function crawl_indowebster($url) {
	//echo "'$url'";
	$craw = new Crawler($url);
	$craw->go2lineregexor('/(<\\/div><\\/a><\\/div><\\/div>)/', 1, 'href="#idws7"');
	$setring = $craw->getbetween('location.href=\'', '\'');
	$path = Crawler::extract($setring, 'path=', '&');
	$file_orig = Crawler::cutafter($setring, 'file_orig=');
	$craw->close();
	return '<a href="'.dirname($setring).'/'.$path.'">'.rawurldecode($file_orig).'</a>';
}
if ($_POST) {
	extract($_POST);
	$base = 'http://www.indowebster.com/';
	echo crawl_indowebster($url);
	//this is one time only:
	/*
	for ($i=1; $i<=9; $i++) {
		echo "page $i<br />\n";
		$link = 'http://www.indowebster.com/search.php?method=standard&pageId='.$i.'&cat=semua&keyword=jappydolls&type=3&sort=nosort';
		$c = new Crawler($link);
		$c->go2linewhere('"dono"');
		while (1) {
			if ($c->strpos('"dono"') !== false) {
				$ledak = explode('"dono"', $c->curline);
				for ($j=1; $j<count($ledak); $j++) {
					$href = Crawler::extract($ledak[$j], '<a href="', '"');
					$c2 = new Crawler($base.$href);
					$c2->go2linewhere('<div id="buttonz" align="center">');
					$x = htmlspecialchars($c2->curline);
					echo "line:'{$x}'<br />\n";
					$strpos = $c2->strpos('.html'); 
					if ($strpos !== false) {
						$lk = $c2->getbetween('<a href="', '"');
						$dirname = dirname($lk);
						$basename = basename($lk);
						echo crawl_indowebster($dirname.'/'.rawurlencode($basename))."<br />\n";
					} else {
						$lk = $c2->getbetween('<a href="');
						$lk = trim($lk);
						$dirname = dirname($lk);
						$basename = basename($lk);
						echo crawl_indowebster($dirname.'/'.rawurlencode($basename.'.html'))."<br />\n";
					}
					$c2->close();unset($c2);
					flush();
				}
			} else if ($c->strpos('text/javascript') !== false) {
				break;
			} else {//opo iki?
				break;
			}
			$c->go2lineor(array('"dono"', 'text/javascript'));
		}
		$c->close();
		unset($c);
	}
	*/
}
?>
<form method="post" action="">
	URL: <input type="text" name="url" value="<?=isset($url)?$url:''?>" />
	<input type="submit" value="Submit" />
</form>