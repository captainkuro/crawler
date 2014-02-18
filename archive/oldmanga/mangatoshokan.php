<?php
require_once "crawler.php";
extract($_POST);
?>
<html>
<body>
	<form action="" method="post">
		Starting URL: <input type="text" name="start_urlx" value="<?=isset($start_urlx)?$start_urlx:''?>" /><br />
		From: <input type="text" name="cfrom" value="<?=isset($cfrom)?$cfrom:''?>" /> 
		To: <input type="text" name="cto" value="<?=isset($cto)?$cto:''?>" /><br />
		Prefix: <input type="text" name="prefix" value="<?=isset($prefix)?$prefix:''?>" /><br />
		<input type="submit" value="Crawl"/>
	</form>
<?php
$bas = 'http://www.mangatoshokan.com';
function crawl_1_page($start_url) {
	global $prefix;
	global $bas;
	$cr = new Crawler($start_url);
	/*
		echo $cr->readline();flush();
	while(!feof($cr->stream)) {
	}
	exit(0);
	*/
	$cr->go2linewhere('headerSelect');
	$cr->readline();
	$line = $cr->curline;
	$cr->close();
	$chap = Crawler::cutfromlast1($start_url, '/');
	if (strpos($chap, '.') === false) {
		$chap = Crawler::n($chap, 3);
	} else {
		$a = explode('.', $chap);
		$a[0] = Crawler::n($a[0], 3);
		$chap = implode('.', $a);
	}
	$pi = 1;	// page i
	$ledak = explode('value="', $line);
	$pages = array();
	for ($i = 1; $i<count($ledak); $i++) {	
		$uurl = Crawler::cutuntil($ledak[$i], '"');
		$key = Crawler::cutfromlast1($uurl, '/');
		$pages[$key] = (strpos($uurl, 'http://') === 0) ? $uurl : $bas.$uurl;
	}
	//print_r($pages);flush();
	$results = array();
	foreach ($pages as $pagenum => $new_url) {
		$berhasil = false;
		while (!$berhasil) {
			$cr = new Crawler($new_url);
			//echo "URL:$new_url<br/>\n";flush();
			$cr->go2linewhere('id="readerPage"');
			if ($cr->strpos('<img src="') === false) {
				$cr->readline();
			}
			$line = $cr->curline;
			$cr->close();
			
			$img_url = Crawler::extract($line, '<img src="', '"');
			//echo "IMG:$img_url<br/>\n";flush();
			$real_name = basename($img_url);
			$ext = Crawler::cutfromlast($img_url, '.');
			//$filename = $prefix . '-' . $chap . '-' . Crawler::n($pagenum, 2) . $ext;
			$filename = $prefix . '-' . $chap . '-' . urldecode($real_name);
			$val = $img_url;
			if (!empty($val)) {
				$berhasil = true;
				$key = $filename;
				$results[$filename] = $img_url;
				?>
				<a href="<?=$val?>"><?=$key?></a><br />
				<?
				flush();
			} else {
			}
		}
	}
}

if (isset($start_urlx)) {
	if (strlen($cfrom) && strlen($cto)) {
		$start_urlx = Crawler::cutuntillast($start_urlx, '/');
		for ($i=$cfrom; $i<=$cto; ++$i) {
			crawl_1_page($start_urlx . '/' . $i);
		}
	} else {
		crawl_1_page($start_urlx);
	}
}
?>
</body>
</html>