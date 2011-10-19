<html>
<body>
<?php
require_once "crawler.php";
$istart = 1;
$ifinish = 399;
$start = 'http://asianchicki.com/Girl.aspx?ID=';
extract($_GET);
extract($_POST);
for ($i=$istart; $i<=$ifinish; $i++) {
	$turl = $start . $i;
	$c = new Crawler($turl);
	if ($c->stream) {
		$c->go2linewhere('Thumbnail');
		$c->close();
		$nama = $c->getbetween('ctl00_ContentPlaceHolder1_lblName">', '</span');
		$ledak = explode('FileName="', $c->curline);
		//echo "<pre>{$c->curline}</pre><br />\n";
		$ccount = count($ledak);
		for ($j=1; $j<$ccount; $j++) {
			$iurl = Crawler::extract($ledak[$j], 'src="', '"');
			$iurl = str_replace('Thumbnail', 'Viewer', $iurl);
			$parsed = Crawler::parse_url($iurl);
			echo '<a href="'.$iurl.'">'.$nama.'</a>'."<br />\n";
		}
	}
	flush();
}
?>