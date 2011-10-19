<?php
function make_n($angka, $n) {
	while (strlen($angka) < $n) $angka = '0'.$angka;
	return $angka;
}
$ifrom = 633;
$ito = 659;
extract($_GET);

for ($i=$ifrom; $i<=$ito; $i++) {
	$fil = fopen('http://xkcd.com/'.$i, 'r');
	$reply = 0;
	while (!$fil && $reply++ < 10) {$fil = fopen('http://xkcd.com/'.$i, 'r');}
	
	while ($line = fgets($fil)) {
		if (strpos($line, 'Image URL (for hotlinking/embedding): ') !== false) {
			break;
		}
	}
	$str1 = substr($line, strpos($line, ': ')+2);
	$pos1 = strpos($str1, '<');
	$str1 = substr($str1, 0, $pos1);
	$filename = substr($str1, strrpos($str1, '/')+1);
	echo '<a href="',$str1, '">',make_n($i, 3),'_',$filename,'</a><br />',"\n";
	flush();
	fclose($fil);
}