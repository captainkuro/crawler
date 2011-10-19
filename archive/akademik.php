<?php
/*
function pred($c) {
	$ar = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	if (in_array($c, $ar)) {
		if ($c == 'A') {
			$c = 'Z';
		} else {
			for ($i=0; $i<count($ar); $i++) {
				if ($ar[$i] == $c) {
					$c = $ar[$i-1];
					break;
				}
			}
		}
	}
	return $c;
}
echo '<pre>';
$a = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
for ($p = 0; $p <= 24; $p++) {
	for ($x = 0; $x<strlen($a); $x++) {
		$c = $a[$x];
		for ($i=0; $i<$p; $i++) $c = pred($c);
		echo $c;
	}
	echo '<br />';
}
echo '</pre>';
*/
require "crawler.php";
$base_url = "http://akademik.itb.ac.id/publik/";
$start_suffix = "daftarkelas.php?ps=135&semester=1&tahun=2009&th_kur=2008";
$dir_path = "./Mahasiswa/";
//$palsu = "./daftarkelas.php.html";//debug

function crawl_one_page($url) {
	$nims = array();
	$kraw = new Crawler($url);
	$kraw->go2linewhere('------------------------------------------');
	$kraw->go2linewhere('------------------------------------------');
	$kraw->readline();
	while ($kraw->strpos('------------------------------------------') === false) {
		$nims[] = $kraw->getbetween(' ', '  ');
		$kraw->readline();
	}
	$kraw->close();
	return $nims;
}


//make sure the directory is exists
if (!is_dir($dir_path)) {
	mkdir($dir_path);
}

//start crawling
$craw = new Crawler($base_url.$start_suffix);
//$craw = new Crawler($palsu);//debug
/**
jadi strukturnya:
<ol>
	<li>Judul Matakuliah
		<ul>
			<li>Kelas X</li>
			...
			<li>Kelas Y</li>
		</ul>
		<br />
	<li>Judul Matakuliah 2
	....
</ol>
*/
$finish = false;
$subfinish = false;
$hasil = array();
$craw->go2linewhere('<ol>');
while (!$finish) {
	$craw->go2lineor(array('<li>', '</ol>'));
	if ($craw->strpos('</ol>') !== false) {
		$finish = true;
	} else {
		//ambil nama matkul
		$matkul = $craw->getbetween('<li>', ' ');
		echo "\n\nMatKul: $matkul\n";//debug
		$subfinish = false;
		$craw->go2linewhere('<ul>');
		while (!$subfinish) {
			$craw->go2lineor(array('<li>', '</ul>'));
			if ($craw->strpos('</ul>') !== false) {
				$subfinish = true;
			} else {
				$link = $craw->getbetween('<a href="', '"');
				$kelas = $craw->getbetween('" >', '</a>');
				echo "Kelas: $kelas\t";
				echo "Link: $link\n";
				//akses satu per satu halaman $base_url.$link
				$hasil[$matkul][$kelas] = $link;
			}
		}
	}
}
$craw->close();
//print_r($hasil);//debug

//INI BAGIAN PER 1 HALAMAN

foreach ($hasil as $matkul => $el) {
	$fil = fopen($dir_path.$matkul, 'w');
	foreach ($el as $kelas => $link) {
		//do something
		$gets = crawl_one_page($base_url.$link);
		//$gets = crawl_one_page("./IF2030-K1.html");//debug
		foreach ($gets as $get) {
			fwrite($fil, $get."\n");
		}
	}
	fclose($fil);
}
