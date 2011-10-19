<?php
require 'crawler.php';

$url = 'http://mangastream.com/read/billy_bat/18964888/1'; // The page url
$dir = 'd:/temp/'; // Where to store
$filename = 'test'; // Rename to this

// 1 Buka satu halaman manga reader
$c = new Crawler($url);
$imgs = array();
// 2 Pergi ke baris yang berisi definisi CSS salah satu potongan 
$c->go_to('/#.+position.+width.+height.+top.+left/', '', true);
// 3 Iterasi hingga ketemu baris penutup (berisi '-->')
while ($line = $c->readline()) {
	if (preg_match('/#(\\w+) .+width:(\\d+).*height:(\\d+).*top:(\\d+).*left:(\\d+)/', $line, $match)) {
		// 3a Ambil informasi id, z-index, height, width, left, top tiap potongan 
		list($all, $id, $width, $height, $top, $left) = $match;
		if (preg_match('/z-index:(\\d+)/', $line, $match)) $zindex = $match[1]; else $zindex = 0;
		// 3b Masukkan ke array (var $imgs)
		$imgs[$id] = array('id'=>$id, 'zindex'=>$zindex, 'width'=>$width, 'height'=>$height, 'top'=>$top, 'left'=>$left);
	} else if (Crawler::is_there($line, '-->')) {
		break;
	}
}
// 4 Pergi ke baris yang berisi ukuran total gambar 
$reg = '/<div .+position:relative.+width:(\\d+).+height:(\\d+)/';
$c->go_to($reg, '', true);
preg_match($reg, $c->curline, $match);
// 5 Ambil $tot_width dan $tot_height dari baris ini 
list($all, $tot_width, $tot_height) = $match;
// 6 Iterasi hingga ketemu baris penutup (regex '/^\\s+<\\/div>/')
while ($line = $c->readline()) {
	if (preg_match('/<div id="([^"]+)".+src="([^"]+)"/', $line, $match)) {
		// 6a Ambil informasi id, src tiap potongan 
		list($all, $id, $src) = $match;
		// 6b Gabungkan ke array tadi (var $imgs)
		$imgs[$id]['src'] = $src;
		$imgs[$id]['filename'] = basename($src);
		$imgs[$id]['ext'] = strtolower(Crawler::cutfromlast1(basename($src), '.'));
	} else if (preg_match('/^\\s+<\\/div>/', $line)) {
		break;
	}
}
// 7 Setelah seluruh informasi potongan didapat, urutkan ascending berdasarkan z-index 
function the_comp($a, $b) {
	if ($a['zindex'] == $b['zindex']) return 0;
	return ((int)$a['zindex'] < (int)$b['zindex']) ? -1 : 1;
}
usort($imgs, 'the_comp');
// 8 Create canvas berukuran $tot_iwdth x $tot_height
$canvas = imagecreatetruecolor($tot_width, $tot_height);
// 9 Iterasi array berisi informasi tadi
foreach ($imgs as $img) {
	// 9a Download potongan
	file_put_contents($dir . $img['filename'], file_get_contents($img['src']));
	// 9b Convert jadi canvas
	if ($img['ext'] == 'png') {
		$tmpimg = imagecreatefrompng($dir . $img['filename']);
	} else { 
		$tmpimg = imagecreatefromjpeg($dir . $img['filename']);
	}
	// 9c Tempelkan ke canvas utama 
	imagecopy($canvas, $tmpimg, 
		$img['left'], $img['top'], 	// koordinat di canvas
		0, 0,  // koordinat ambil dari potongan
		$img['width'], $img['height'] // besar potongan yg di-copy
	);
	// 9d Hapus potongan
	unlink($dir . $img['filename']);
}
// 10 Export menjadi jpg/png
$ext = $imgs[count($imgs)-1]['ext'];
$fullpath = "$dir$filename.$ext";
if ($ext == 'jpg') {
	imagejpeg($canvas, $fullpath, 90);
} else {
	imagepng($canvas, $fullpath, 9, PNG_NO_FILTER);
}
