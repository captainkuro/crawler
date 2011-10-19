<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" /> 
	<title>Scraper in Work</title>
</head>
<body>
<?php
require_once 'crawler.php';

$phase = 'printout';
//$extract_url = 'http://www.itcute.com/wp-content/uploads/';
/*
http://www.nvhentai.com//wp-content/plugins/lazyest-gallery/albums/doujinshi/
http://www.nvdoujins.com/wp-content/gallery/
http://www.maihentai.com/wp-content/lg-gallery/Doujinshi/
*/
$extract_url = 'http://www.asianfav.com/wp-content/uploads/';

switch ($phase) {
	case 'extraction':
		echo "<?php\n/*";
		$h = Crawler::explore($extract_url, '<a href="', '"', 1);
		echo "*/\n\$a = ";
		var_export($h);
		echo ";";
		break;
	case 'printout':
		include 'browseout.php';
		// $a berisi
		function valid_dir($dirname) {
			// When dir is browseable
			// return strpos($dirname, 'thumb') === false
			return true;
		}
		
		function valid_file($filename) {
			// When file is outputable
			// return strpos($key, 's') !== 0
			return !preg_match('/\\d+x\\d+/', $filename);
			// return preg_match('/_100_.*thumb/', $filename);
			return true;
		}
		
		function recurse2($prefix, $item, $name) {
			foreach ($item as $key => $val) {
				if (is_array($val)) {
					if (valid_dir($key)) {
						recurse2($prefix.$key, $val, $name);
					}
				} else if (valid_file($key)) {
					//$name = basename($prefix);
					$name = rawurldecode($key);
					//$key = preg_replace('/(_100_.*thumb)/', '', $key);
					echo '<a href="'.$prefix.$key.'">'.$name.'</a><br/>'."\n";
				}
			}
		}
		foreach ($a as $name => $el) {
			recurse2($extract_url . $name, $el, $name);
		}
		break;
	case 'asianphoto.org':	// khusus situs asianphoto.org, outputnya 
		$base = 'http://www.asianphoto.org/';
	// 1. Retrieve Categories
		$c = new Crawler($base);
		$c->go_to('Categories');
		$cats = array();
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, '"maincat"')) {
				$part = Crawler::extract($line, 'href="', '"');
				$title = Crawler::extract($line, '">', '</');
				$cats[$title] = $part;
			} else if (Crawler::is_there($line, 'javascript')) {
				break;
			}
		}
		$c->close();
	// 2. Browse per categories
		foreach ($cats as $title => $part) {
			$c = new Crawler($base . $part);
			$c->go_to('Found:');
			preg_match('/ on (\\d+) page/', $c->curline, $matches);
			$n = $matches[1];
			// crawl first page
			while ($line = $c->readline()) {
				if (Crawler::is_there($line, '/thumbnails/')) {
					$img = Crawler::extract($line, '<img src="', '"');
					$img = str_replace('/thumbnails/', '/media/', $img);
					$fname = basename($img);
					echo "<a href='$base$img'>$title</a><br/>\n";
					$c->readline();
				} else if (Crawler::is_there($line, 'hotasianphoto.com')) {
					break;
				}
			}
			$c->close();
			// crawl the rest of the pages
			// change /thumbnails/ to /media/
			for ($i=2; $i<=$n; $i++) {
				$c = new Crawler($base . $part . '?page=' . $i);
				$c->go_to('Found:');
				while ($line = $c->readline()) {
					if (Crawler::is_there($line, '/thumbnails/')) {
						$img = Crawler::extract($line, '<img src="', '"');
						$img = str_replace('/thumbnails/', '/media/', $img);
						$fname = basename($img);
						echo "<a href='$base$img'>$title</a><br/>\n";
						$c->readline();
					} else if (Crawler::is_there($line, 'hotasianphoto.com')) {
						break;
					}
				}
				$c->close();
			}
		}
		break;
	case 'blog':	// specialized for blog-type
		$base = 'http://prettygirl2u.com/';
		// extract_posts_link
		function extract_posts_link($start_url) {
			// extract first page
			$result = array();
			$n = 0;
			$c = new Crawler($start_url);
			$c->go_to('class="PostHead"');
			while ($line = $c->readline()) {
				if (Crawler::is_there($line, 'class="title"')) {
					$url = Crawler::extract($line, '" href="', '"');
					$title = Crawler::extract($line, 'rel="bookmark">', '</a');
					$result[$title] = $url;
				} else if (Crawler::is_there($line, 'wp-pagenavi')) {
					$line = $c->readline();
					preg_match('/Page 1 of (\\d+)/', $line, $matches);
					$n = $matches[1];
					break;
				}
			}
			$c->close();
			// extract the rest of the pages
			for ($i=2; $i<=$n; $i++) {
				echo "URL: $start_url $i<br/>\n";
				$c = new Crawler($start_url . 'page/' . $i);
				$c->go_to('class="PostHead"');
				while ($line = $c->readline()) {
					if (Crawler::is_there($line, 'class="title"')) {
						$url = Crawler::extract($line, '" href="', '"');
						$title = Crawler::extract($line, 'rel="bookmark">', '</a');
						$result[$title] = $url;
						echo "$title $url <br/>\n";
					} else if (Crawler::is_there($line, 'wp-pagenavi')) {
						break;
					}
				}
				$c->close();
			}
			//file_put_contents('browse.debug', print_r($result, true));
			return $result;
		}
		// extract_images_from_post
		function extract_images_from_post($post_url, $title) {
			echo "Post: $post_url<br/>\n";
			$c = new Crawler($post_url);
			$c->go_to('class="PostContent');
			$images = array();
			while ($line = $c->readline()) {
				if (Crawler::is_there($line, 'src="')) {
					$img = Crawler::extract($line, 'src="', '"');
					//$alt = Crawler::extract($line, 'title="', '"');
					$images[] = $img;
				} else if (Crawler::is_there($line, '<h3>You might also like:</h3>')) {
					break;
				}
			}
			$c->close();
			return $images;
		}
		// print_image
		function print_image($img_url, $text, $title) {
			echo "<a href='$img_url'>$title</a><br/>\n";
		}
		foreach (extract_posts_link($base) as $title => $post_link) {
			foreach (extract_images_from_post($post_link, $title) as $key => $img_url) {
				print_image($img_url, $key, $title);
			}
		}
		break;
}
?>
</body>
</html>