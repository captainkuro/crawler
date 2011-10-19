<?php
require 'crawler.php';
extract($_POST);

function imangascans_chapters($chs) {	
	global $base;
	global $sitename;
	global $prefix;
	foreach ($chs as $ch) {
		$url = $base . '/' . $ch;
		echo "$url<br />\n";
		$c = new Crawler($url);
		// Retrieve the js url
		// $c->go_to('src="pages/');
		// preg_match('/src="(pages[^"]*\\.js)"/', $c->curline, $match);
		// $js = Crawler::url_encode($match[1]);
		// $c->close();
		
		// $c = new Crawler($sitename . '/' . $js, array('use_curl' => true));
		$c->go_to('var pages = ');
		$pages = json_decode('{'.Crawler::extract($c->curline, '{', '}').'}');
		
		/* CHANGED
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, 'img_url.push')) {
				$img = Crawler::extract($line, "('", "'");
				$img = str_replace("\\'", "'", $img);
				if ($img) echo '<a href="'.$sitename.'/'.$img.'">'.$prefix.'-'.Crawler::pad($ch, 3).'-'.basename($img).'</a>'."<br/>\n";
			} else if (Crawler::is_there($line, 'var page = ')) {
				break;
			}
		}
		*/
		// $raw = explode('","', $c->curline);
		$chap = Crawler::pad($ch, 3);
		foreach ($pages->pages as $p) if ($p) {
			$name = basename($p);
			echo "<a href='$sitename/{$pages->pg_base}{$p}'>$prefix-$chap-$name</a><br />\n";
		}
		/*
		for ($i=1; $i<count($raw); $i++) {
			$row = $raw[$i];
			if (Crawler::is_there($row, '"')) {
				$row = Crawler::cutuntil($row, '"');
			}
			echo "<a href='$sitename/$row'>$prefix-$chap-$name</a><br />\n";
		}
		*/
		// print_r($ledak);
		$c->close();
	}
}

function mangatopia_chapters($chapters, $infixs) {
	global $base;
	global $sitename;
	global $prefix;
	
	$base = dirname(dirname(dirname($base)));
	// $chapters = array_reverse($chapters);
	// $infixs = array_reverse($infixs);
	foreach ($chapters as $key => $val) {
		$url = $base . '/' . $val . '/page/01';
		echo "$url<br/>";
		$chapter = $val;
		$c = new Crawler($url);
		$c->go_to('id="pages"'); 
		$pages = array();
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, 'value=')) {
				$pages[] = Crawler::extract($line, 'value="', '"');
			} else if (Crawler::is_there($line, '</select>')) {
				break;
			}
		}
		$c->close();
		$url = dirname($url);
		//print_r($pages);flush();
		foreach ($pages as $page) {
			//echo "$url/$page<br/>";flush();
			do {
				try {
					$c = new Crawler($url . '/' . $page);
					echo '1';flush();
					$c->go_to('class="page"');
					echo '2';flush();
					$img = $c->getbetween('<img src="', '"');
					echo '3';flush();
					//$ifx = Crawler::pad($chapter, 3);
					$ifx = $infixs[$key];
					echo '<a href="'.$sitename.'/'.$img.'">'.$prefix.'-'.$ifx.'-'.basename($img).'</a>'."<br/>\n";flush();
					$c->close();
					$berhasil = true;
				} catch (Exception $e) {
					$berhasil = false;
				}
			} while (!$berhasil);
		}
	}
}

// khusus egscans.com/Manga_Viewer
function egscans_chapters($chapters, $infixs) {
	global $base;
	global $sitename;
	global $prefix;
	
	// $chapters = array_reverse($chapters);
	// $infixs = array_reverse($infixs);
	foreach ($chapters as $key => $val) {
		$url = $base . '/' . $val;
		echo "$url<br/>";
		$chapter = $val;
		$c = new Crawler($url);
		$c->go_to('id="image_frame"');
		$images = array();
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, "img_url.push('")) {
				$images[] = Crawler::extract($line, "'", "'");
			} else if (Crawler::is_there($line, 'var well_behaved_browser')) {
				break;
			}
		}
		$c->close();
		
		// print_r($images);flush();
		if (preg_match('/(\d+)/', $infixs[$key], $m)) {
			$ifx = Crawler::pad($m[1], 3);
		} else {
			$ifx = Crawler::pad($infixs[$key], 3);
		}
		foreach ($images as $img) {
			echo '<a href="'.$sitename.'/'.$img.'">'.$prefix.'-'.$ifx.'-'.basename($img).'</a>'."<br/>\n";flush();
		}
		
	}
}

function omfggscans_chapters($chapters, $infixs) {
	global $base;
	global $sitename;
	global $prefix;
	
	foreach ($chapters as $key => $val) {
		$url = $base."&c=$val";
		$ifx = Crawler::pad($infixs[$key], 3);
		echo "$url<br/>\n";
		
		$c = new Crawler($url);
		// retrieve pages
		$c->go_to("name='page'");
		$pages = array();
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, '<option')) {
				$pg = Crawler::extract($line, "value='", "'");
				$pgtext = Crawler::extract($line, "'>", "</");
				$pages[$pg] = $pgtext;
			} else if (Crawler::is_there($line, '</select>')) {
				break;
			}
		}
		// sample image url
		$c->go_to("class='manga-img'");
		$src = Crawler::extract($c->curline, 'src="', '"');
		$pre_src = dirname($src).'/';
		$post_src = '.png';
		$c->close();
		
		foreach ($pages as $k => $v) {
			$href = $pre_src.$v.$post_src;
			$text = "$prefix-$ifx-$v$post_src";
			echo "<a href='$href'>$text</a><br />\n";
		}
	}
}

// REFERENCE
$sites = array(
    'keishou.net' => 'http://keishou.net/manga',
    'imangascans.com' => 'http://reader.imangascans.com',
    'imangascans.org' => 'http://reader.imangascans.org',
    'allmymanga.com' => 'http://allmymanga.com',
    'helz0ne-scans.com' => 'http://www.helz0ne-scans.com/reader',
    'mangatopia.net/reader' => 'http://mangatopia.net/reader/en',
    'mangatopia.net/manga' => 'http://mangatopia.net/',
    'urupload.com' => 'http://urupload.com',
    'egscans.com' => 'http://egscans.com/Manga_Viewer/',
	'lector.otaku-central.com' => 'http://lector.otaku-central.com',
	'asterixonline.info' => 'http://asterixonline.info/comics',
	'omfggscans.com' => 'http://www.omfggscans.com/Reader/?m=Grim+Reaper',
);
?>
<html>
<body>
<script type="text/javascript">
var global_check = false;
function click_this() {
    global_check = !global_check;
    var tags = document.getElementsByTagName("input");
    for (i in tags) {
        if (tags[i].type == "checkbox") {
            tags[i].checked = global_check;
        }
    }
}
</script>
Supported:
<pre><?php print_r($sites) ?></pre>
Note:<ul>
	<li>http://mangatopia.net/manga : http://mangatopia.net/manga/historys-strongest-disciple-kenichi/chapter/414/page/01</li>
</ul>
<fieldset>
    <legend>Stage 1</legend>
    <form method="POST" action="">
        URL FOLDER: <input type="text" name="base" value="<?php echo @$base;?>"><br />
        Prefix: <input type="text" name="prefix" value="<?php echo @$prefix;?>"><br />
        <input type="submit" name="stage1"/>
    </form>
</fieldset>
<?php
//http://keishou.net/manga/Bakuman
// http://allmymanga.com/Good_Ending/050
// http://mangatopia.net/reader/en/Air_Gear/288/1
// http://urupload.com/defense_devil/067/2  http://urupload.com/mangas/defense%20devil/067/01.png
// http://reader.imangascans.org/Change_123/056
$base = @$_POST['base'];
$prefix = @$_POST['prefix'];
$sitename = '';

foreach ($sites as $part => $site) {
    if (strpos($base, $part)) {
        $sitename = $site;
        break;
    }
}
if (strpos($base, 'mangatopia.net/manga')) $is_topia1 = true; else $is_topia1 = false;
?>
<br/><br/><br/>
<fieldset>
    <legend>Stage 2</legend>
    <form method="POST" action="">
        URL FOLDER: <input type="text" name="base" value="<?php echo $base;?>"><br />
        Prefix: <input type="text" name="prefix" value="<?php echo $prefix;?>"><br />
        <div>Choose volume/chapter to be downloaded:</div>
        <input type="checkbox" name="all" value="all" onclick="click_this()"/>All<br/>
<?php
if (@$stage1) {
    //
	$c = new Crawler($base);
	if ($is_topia1) {
		$c->go_to('name="chapters"');
		$chapters = array();
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, '<option')) {
				$ch = Crawler::extract($line, 'value="', '"');
				$chapters[$ch] = $ch;
			} else if (Crawler::is_there($line, '</select>')) {
				break;
			}
		}
	} else if (strpos($base, 'omfggscans.com/Reader')) {
		$c->go_to("name='chapter'");
		$chapters = array();
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, '<option')) {
				$ch = Crawler::extract($line, "value='", "'");
				if ($ch) $chapters[$ch] = $ch;
			} else if (Crawler::is_there($line, '</select>')) {
				break;
			}
		}
	} else {
		$c->go_to('name="chapter"');
		$ledak = explode('value="', $c->curline);
		$chapters = array();
		for ($i=1; $i<count($ledak); $i++) {
			$ch = Crawler::cutuntil($ledak[$i], '"');
			$chapters[$ch] = $ch;
		}
	}
	$c->close();
    foreach ($chapters as $key => $val) {
        echo '<input type="checkbox" name="chapters['.$key.']" value="'.$val.'" />'.$key.
			'<input type="text" name="infixs['.$key.']" value="'.$key.'" />'.
			'<br/>'."\n";
        flush();
    }
} else if (@$stage2) {
    foreach ($chapters as $key => $val) {
        echo '<input type="checkbox" name="chapters['.$key.']" value="'.$val.'" checked="checked"/>'.$val.
			'<input type="text" name="infixs['.$key.']" value="'.$infixs[$key].'" />'.
			'<br/>'."\n";
        flush();
    }
}
flush();
?>
        <input type="submit" name="stage2"/>
    </form>
</fieldset>
<br/><br/><br/>
<fieldset>
    <legend>Stage 3</legend>
    <div>Right-click and DownThemAll! with *text*\*name*.*ext* option</div>
    <?php flush();
    if (@$stage2) {
		if (strpos($base, 'imangascans.org')) {
			imangascans_chapters($chapters);
		} else if ($is_topia1) {
			mangatopia_chapters($chapters, $infixs);
		} else if (strpos($base, 'egscans.com')) {
			egscans_chapters($chapters, $infixs);
		} else if (strpos($base, 'omfggscans.com')) {
			omfggscans_chapters($chapters, $infixs);
		} else {
			// $chapters = array_reverse($chapters);
			// $infixs = array_reverse($infixs);
			foreach ($chapters as $key => $val) {
				$url = $base . '/' . $val;
				echo "$url<br/>";
				$chapter = $val;
				$c = new Crawler($url);
				$c->go_to('name="page"');
				$pages = Crawler::extract_to_array($c->curline, 'value="', '"');
				$c->close();
				//print_r($pages);flush();
				foreach ($pages as $page) {
					//echo "$url/$page<br/>";flush();
					do {
						try {
							$c = new Crawler($url . '/' . $page);
							echo '1';flush();
							$c->go_to('class="picture"');
							echo '2';flush();
							$img = $c->getbetween('<img src="', '"');
							echo '3';flush();
							//$ifx = Crawler::pad($chapter, 3);
							$ifx = $infixs[$key];
							echo '<a href="'.$sitename.'/'.$img.'">'.$prefix.'-'.$ifx.'-'.basename($img).'</a>'."<br/>\n";flush();
							$c->close();
							$berhasil = true;
						} catch (Exception $e) {
							$berhasil = false;
						}
					} while (!$berhasil);
				}
			}
		}
    }?>
</fieldset>
