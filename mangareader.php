<?php
require 'crawler.php';
extract($_POST);

function mangareader_1_page($fil, $url, $chapter) {
	global $prefix;
    $chapter = Crawler::pad($chapter, 3);
	$c = new Crawler($fil);
	$c->go_to('width="800"');
	$img = $c->getbetween('src="', '"');
    if (@$_GET['show_url']) echo "<a href='$url'>URL</a> ";
	echo '<a href="'.$img.'">'.$prefix.'-'.$chapter.'-'.basename($img).'</a>'."<br/>\n";
	$c->close();
}

function mangareader_1_chapter($url, $chapter) {
	global $sitename;
	global $prefix;
	$c = new Crawler($url);
	$c->go_to('id="pageMenu"');
	$pages = array();
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, '<option')) {
			$pages[] = $sitename . Crawler::extract($line, 'value="', '"');
		} else if (Crawler::is_there($line, '</select>')) {
			break;
		}
	}
	//$pages = Crawler::extract_to_array($c->curline, 'value="', '"');
	$c->close();
	
	Crawler::multiProcess(4, $pages, 'mangareader_1_page', array($chapter));
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
        URL FOLDER: <input type="text" name="base" value="<?php echo $base;?>"/> kalo url chapter berarti shortcut<br />
        Prefix: <input type="text" name="prefix" value="<?php echo $prefix;?>"/><br />
		Infix: <input type="text" name="infiks" value="<?php echo $infiks?>" /> for shortcut<br/>
        <input type="submit" name="stage1"/>
    </form>
</fieldset>
<?php
//http://www.mangareader.net/103/one-piece.html
$sitename = 'http://www.mangareader.net';

if (Crawler::is_there($base, '/chapter-')) {	// shortcut, hanya 1 chapter
	mangareader_1_chapter($base, $infiks);
	exit;
}
?>
<br/><br/><br/>
<fieldset>
    <legend>Stage 2</legend>
    <form method="POST" action="">
        URL FOLDER: <input type="text" name="base" value="<?php echo $base;?>"><br />
        Prefix: <input type="text" name="prefix" value="<?php echo $prefix;?>"><br />
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
	$c->go_to('id="listing"');
	$chapters = array();
	$descriptions = array();
	$infix = array();
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, 'class="chico_')) {
			if (!Crawler::is_there($line, ' href="')) $line = $c->readline();
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
		<td><input type="text" name="infix[<?php echo $i?>]" value="<?php echo $infix[$key]?>"/></td>
	</tr><?  $i++;
    }
} else if ($stage2) {
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
    if ($stage2) {
		$chapters = array_reverse($chapters, true);
		//$descriptions = array_reverse($descriptions);
		$infix = array_reverse($infix, true);
        foreach ($chapters as $key => $val) {
			$url = $sitename  . $val;
			mangareader_1_chapter($url, $infix[$key]);
        }
    }?>
</fieldset>