<?php
require 'crawler.php';
extract($_POST);

function crawl_1_chapter($url, $chapter) {
	global $sitename;
	global $prefix;
	$_series = Crawler::extract($url, 'series=', '&');
	$_chapter = Crawler::cutafter($url, 'chapter=');
	//echo $_series.'|'.$_chapter."<br/>\n";flush();return;
	$c = new Crawler($url);
	$c->go_to("name='pagesel1'");
	$pages = array();
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, '<option')) {
			$pages[Crawler::extract($line, "value='", "'")] = trim(Crawler::cutafterlast($line, '>'));
		} else if (Crawler::is_there($line, '</select>')) {
			break;
		}
	}
	$c->close();
	foreach ($pages as $key => $val) {
		// WARNING: need high reliable connection
		$post_data = 'manga_hid='.$_series.
			'&chapter_hid='.$_chapter.
			'&image_hid='.$key.
			'&series='.$_series.
			'&chapter='.$_chapter.
			'&pagesel1=0';
		//echo "<br/>POST:$post_data<br/>\n";
		$c = new Crawler('http://stoptazmo.com/downloads/manga_viewer.php', 
			array(/*
				'Referer' => "http://stoptazmo.com/downloads/manga_viewer.php?series=$_series&chapter=$_chapter",
				'Cookie' => 'bblastvisit=1281047101; bblastactivity=0; bbsessionhash=8e05ef2bcf531b0c45f0ce12b498717e',
			*/), 
			$post_data);
		$c->go_to("style='border: none;margin-left:-6px;'");
		$imgurl = Crawler::extract($c->curline, "<img src='", "'");
		$c->close();
		$filename = basename($imgurl);
		echo '<a href="'.$imgurl.'">'.$prefix.'-'.Crawler::pad($chapter, 3).'-'.$filename.'</a><br/>'."\n";flush();
	}
	//print_r($pages);flush();
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
        URL FOLDER: <input type="text" name="base" value="<?=$base;?>"/> kalo url chapter berarti shortcut<br />
        Prefix: <input type="text" name="prefix" value="<?=$prefix;?>"/><br />
		Infix: <input type="text" name="infiks" value="<?=$infiks?>" /> for shortcut<br/>
        <input type="submit" name="stage1"/>
    </form>
</fieldset>
<?php
//http://stoptazmo.com/manga-series/one_piece/
//http://stoptazmo.com/downloads/manga_viewer.php?series=one_piece&chapter=one_piece_360.zip
$sitename = 'http://stoptazmo.com';

if (Crawler::is_there($base, '/manga_viewer.php')) {	// shortcut, hanya 1 chapter
	crawl_1_chapter($base, $infiks);
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
<?php flush();
if ($stage1) {
	echo '<tr><td colspan="2">Progress.. ';
	$c = new Crawler($base);
	$c->go_to('<!-- START DOWNLOADS -->');
	$chapters = array();
	$descriptions = array();
	$infix = array();
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, 'READ ONLINE')) {
			$chapters[] = Crawler::extractlast($line, "href='", "'");
			$desc = Crawler::extract($line, '<td>', '</td>');
			$descriptions[] = $desc;
			$ifx = Crawler::extractlast($desc, '_', '.');
			$infix[] = $ifx;
			echo $ifx.'.. '; flush();
		} else if (Crawler::is_there($line, '</table>')) {
			break;
		}
	}
	$c->close();
	echo 'End</td></tr>';flush();

	$chapters = array_reverse($chapters);
	$descriptions = array_reverse($descriptions);
	$infix = array_reverse($infix);
	$i = 1;
    foreach ($chapters as $key => $val) {
	?><tr>
        <td>
			<input type="checkbox" name="chapters[<?=$i?>]" value="<?=$chapters[$key]?>" />
			<?=$descriptions[$key]?>
			<input type="hidden" name="descriptions[<?=$i?>]" value="<?=$descriptions[$key]?>" />
		</td>
		<td><input type="text" name="infix[<?=$i?>]" value="<?=$infix[$key]?>"/></td>
	</tr><? flush(); $i++;
    }
} else if ($stage2) {
    foreach ($chapters as $key => $val) {
        ?><tr>
			<td>
				<input type="checkbox" name="chapters[<?=$key?>]" value="<?=$chapters[$key]?>" checked="checked"/>
				<?=$descriptions[$key]?>
				<input type="hidden" name="descriptions[<?=$key?>]" value="<?=$descriptions[$key]?>" />
			</td>
			<td><input type="text" name="infix[<?=$key?>]" value="<?=$infix[$key]?>"/></td>
		</tr><? flush();
    }
}
flush();
?>
		</table>
        <input type="submit" name="stage2"/>
    </form>
</fieldset>
<br/><br/><br/>
<fieldset>
    <legend>Stage 3</legend>
    <div>Right-click and DownThemAll! with *text*\*name*.*ext* option</div>
    <?php flush();
    if ($stage2) {
		$chapters = array_reverse($chapters, true);
		//$descriptions = array_reverse($descriptions);
		$infix = array_reverse($infix, true);
        foreach ($chapters as $key => $val) {
			$url = $val;
			crawl_1_chapter($url, $infix[$key]);
        }
    }?>
</fieldset>