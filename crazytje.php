<?php
require 'crawler.php';
extract($_POST);
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
        URL FOLDER: <input type="text" name="base" value="<?=$base;?>"><br />
        <!--Prefix: <input type="text" name="prefix" value="<?=$prefix;?>"><br />-->
        <input type="submit" name="stage1"/>
    </form>
</fieldset>
<?php
//http://crazytje.be/Manga/230
$base = $_POST['base'];
$prefix = $_POST['prefix'];
$sitename = "http://crazytje.be";
?>
<br/><br/><br/>
<fieldset>
    <legend>Stage 2</legend>
    <form method="POST" action="">
        URL FOLDER: <input type="text" name="base" value="<?=$base;?>"><br />
        <!--Prefix: <input type="text" name="prefix" value="<?=$prefix;?>"><br />-->
        <div>Choose volume/chapter to be downloaded:</div>
        <input type="checkbox" name="all" value="all" onclick="click_this()"/>All<br/>
<?php
if ($stage1) {
    $c = new Crawler($base);
    $c->go2linewhere("latestreleases");
    $chapters = array();
    $tempurl = null;
    $tempdesc = null;
    while ($line = $c->readline()) {
        if (strpos($line, 'class="readonlinetext"><a href="') !== false) {
            $tempurl = Crawler::extract($line, 'class="readonlinetext"><a href="', '"');
        } else if (strpos($line, "class='description2'") !== false) {
            $tempdesc = Crawler::extract($line, "class='description2'>", '</div>');
            $chapters[$tempdesc] = $tempurl;
        } else if (strpos($line, '</table>') !== false) {
            break;
        }
    }
    $c->close();
    foreach ($chapters as $key => $val) {
        echo '<input type="checkbox" name="chapters[]" value="'.$val.'" />'.$key.'<br/>'."\n";
        flush();
    }
} else if ($stage2) {
    foreach ($chapters as $key => $val) {
        echo '<input type="checkbox" name="chapters[]" value="'.$val.'" checked="checked"/>'.$val.'<br/>'."\n";
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
    <?php 
    if ($stage2) {
        foreach ($chapters as $key => $val) {
            $url = $sitename . $val;
            $c = new Crawler($url);
            // get chapter name
            $c->go2linewhere('data[chapter]');
            $c->go2linewhere('selected="selected"');
            $selected = $c->getbetween('selected="selected">', '</option>');
            $c->go2lineor(array('data[volumechapter]', 'data[pages]'));
            // mana yg duluan ketemu
            if ($c->strpos('data[volumechapter]') !== false) {
                $volchaps = array();
                while ($line = $c->readline()) {
                    if (strpos($line, '</option>') !== false) {
                        $volchaps[Crawler::extract($line, 'value="', '"')] = Crawler::extract($line, '>', '</option');
                    } else if (strpos($line, '</select><br') !== false) {
                        break;
                    }
                }
                //print_r($volchaps);flush();
                $c->close();
                foreach ($volchaps as $key2 => $val2) {
                    $url2 = $sitename . dirname($val) . '/' . $key2;
                    $c = new Crawler($url2);
                    $c->go2linewhere('data[pages]');
                    $pages = array();
                    while ($line = $c->readline()) {
                        if (strpos($line, '</option>') !== false) {
                            $pages[] = Crawler::extract($line, '>', '</option');
                        } else if (strpos($line, '</select>') !== false) {
                            break;
                        }
                    }
                    $c->go2linewhere('scanlations');
                    $imgurl = $c->getbetween('<img src="', '"');
                    $imgbase = dirname($imgurl);
                    foreach ($pages as $page) {
                        echo '<a href="'.$imgbase.'/'.$page.'">'.$val2.'</a><br/>'."\n";
                        flush();
                    }
                    $c->close();
                }
            } else if ($c->strpos('data[pages]') !== false) {
                $pages = array();
                while ($line = $c->readline()) {
                    if (strpos($line, '</option>') !== false) {
                        $pages[] = Crawler::extract($line, '>', '</option');
                    } else if (strpos($line, '</select>') !== false) {
                        break;
                    }
                }
                $c->go2linewhere('scanlations');
                $imgurl = $c->getbetween('<img src="', '"');
                $imgbase = dirname($imgurl);
                foreach ($pages as $page) {
                    echo '<a href="'.$imgbase.'/'.$page.'">'.$selected.'</a><br/>'."\n";
                    flush();
                }
            }
            $c->close();
        }
    }?>
</fieldset>
