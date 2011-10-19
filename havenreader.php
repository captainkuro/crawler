<?php
require 'crawler.php';
extract($_POST);

function crawl_1_page($fil, $url, $chapter) {
	global $prefix;
	$c = new Crawler($fil);
	$c->go_to('id="img"');
	$c->readline();
	$c->close();
	$img = Crawler::extract($c->curline, 'src="', '"');
	if ($img) {
		$fname = Crawler::fix_filename(basename($img));
		echo "<a href='$img'>$prefix-$chapter-$fname</a><br />\n";
	} else { // Ulangi
		crawl_1_page($url, $url, $chapter);
	}
}

function crawl_1_chapter($url, $chapter) {
	global $sitename;
	global $prefix;
	// http://ani-haven.net/hr-alpha/Psyren/145/
	// @todo
	$chapter = Crawler::pad($chapter, 3);
	$c = new Crawler($url);
	$c->go_to('id="myselectbox3"');
	$c->readline();
	$pages = Crawler::extract_to_array($c->curline, 'value="', '"');
	$c->close();
	
	// append $url ke $pages
	foreach ($pages as $i => $page) {
		$pages[$i] = $url . $page;
	}
	
	Crawler::multiProcess(4, $pages, 'crawl_1_page', array($chapter));
}
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
<fieldset>
    <legend>Stage 1</legend>
    <form method="POST" action="">
        URL FOLDER: <input type="text" name="base" value="<?php echo @$base;?>"/> kalo url chapter berarti shortcut<br />
        Prefix: <input type="text" name="prefix" value="<?php echo @$prefix;?>"/><br />
		Infix: <input type="text" name="infiks" value="<?php echo @$infiks?>" /> for shortcut<br/>
        <input type="submit" name="stage1"/>
    </form>
</fieldset>
<?php
// http://ani-haven.net/hr-alpha/Psyren/
// http://ani-haven.net/hr-alpha/Psyren/145/
// http://ani-haven.net/hr-alpha/Psyren/145/19
// http://haven-reader.net/manga/Psyren/Chapter%20145/Psyren_145_19_%5BMudaScans%5D.png
$sitename = 'http://ani-haven.net/hr-alpha/';
if (preg_match('/\\/\\d+(\\/\\d+)?$/', @$base)) {	// shortcut, hanya 1 chapter
	$parsed = parse_url($base);
	$ledak = explode('/', $parsed['path']);
	// manga name is $ledak[2]; chapter is $ledak[3];
	crawl_1_chapter($sitename . $ledak[2] . '/' . $ledak[3] . '/', $infiks);
	/*
	$_series = urldecode(Crawler::extract($base, '&series=', '&'));
	$_chapter = urldecode(Crawler::cutafter($base, '&chapter='));
	crawl_1_chapter($sitename.'/index.php?mode=view&series='.$_series.'&chapter='.$_chapter.'&page=0', $infiks);
	*/
	exit;
}
?>
<br/><br/><br/>
<fieldset>
    <legend>Stage 2</legend>
    <form method="POST" action="">
        URL FOLDER: <input type="text" name="base" value="<?php echo @$base;?>"><br />
        Prefix: <input type="text" name="prefix" value="<?php echo @$prefix;?>"><br />
        <div>Choose volume/chapter to be downloaded:</div>
        <input type="checkbox" name="all" value="all" onclick="click_this()"/>All<br/>
		<table>
			<tr>
				<th>Chapter Name</th>
				<th>Infix</th>
			</tr>
<?php 
if (@$stage1) {
	echo '<tr><td colspan="2">Progress.. ';
	$parsed = parse_url($base);
	$ledak = explode('/', $parsed['path']);
	// manga name is $ledak[2]; chapter is $ledak[3];
	$c = new Crawler($base);
	$c->go_to('id="myselectbox2"');
	$c->readline();
	$chapters = Crawler::extract_to_array($c->curline, 'value="', '"');
	$descriptions = Crawler::extract_to_array($c->curline, '">', '</option');
	$infix = Crawler::extract_to_array($c->curline, '">Chapter ', '</option');
	$c->close();
	
	echo 'End</td></tr>';

	$chapters = array_reverse($chapters);
	$descriptions = array_reverse($descriptions);
	$infix = array_reverse($infix);
	$i = 1;
    foreach ($chapters as $key => $val) {
	?><tr>
        <td>
			<input type="checkbox" id="chapters-<?php echo $i?>" name="chapters[<?php echo $i?>]" value="<?php echo $chapters[$key]?>" />
			<label for="chapters-<?php echo $i?>"><?php echo $descriptions[$key]?></label>
			<input type="hidden" name="descriptions[<?php echo $i?>]" value="<?php echo $descriptions[$key]?>" />
		</td>
		<?php if (!trim($infix[$key])) $infix[$key] = $descriptions[$key] ?>
		<td><input type="text" name="infix[<?php echo $i?>]" value="<?php echo $infix[$key]?>"/></td>
	</tr><? $i++;
    }
} else if (@$stage2) {
    foreach ($chapters as $key => $val) {
        ?><tr>
			<td>
				<input type="checkbox" id="chapters-<?php echo $key?>" name="chapters[<?php echo $key?>]" value="<?php echo $chapters[$key]?>" checked="checked"/>
				<label for="chapters-<?php echo $key?>"><?php echo $descriptions[$key]?></label>
				<input type="hidden" name="descriptions[<?php echo $key?>]" value="<?php echo $descriptions[$key]?>" />
			</td>
			<td><input type="text" name="infix[<?php echo $key?>]" value="<?php echo $infix[$key]?>"/></td>
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
    if (@$stage2) {
		//$_series = urldecode(Crawler::cutafter($base, '&series='));
		$chapters = array_reverse($chapters, true);
		//$descriptions = array_reverse($descriptions);
		$infix = array_reverse($infix, true);
        foreach ($chapters as $key => $val) {
			//$url = $val;
			$url = $base . '/' . $val . '/';
			//$url = $sitename.'/index.php?mode=view&series='.$_series.'&chapter='.$val.'&page=0';
			echo "$url<br/>\n";
			crawl_1_chapter($url, $infix[$key]);
        }
    }?>
</fieldset>