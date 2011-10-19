<?php
/**
 * Crawler of http://disneycomics.free.fr/
 */
require_once 'crawler.php';

$base = 'http://disneycomics.free.fr/';
$tree = array(
	'Carl Barks' => 'index_barks_date.php',
	'Don Rosa' => 'index_rosa_date.php',
	'Marco Rota' => 'index_rota_date.php',
	'Romano Scarpa' => 'index_scarpa_date.php',
	'Tony Strobl' => 'index_strobl_date.php',
	'Al Taliaferro' => 'index_taliaferro.php',
	'Vicar' => 'index_vicar_date.php',
	'William Van Horn' => 'index_vanhorn_date.php',
	'Paul Murry' => 'index_murry_date.php',
	'Daily Strips' => 'index_dailies.php',
	'Sunday Strips' => 'index_sunday.php',
);

function download_it($img_url, $output_file) {
	$dir = dirname($output_file) . '\\';
	//exec("mkdir \"$dir\"");
	exec("wget -t 0 --retry-connrefused -O \"$output_file\" $img_url");
}

$mode = 'phase3b';
switch ($mode) {
	case 'beginning':
		$result = array();
		foreach ($tree as $name => $link) {
			$c = new Crawler($base . $link);
			$c->go_to('<tbody>');
			while ($line = $c->readline()) {
				if (Crawler::is_there($line, '<tr>')) {
					$line = $c->readline();	// nomor urut
					if (!Crawler::is_there($line, '<a href')) {
						$line = $c->readline();	// Hero
						if (!Crawler::is_there($line, '<a href')) {
							$line = $c->readline();	// Title dan link
						}
					}
					// ada yg berupa original/reprint, ...
					if (preg_match('/class="red">(.*)<\\/h4>.*class="blue" href="([^"]*)">original<.*href="([^"]*)">reprint</', $line, $matches)) {
						$result[$name][strip_tags($matches[1]).'-original'] = html_entity_decode($matches[2]);
						$result[$name][strip_tags($matches[1]).'-reprint'] = html_entity_decode($matches[3]);
					}
					// ... dan ada yg title langsung link, ...
					else if (preg_match('/href="([^"]*)">(.*)<\\/a>/', $line, $matches)) {
						$result[$name][strip_tags($matches[2])] = html_entity_decode($matches[1]);
					} 
				} else if (Crawler::is_there($line, '</tbody>')) {
					break;
				}
			}
			$c->close();
		}
		ob_start();
		echo "<?php\n";
		echo '$a = ';
		var_export($result);
		echo ';';
		file_put_contents('disneycomics.phase1', ob_get_clean());
		break;
	case 'phase2':
		require 'disneycomics.phase1';
		//print_r(array_keys($a));exit;
		$hasil = array();
		foreach ($a as $name => $comics) {
			foreach ($comics as $title => $url) {
				$url = preg_replace('/show.*\\.php.*loc=/', '', $url);
				/*
				$url = str_replace('show.php?s=date&loc=', '', $url);
				$url = str_replace('show.php?loc=', '', $url);
				$url = str_replace('show2.php?loc=', '', $url);
				*/
				echo $url."\n";flush();
				$text = file_get_contents($base . $url . '/');
				$raws = Crawler::extract_to_array($text, '<A HREF="', '"');
				$n = count($raws);
				for ($i = 5; $i < $n; $i++) {
					$raws[$i] = html_entity_decode($raws[$i]);
					$hasil[$name][$title][$raws[$i]] = $base . $url . '/' . $raws[$i];
					//echo $base . $url . '/' . $raws[$i] . "\n";flush();
				}
				//$hasil[$name][$title] = $raws;
			}
		}
		ob_start();
		echo "<?php\n\$a = ";
		var_export($hasil);
		echo ';';
		file_put_contents('disneycomics.phase2', ob_get_clean());
		break;
	case 'phase3':
		require 'disneycomics.phase2';
		foreach ($a as $name => $comics) {
			foreach ($comics as $title => $pages) {
				echo "$name\\$title\n";flush();
				exec("mkdir \"$name\\$title\\\"");
				foreach ($pages as $file => $img) {
					$file = html_entity_decode($file);	// quickfix
					$img = html_entity_decode($img);
					$output_file = "$name\\$title\\$file";
					//echo "<a href='$img'>$name/$title/$file</a><br/>\n";flush();
					download_it($img, $output_file);
					/*
					exec("wget -t 0 --retry-connrefused -O \"$name\\$title\\$file\" $img");
					*/
				}
			}
		}
		break;
	case 'phase3b':	// phase 3 pake concurrent download curl
		require 'disneycomics.phase2';
		foreach ($a as $name => $comics) {
			// cUrl init
			$n = 0;
			$size = 2;// mau berapa concurrent download at a time
			$curlHandle = null;
			$curlList = array();
			foreach ($comics as $title => $pages) {
				echo "$name\\$title";flush();
				$dirname = "$name\\$title";
				exec("mkdir \"$dirname\"");
				$i = 0;
				foreach ($pages as $file => $img) {
					echo '.'.++$i.'.';flush();
					$file = html_entity_decode($file);	// quickfix
					$img = html_entity_decode($img);
					$output = "$dirname\\$file";
					// cUrl
					if ($n == 0) {
						$curlHandle = curl_multi_init();
					}
					$curlList[$output] = Crawler::addHandle($curlHandle, $img);
					$n++;
					if ($n >= $size) {
						Crawler::execHandle($curlHandle);
						foreach ($curlList as $outfile => $curlEl) {
						//	exec("mkdir \"".dirname($outfile)."\\\"");
							file_put_contents($outfile, curl_multi_getcontent($curlEl));
							curl_multi_remove_handle($curlHandle, $curlEl);
						}
						curl_multi_close($curlHandle);
						$n = 0;
						$curlList = array();
					}
				}
				echo "\n";flush();
			}
			// the last call
			if ($curlList) {
				Crawler::execHandle($curlHandle);
				foreach ($curlList as $outfile => $curlEl) {
				//	exec("mkdir \"".dirname($outfile)."\\\"");
					file_put_contents($outfile, curl_multi_getcontent($curlEl));
					curl_multi_remove_handle($curlHandle, $curlEl);
				}
				curl_multi_close($curlHandle);
			}
		}
		break;
	case 'phase4':
	// folder yg -original$ dan -reprint$ digabung jdai satu
	// filenamenya diprefix ^original- dan ^reprint-
		$dirname = 'Carl Barks';
		$subdirs = scandir($dirname);
		$n = count($subdirs);
		$x = 0;
		for ($i=0; $i<$n; ++$i) {
			// jika berakhiran -original$, 
			// asumsi terurut alfabet menurun jadi -original dulu baru persis di bawahnya -reprint
			if (preg_match('/\\-original$/', $subdirs[$i])
				&& preg_match('/\\-reprint/', $subdirs[$i+1])) {	
				$orig = $subdirs[$i];
				$repr = $subdirs[$i+1];
				$cleanname = Crawler::cutuntil($orig, '-original');
				exec("mkdir \"$dirname\\$cleanname\"");
				echo $cleanname."\n";//debug
				// baca isi original dan move ke tempat dan nama baru
				$origfiles = scandir($dirname . '\\' . $orig);
				foreach ($origfiles as $filename) { if ($filename != '.' && $filename != '..') {
					$from = $dirname . '\\' . $orig . '\\' . $filename;
					$to = $dirname . '\\' . $cleanname . '\\original-' . $filename;
					exec("move \"$from\" \"$to\"");
					echo "From $from; To $to; \n";
				}}
				exec("rmdir \"$dirname\\$orig\"");
				$reprfiles = scandir($dirname . '\\' . $repr);
				foreach ($reprfiles as $filename) { if ($filename != '.' && $filename != '..') {
					$from = $dirname . '\\' . $repr . '\\' . $filename;
					$to = $dirname . '\\' . $cleanname . '\\reprint-' . $filename;
					exec("move \"$from\" \"$to\"");
					echo "From $from; To $to; \n";
				}}
				exec("rmdir \"$dirname\\$repr\"");
				$x++;
				//if ($x > 3) exit;
			}
		}
		break;
}