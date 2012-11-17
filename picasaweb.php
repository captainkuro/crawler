<?php
require_once "crawler.php";

$base_url = 'http://picasaweb.google.com/';
extract($_POST);

function crawl_album($url, $alias = false) {
    $c = new Crawler($url);
    $c->go_to('<noscript>');
    $c->go_to('<noscript>');
    $c->readline();
	$target = '';//$c->curline;
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, '</noscript>')) {
			break;
		} else {
			$target .= trim($line);
		}
	}
    $hasil = Crawler::extract_to_array($target, 'src="', '"');
    $c->close();
	/* kalo mo ngambil desc sebagai nama file
	preg_match_all('/<img src="([^"]+)"><\\/a><p><a [^>]+>([^<]+)<\\/a>/', $target, $match);
	//file_put_contents('picasaweb.out', print_r($match, true));exit;
	foreach ($match[1] as $i => $uri) {
		$info = pathinfo(basename($uri));
		$ext = $info['extension'];
		$name = $match[2][$i];
		$img = str_replace('/s128/', '/', $uri);
		echo "<a href='$img'>$name.$ext</a><br />\n";
	}
	exit;
	*/
    if ($alias) {
        foreach ($hasil as $img) {
            $img = str_replace('/s128/', '/d/', $img);
            echo "<a href='$img'>$alias</a><br/>\n";flush();
        }
    } else {
        foreach ($hasil as $img) {
            $img = str_replace('/s128/', '/d/', $img);
            $basename = urldecode(basename($img));
            echo "<a href='$img'>$basename</a><br/>\n";flush();
        }
    }
}
?>
<html>
<head><meta http-equiv="Content-Type" content="application/xhtml+xml; charset=UTF-8" />
</head>
<body>
	<form action="" method="post">
		Start url: <input type="text" name="start_url" value="<?=isset($start_url)?$start_url:''?>" />
		<input type="submit" value="Submit" />
	</form>
<?php
if ($start_url) {
    $start_url = (Crawler::is_there($start_url, '#')) ? Crawler::cutuntil($start_url, '#') : $start_url;
    $ledak = explode('/', $start_url);
    if (count($ledak) == 5) {   // 1 album
        crawl_album($start_url);
    } else if (count($ledak) == 4) {    // 1 gallery
        $c = new Crawler($start_url);
        $c->go_to('<noscript>');
        $c->go_to('<noscript>');
        $links = array();
        while ($line = $c->readline()) {
            if (Crawler::is_there($line, '<a href="')) {
                $url = Crawler::extract($line, '<a href="', '"');
				$c->readline();
				$key = trim(Crawler::extract($c->curline, '<p>', '</p>'));
				//$key = basename($url);
                $links[$key] = $url;
            } else if (Crawler::is_there($line, '</noscript>')) {
                break;
            }
        }
        $c->close();
        foreach ($links as $key => $val) {
            crawl_album($val, $key);
        }
    }
}
?>
</body>
</html>