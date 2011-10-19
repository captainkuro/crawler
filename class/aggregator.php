<?php
require_once '../crawler.php';
require_once 'spider_fakku.php';
require_once 'spider_luscious.php';
require_once 'spider_hfhgallery1.php';
require_once 'spider_hfhgallery2.php';
require_once 'spider_hfhgallery3.php';

// read the "database" file and crawl links, produce separate files
function stage_extract() {
	$filepath = 'D:\temp\torrent\splitter.ini';
	$f = fopen($filepath, 'r');
	$name = '';
	$number = 1;
	while (!feof($f)) {
		$line = fgets($f);
		echo ": $line";flush();
		if (preg_match('/^[^\\/]+$/', $line)) {
			$name = trim($line);
			$number = 1;
		} else if (preg_match('/fakku\\.net/', $line)) {
			ob_start();
			$berhasil = true;
			do {
				try {
					$s = new Spider_Fakku(trim($line));
					$s->go();
					$berhasil = true;
				} catch (Exception $e) {
					$berhasil = false;
				}
			} while (!$berhasil);
			$result = ob_get_clean();
			// file_put_contents($name.'-'.$number++.'.html', $result);
			file_put_contents($name.'.html', $result, FILE_APPEND);
		} else if (preg_match('/lu\\.scio\\.us/', $line)) {
			ob_start();
			$berhasil = true;
			do {
				try {
					$s = new Spider_Luscious(trim($line));
					$s->go();
					$berhasil = true;
				} catch (Exception $e) {
					$berhasil = false;
				}
			} while (!$berhasil);
			$result = ob_get_clean();
			// file_put_contents($name.'-'.$number++.'.html', $result);
			file_put_contents($name.'.html', $result, FILE_APPEND);
		} else if (preg_match('/gallery\\.hentaifromhell\\.net.*hfhgallery/', $line)) {
			ob_start();
			$berhasil = true;
			do {
				try {
					$s = new Spider_Hfhgallery1(trim($line));
					$s->go();
					$berhasil = true;
				} catch (Exception $e) {
					$berhasil = false;
				}
			} while (!$berhasil);
			$result = ob_get_clean();
			// file_put_contents($name.'-'.$number++.'.html', $result);
			file_put_contents($name.'.html', $result, FILE_APPEND);
		} else if (preg_match('/gallery\\.hentaifromhell\\.net.*level=album/', $line)) {
			ob_start();
			$berhasil = true;
			do {
				try {
					$s = new Spider_Hfhgallery2(trim($line));
					$s->go();
					$berhasil = true;
				} catch (Exception $e) {
					$berhasil = false;
				}
			} while (!$berhasil);
			$result = ob_get_clean();
			// file_put_contents($name.'-'.$number++.'.html', $result);
			file_put_contents($name.'.html', $result, FILE_APPEND);
		/*
		} else if (preg_match('/hentaifromhell\\.net.*\\.htm/', $line)) {
			ob_start();
			$berhasil = true;
			do {
				try {
					$s = new Spider_Hfhgallery3(trim($line));
					$s->go();
					$berhasil = true;
				} catch (Exception $e) {
					$berhasil = false;
				}
			} while (!$berhasil);
			$result = ob_get_clean();
			file_put_contents($name.'-'.$number++.'.html', $result);
		*/
		}
	}
	fclose($f);
}

// read many files produced by stage_extract() and combine to produce less files, 1 per category
// looks like it's not needed anymore
function stage_combine() {
	$dir = new DirectoryIterator(dirname(__FILE__));
	foreach ($dir as $file) if (!$file->isDot() && !$file->isDir()) {
		$name = $file->getFilename();
		if (preg_match('/^(.+)\-\d+\.html$/', $name, $m)) {
			$outname = 'zz' . $m[1] . '.htm';
			file_put_contents($outname, file_get_contents($name) . "<br />\n<br />\n", FILE_APPEND);
		}
	}
}

stage_extract();