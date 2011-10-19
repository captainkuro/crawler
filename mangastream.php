<?php
/**
 * Spider for mangastream.com
 *
 * Requirements:
 * - PHP > 5.1.6
 * - PHP GD extension (check your phpinfo())
 */
require 'crawler.php';
extract($_POST);
?>
<html>
<body>
<form method="POST" action="">
	URL FOLDER: <input type="text" name="base" value="<?php echo @$base;?>"><br />
	Prefix: <input type="text" name="prefix" value="<?php echo @$prefix;?>"><br />
	Infix: <input type="text" name="infix" value="<?php echo @$infix;?>"> optional<br />
	<input type="submit">
</form>
<?
//http://mangastream.com/read/kekkaishi/33956873/1
$base = @$_POST['base'];
$prefix = @$_POST['prefix'];
$sitename = "http://mangastream.com";
$dir = 'd:/temp/manga/'; // CHANGEME
$MODE = isset($_GET['mode']) ? $_GET['mode'] : 3; 
/*
	mode 1 itu jadul pisan, 
	mode 2 itu yg gambar dipotong2, 
	mode 3 itu 1 page 1 image, mulai 12 juli 2011
*/

if ($base) {
	if ($MODE == 3) {
		$P = new Page($base);
		// RETRIEVE CHAPTER NAME
		$P->go_line('id="top"');
		$P->go_line('<h3>');
		if (!$infix) {
			$chapter = $P->next_line()->dup()
				->cut_between('<strong>', '</s')->to_s();
		} else {
			$chapter = $infix;
		}
		// RETRIEVE PAGES
		$pages = array();
		$P->go_line('id="controls"');
		do {if ($P->curr_line()->regex_match('/>\d+</')) {
			$pages[] = $P->curr_line()->dup()->cut_between('href="', '"');
		}} while(!$P->next_line()->contain('class="spacer"'));
		
		// CYCLE THROUGH PAGES
		$chatext = Crawler::n($chapter, 3);
		$i = 0;
		foreach ($pages as $page) { $i++;
			$Q = new Page($sitename . $page);
			// echo $base.$page.'<br />'; // debug
			$Q->go_line('id="p"');
			// $Q->next_line();
			$img = $Q->curr_line()->dup()
				->cut_between('src="', '"')->to_s();
			$text = Crawler::n($i, 3);
			$ext = Crawler::cutafter(basename($img), '.');
			echo "<a href='$img'>$prefix-$chatext-$text.$ext</a><br />\n";
		}
		exit;
	}
	// else, $MODE 2/1
	
    $c = new Crawler($base);
	// echo $c->curline;
	// RETRIEVE CHAPTER NAME
	$c->go2linewhere('selected="selected"');
	if (!$infix) {
		$chapter = Crawler::extract($c->curline, 'selected="selected">', '<');
	} else {
		$chapter = $infix;
	}
	// Retrieving pages
    $pages = array();
	$c->go2linewhere('<select onchange="window.open(');
	while ($line = $c->readline()) {
		//print_r($line);
		if (strpos($line, 'value="') !== false) {
			$pages[] = Crawler::extract($line, 'value="', '"');
		} else if (strpos($line, '</select>') !== false) {
			break;
		}
	}
    $c->close();
    
	function the_comp($a, $b) {
		if ($a['zindex'] == $b['zindex']) return 0;
		return ((int)$a['zindex'] < (int)$b['zindex']) ? -1 : 1;
	}
	
	$j = 0;
    foreach ($pages as $page) { $j++; // if (!in_array($j, array(20))) continue; // uncomment if only for several page
		$success = false;
		$i = 0;
		$chatext = Crawler::n($chapter, 3);
		while (!$success) {
			echo $sitename.$page . "<br />\n";flush();
			$c = new Crawler($sitename.$page);
			if ($MODE == 1) { // MODE 1 BEGIN
				// Metode lama
				$c->go2linewhere('id="p"');
				//$c->readline();
				$img = Crawler::extract($c->curline, 'src="', '"');
				if (trim($img)) {
					echo '<a href="'.$img.'">'.$prefix.'-'.Crawler::n($chapter, 3).'-'.basename($img).'</a><br/>'."\n";
					flush();
					$success = true;
				} else {
					echo $i++;
				}
				continue;
			} // MODE 1 END
			else if ($MODE == 2) { // MODE 2 BEGIN
				// Metode baru: sejak Claymore 113 keluar
				$imgs = array();
				// Sekarang pake split, jadi pertama ambil data dari headstyle
				// #p1f38d696250f92315cf517345ca297b2 {position:absolute;width:570px;height:392px;top:0px;left:337px}
				// UPDATE: sejak Bleach 440 berubah lagi, pake inline CSS di <div> langsung
				$reg1 = '/#.+position.+width.+height.+top.+left/';
				$reg2 = '/<div .+position:relative.+width:(\\d+).+height:(\\d+)/';
				$c->go_to(array($reg1, $reg2), 'OR', true);
				$penentu = $c->curline;
				//echo $penentu;exit;
				if (preg_match($reg1, $penentu)) {
					while ($line = $c->readline()) {
						if (preg_match('/#(\\w+) .+width:(\\d+).*height:(\\d+).*top:(\\d+).*left:(\\d+)/', $line, $match)) {
							list($all, $id, $width, $height, $top, $left) = $match;
							if (preg_match('/z-index:(\\d+)/', $line, $match)) $zindex = $match[1]; else $zindex = 0;
							$imgs[$id] = array('id'=>$id, 'zindex'=>$zindex, 'width'=>$width, 'height'=>$height, 'top'=>$top, 'left'=>$left);
						} else if (Crawler::is_there($line, '-->')) {
							break;
						}
					}
					$c->go_to($reg2, '', true);
				}
				
				// Sekarang ambil total width dan height
				// <div style="position:relative;width:907px;height:1300px">
				preg_match($reg2, $c->curline, $match);
				list($all, $tot_width, $tot_height) = $match;
				// Ambil satu2 bagian2 gambar
				// <div id="p1f38d696250f92315cf517345ca297b2"><a href="/read/hajime_no_ippo/82193083/2"><img src="http://img.mangastream.com/m/25/82193083/6e009531b7afe043b6f9be330067bf5e.png" border="0" /></a></div>
				while ($line = $c->readline()) {
					if (preg_match('/<div id="([^"]+)".+src="([^"]+)"/', $line, $match)) {
						list($all, $id, $src) = $match;
						$imgs[$id]['src'] = $src;
						$imgs[$id]['filename'] = basename($src);
						$imgs[$id]['ext'] = strtolower(Crawler::cutafter(basename($src), '.'));
					} else if (preg_match('/<div.+z-index:(\\d+).+width:(\\d+).*height:(\\d+).*top:(\\d+).*left:(\\d+).+img src="([^"]+)"/', $line, $match)) {
						list($all, $zindex, $width, $height, $top, $left, $src) = $match;
						
						$imgs[] = array('zindex'=>$zindex, 'width'=>$width, 'height'=>$height, 
							'top'=>$top, 'left'=>$left, 'src'=>$src,
							'filename'=>basename($src), 'ext'=>strtolower(Crawler::cutafter(basename($src), '.')));
					} else if (preg_match('/^\\s+<\\/div>/', $line)) {
						break;
					}
				}
				// Sort by z-index
				usort($imgs, 'the_comp');
				//print_r($imgs);exit;//debug
				// Setelah semua data yg diperlukan tercapai, satukan jadi 1 gambar
				
				// Create canvas sebesar $tot_width x $tot_height
				$canvas = imagecreatetruecolor($tot_width, $tot_height);
				foreach ($imgs as $img) {
					// Download semua gambar
					do {
						$repeat = false;
						$imgcontent = file_get_contents($img['src']);
						while (!$imgcontent) $imgcontent = file_get_contents($img['src']);
						file_put_contents($dir . $img['filename'], $imgcontent);
						if ($img['ext'] == 'png') {
							$tmpimg = imagecreatefrompng($dir . $img['filename']);
						} else { 
							$tmpimg = imagecreatefromjpeg($dir . $img['filename']);
						}
						if ($tmpimg === false) $repeat = true;
					} while ($repeat);
					// Copy tiap gambar ke canvas besar
					imagecopy($canvas, $tmpimg, 
						$img['left'], $img['top'], 	// koordinat di canvas
						0, 0,  // koordinat ambil dari potongan
						$img['width'], $img['height'] // besar potongan yg di-copy
					);
					// Hapus gambar2 
					unlink($dir . $img['filename']);
				}
				// Export jadi png/jpg
				$itext = Crawler::n($j, 3);
				$ext = $imgs[count($imgs)-1]['ext'];
				$fulltext = "$dir$prefix-$chatext-$itext.$ext";
				if ($ext == 'jpg') {
					imagejpeg($canvas, $fulltext, 90);
				} else {
					imagepng($canvas, $fulltext, 9, PNG_NO_FILTER); // optional: set quality/compression level 0 - 9
				}
				$success = true;
				echo $fulltext . "<br />\n";
				//print_r($imgs);exit;//debug
				
			} // MODE 2 END
			$c->close();
		}
    }
}
