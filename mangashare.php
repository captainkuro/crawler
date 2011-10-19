<?php
require 'crawler.php';
extract($_POST);

function crawl_1_chapter($url, $chapter) {
	global $sitename;
	global $prefix;
	$c = new Crawler($url);
	$c->go_to('name="pagejump"');
	$pages = array();
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, '<option')) {
			$pages[] = Crawler::extract($line, 'value="', '"');
		} else if (Crawler::is_there($line, '</select>')) {
			break;
		}
	}
	$c->go_to('id="nextpage"');
	$c->readline();
	$img = $c->getbetween('src="', '"');
	$c->close();
	
	$img_base = dirname($img);
	$ext = '.jpg';
	$chapter = Crawler::pad($chapter, 3);
	foreach ($pages as $page) {
		echo "<a href='$img_base/$page$ext'>$prefix-$chapter-$page$ext</a><br/>\n";flush();
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
//http://read.mangashare.com/K-O-Sen
//http://read.mangashare.com/K-O-Sen/chapter-008/page001.html
$sitename = 'http://read.mangashare.com';

if (Crawler::is_there($base, '/chapter-')) {	// shortcut, hanya 1 chapter
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
	$c->go_to('class="datarow"');
	$chapters = array();
	$descriptions = array();
	$infix = array();
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, '"datarow-1"')) {
			$line1 = $line;
			$c->readline();
			$line2 = $c->readline();
			//$line2 = $c->curline;
			$chapters[] = Crawler::extract($line2, 'href="', '"');
			$desc = Crawler::extract($line1, '>', '</td>');
			$descriptions[] = $desc;
			$ifx = Crawler::cutuntil($desc, ' ');
			$infix[] = $ifx;
			echo $ifx.'.. '; flush();
		} else if (Crawler::is_there($line, '</table>')) {
			break;
		}
	}
	$c->close();
	echo 'End</td></tr>';flush();

	//$chapters = array_reverse($chapters);
	//$descriptions = array_reverse($descriptions);
	//$infix = array_reverse($infix);
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
		//$infix = array_reverse($infix, true);
        foreach ($chapters as $key => $val) {
			$url = $val;
			crawl_1_chapter($url, $infix[$key]);
        }
    }?>
</fieldset>