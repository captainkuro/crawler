<?php
// untuk situs2 yg pakai Foolreader

/*
http://manga.redhawkscans.com/?manga=SWOT
http://manga.redhawkscans.com/?manga=SWOT&chapter=SWOT+001
http://manga.redhawkscans.com/?manga=SWOT&chapter=SWOT+001&page=2
http://mudareader.linkmonsters.net/?manga=Historys+Strongest+Disciple+Kenichi&chapter=Chapter+421#page=1
http://www.mudascantrad.com/Reader/?manga=Billy+Bat&chapter=Chapter+55#page=1
*/
require 'crawler.php';
extract($_POST);

function foolreader_1_page($fil, $url, $chapter) {
	global $prefix;
    $chapter = Crawler::pad($chapter, 3);
	$c = new Crawler($fil);
	// @TODO|ga dipake
    echo "<a href='$url'>URL</a> ";
	echo '<a href="'.$img.'">'.$prefix.'-'.$chapter.'-'.basename($img).'</a>'."<br/>\n";
	$c->close();
}

function foolreader_1_chapter($url, $chapter) {
	global $sitename;
	global $prefix;
	$chapter = Crawler::pad($chapter, 3);
	$c = new Crawler($url);
	$c->go_to('imageArray = new Array');
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, 'imageArray[')) {
			$img = Crawler::extract($line, "'", "'");
			if (strpos($img, 'http://') !== 0) $img = $sitename . $img;
			$fname = basename($img);
			echo "<a href='$img'>$prefix-$chapter-$fname</a><br/>\n";
		} else if (Crawler::is_there($line, 'function loadImage')) {
			break;
		}
	}
	$c->close();
	/*
	// @TODO
	$pages = array();
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, '<option')) {
			$pages[] = $sitename . Crawler::extract($line, 'value=\'', "'");
		} else if (Crawler::is_there($line, '</select>')) {
			break;
		}
	}
	//$pages = Crawler::extract_to_array($c->curline, 'value="', '"');
	$c->close();
	
	//Crawler::multiProcess(4, $pages, 'foolreader_1_page', array($chapter));
	*/
}

$sites = array(
	'mangacurse.info' => 'http://mangacurse.info/reader/',
	'redhawkscans.com' => 'http://manga.redhawkscans.com/',
	'allmymanga.com' => 'http://www.allmymanga.com/',
	'mudareader.' => 'http://mudareader.linkmonsters.net/',
	// 'mudascantrad.com' => 'http://www.mudascantrad.com/Reader/',
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
Support:
<pre><?php print_r($sites) ?>
Can also guessing</pre>

<fieldset>
    <legend>Stage 1</legend>
    <form method="POST" action="">
        URL FOLDER: <input type="text" name="base" value="<?=$base;?>"/> kalo url chapter berarti shortcut<br />
        Prefix: <input type="text" name="prefix" value="<?=$prefix;?>"/><br />
		Infix: <input type="text" name="infiks" value="<?=$infiks?>" /> for shortcut<br/>
        <input type="submit" name="stage1"/>
    </form>
</fieldset>
<?php
// http://manga.redhawkscans.com/?manga=SWOT
// http://manga.redhawkscans.com/?manga=SWOT&chapter=SWOT+001
$sitename = '';
foreach ($sites as $part => $site) {
    if (strpos($base, $part) !== false) {
        $sitename = $site;
        break;
    }
}
if (!$sitename) {
	// nama domain tidak terdaftar, try guessing
	preg_match('/^([^\?]+)\?/', $base, $m);
	$sitename = $m[1];
}

if (Crawler::is_there($base, '&chapter=')) {	// shortcut, hanya 1 chapter
	foolreader_1_chapter($base, $infiks);
	exit;
}
?>
<br/><br/><br/>
<fieldset>
    <legend>Stage 2</legend>
    <form method="POST" action="">
        URL FOLDER: <input type="text" name="base" value="<?=$base;?>"><br />
        Prefix: <input type="text" name="prefix" value="<?=$prefix;?>"><br />
        <div>Choose volume/chapter to be downloaded:</div>
        <input type="checkbox" name="all" value="all" onclick="click_this()"/>All<br/>
		<table>
			<tr>
				<th>Chapter Name</th>
				<th>Infix</th>
			</tr>
<?php 
if ($stage1) {
	echo '<tr><td colspan="2">Progress.. ';
	$c = new Crawler($base);
	$chapters = array();
	$descriptions = array();
	$infix = array();
	// @TODO
	
	$c->go_to('class="selector"');
	$c->go_to('class="selector"');
	$chapters = Crawler::extract_to_array($c->curline, "href='", "'");
	$raws = Crawler::extract_to_array($c->curline, "class='option'>", '</div');
	//array_shift($raws);
	//array_pop($raws);
	$descriptions = $raws;
	foreach ($descriptions as $desc) {
		preg_match('/(\\d+)$/', $desc, $matches);
		$infix[] = $matches[1];
	}
	/*
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, 'class="chico"')) {
			$chp = Crawler::extract($line, 'href="', '"');
			$chapters[] = $chp;
			$descriptions[] = strip_tags(Crawler::extract($line, ': ', '</td>'));
			$ifx = Crawler::cutfromlast1($chp, '/');
			$ifx = str_replace('chapter-', '', $ifx);
			$ifx = str_replace('.html', '', $ifx);
			$infix[] = $ifx;
			echo $ifx.'.. '; 
		} else if (Crawler::is_there($line, '</table>')) {
			break;
		}
	}
	*/
	$c->close();
	echo 'End</td></tr>';

	/*
	$chapters = array_reverse($chapters);
	$descriptions = array_reverse($descriptions);
	$infix = array_reverse($infix);
	*/
	$i = 1;
    foreach ($chapters as $key => $val) {
	?><tr>
        <td>
			<input type="checkbox" id="chapters-<?=$i?>" name="chapters[<?=$i?>]" value="<?=$chapters[$key]?>" />
			<label for="chapters-<?=$i?>"><?=$descriptions[$key]?></label>
			<input type="hidden" name="descriptions[<?=$i?>]" value="<?=$descriptions[$key]?>" />
		</td>
		<?php if (!trim($infix[$key])) $infix[$key] = $descriptions[$key] ?>
		<td><input type="text" name="infix[<?=$i?>]" value="<?=$infix[$key]?>"/></td>
	</tr><?  $i++;
    }
} else if ($stage2) {
    foreach ($chapters as $key => $val) {
        ?><tr>
			<td>
				<input type="checkbox" id="chapters-<?=$key?>" name="chapters[<?=$key?>]" value="<?=$chapters[$key]?>" checked="checked"/>
				<label for="chapters-<?=$key?>"><?=$descriptions[$key]?></label>
				<input type="hidden" name="descriptions[<?=$key?>]" value="<?=$descriptions[$key]?>" />
			</td>
			<td><input type="text" name="infix[<?=$key?>]" value="<?=$infix[$key]?>"/></td>
		</tr><? 
    }
}
?>
		</table>
        <input type="submit" name="stage2"/>
    </form>
</fieldset>
<br/><br/><br/>
<fieldset>
    <legend>Stage 3</legend>
    <div>Right-click and DownThemAll! with *text*\*name*.*ext* option</div>
    <?php 
    if ($stage2) {
		$chapters = array_reverse($chapters, true);
		$descriptions = array_reverse($descriptions);
		$infix = array_reverse($infix, true);
		
		foreach ($chapters as $key => $val) {
			$url = $sitename  . $val;
			echo "$url<br/>\n";
			foolreader_1_chapter($url, $infix[$key]);
        }
    }?>
</fieldset>