<?php
require_once "crawler.php";//class Crawler
//format: 1(1-4)+2(5-6)
//atau: (10-20)

function pecahkan_format($formt) {
	$ar1 = explode('+', $formt);
	$ar2 = array();
	foreach ($ar1 as $one) {
		$pos1 = strpos($one, '(');
		$vol = substr($one, 0, $pos1);
		$chap_range = substr($one, $pos1+1, strrpos($one, ')')-$pos1-1);
		//list($start_chap, $end_chap) = explode('-', $chap_range);
		$ar2[$vol] = explode('-', $chap_range);
	}
	return $ar2;
}

//make N digits
function n($num, $l) {
	while (strlen($num) < $l) {
		$num = '0'.$num;
	}
	return $num;
}

if ($_POST) {
	extract($_POST);
	//$prefix = 'Tokyo_Akazukin';//hahaha
	//pecahkan format
	$queue = pecahkan_format($format);
	foreach ($queue as $vol => $el) {
		$volt = n($vol, 2);
		list($startc, $endc) = $el;
		for ($i=$startc; $i<=$endc; $i++) {
			$chapt = n($i, 3);
			if (is_int($vol)) {
				$target = $start_url.'v'.$volt.'/c'.$chapt.'/';
			} else {
				$target = $start_url.'c'.$chapt.'/';
			}
			//echo $start_url.$volt.'/'.$chapt."\n";
			$craw = new Crawler($target);
			//get whole page
			$craw->go2linewhere("  of ");
			$jumlah = $craw->getbetween('  of ', ' ');
			//echo $target, ': ', $jumlah, "\n";
			$craw->close();
			unset($craw);
			for ($page=1; $page<=$jumlah; $page++) {
				$ptarget = $target.$page.'.html';
				$craw = new Crawler($ptarget);
				$craw->go2linewhere('onclick="return enlarge();"');
				$img_url = $craw->getbetween('<img src="', '"');
				$craw->close();
				unset($craw);
				$ext = substr($img_url, strrpos($img_url, '.'));
				$paget = n($page, 3);
				echo "<a href='{$img_url}'>{$prefix}-{$chapt}-{$paget}{$ext}</a><br />\n";
				flush();
			}
		}
	}
}
?>
<html>
<body>
	<form action="" method="post">
		Starting URL: <input type="text" name="start_url" value="<?=isset($start_url)?$start_url:''?>" /><br />
		Format: <input type="text" name="format" value="<?=isset($format)?$format:''?>">1(1-4)+2(5-6)<br />
		Prefix: <input type="text" name="prefix" value="<?=isset($prefix)?$prefix:''?>" /><br />
		<input type="submit" value="Crawl"/>
	</form>
</body>
</html>