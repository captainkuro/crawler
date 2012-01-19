<?php
require_once 'crawler.php';
extract($_POST);
?>
<html>
<body>
<form method="POST" action="">
	URL : <input type="text" name="base" value="<?=isset($base)?$base:''?>"><br />
	<br/>misal: http://g.e-hentai.org/g/168833/93bcebfaaf/<br />
	<input type="submit">
</form>
<?
// http://g.e-hentai.org/g/205508/900f2d2c1a/
// http://g.e-hentai.org/g/168833/93bcebfaaf/
// http://g.e-hentai.org/s/2d13e252e5/168833-1
// http://g.e-hentai.org/codegen.php?gid=205508&t=900f2d2c1a&s=1-m-y&type=html

$sitename = "http://g.e-hentai.org";
$cookie = "__utma=185428086.1551282410.1291405578.1291422608.1291427459.4; __utmz=185428086.1291405578.1.1.utmcsr=google|utmccn=(organic)|utmcmd=organic|utmctr=sofia%20webber%20wiki; ipb_member_id=479443; ipb_pass_hash=af8cb1500880244286676a513f1a1ba4; uconfig=tl_m-uh_y-cats_0-ts_m-tr_1-prn_y-dm_l-rx_0-ry_0-sa_y-oi_n-qb_n-tf_n-hp_-hk_; lv=1291414385-1291422236; tips=1; __utmb=185428086.3.10.1291427459; __utmc=185428086; ipb_session_id=770adfca602081684d15ce9b0528d9b9";
if ($base) {
	// parse $base
	preg_match('/\\/g\\/([^\\/]+)\\/([^\\/]+)/', $base, $matches);
	$gid = $matches[1];
	$t = $matches[2];
	$codegen = "http://g.e-hentai.org/codegen.php?gid=$gid&t=$t&s=1-m-y&type=html";
	$c = new Crawler($codegen, array('use_curl' => true));
	echo $codegen."<br/>";
	$c->go_to('class="ehggt"');
	$pages = array();
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, '"ehga"')) {
			$pages[] = Crawler::extract($line, 'href="', '"');
		} else if (Crawler::is_there($line, '</table>')) {
			break;
		}
	}
	$c->close();
	
	foreach ($pages as $url) {
		echo "URL:$url<br/>\n";
		$c = new Crawler($url, array(
			'use_curl' => true,
			//'cookie' => $cookie,
		));
		$c->go_to('</span>');
		// ambil image source
		$raws = Crawler::extract_to_array($c->curline, 'src="', '"');
		echo '<pre>';print_r($raws);echo '</pre>';
		// gambar image biasanya berada di $raws[4] atau $raws[5]
		if (Crawler::is_there($raws[0], '/n/next.png')) array_shift($raws);
		// gambar image namanya lebih panjang
		$base1 = basename($raws[4]);
		$base2 = basename($raws[5]);
		if (strlen($base1) > strlen($base2)) {
			$img = $raws[4];
		} else {
			$img = $raws[5];
		}
		$fname = basename($img);
		echo "<a href='$img'>$fname</a><br/>\n";
		// Download original
		if (preg_match('/href="([^"]+)">Download original/', $c->curline, $matches)) {
			$img = $matches[1];
			$fname = basename($img);
			echo "<a href='$img'>$fname</a><br/>\n";
		}
		$c->close();
		sleep(5);
	}
}
?>

</body>
</html>