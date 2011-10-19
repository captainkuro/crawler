<html>
<body>
http://www.xcentre.net/archives/tag/gravure-idol/page/1<br/>
http://www.xcentre.net/archives/category/gallery/page/87/<br/>
<?php 
require "crawler.php";

function crawl1page($url) {
    echo 'Entering '.$url . '<br/>';flush();
    $c = new Crawler($url);
    $c->go2linewhere('<div class="ngg-gallery-thumbnail"');
    $c->readline();
    $sample = $c->getbetween('href="', '"');
    $c->close();
    
    $dir = (dirname($sample));
    if (!$dir) return;
    $folder = substr($dir, strrpos($dir, '/')+1);
    $dir = dirname($dir).'/'.rawurlencode($folder).'/';
    echo 'Dir:'.$dir.'<br/>'."\n";flush();
    
    $c = new Crawler($dir);
    $c->go2linewhere('<ul>');
    $c->readline();
    while ($line = $c->readline()) {
        //echo $line;flush();
        if (strpos($line, '</ul>') !== false) {
            break;
        } else if (strpos($line, '"thumbs/"')) {
            break;
        }
        $filename = Crawler::extract($line, 'href="', '"');
        echo '<a href="' . $dir . $filename . '">' . rawurldecode($filename) . '</a><br/>' . "\n";
        flush();
    }
    $c->close();
    echo '<br/>'."\n";
    flush();
}

for ($i = 1; $i<=87; $i++) {
    echo "Sekarang i: $i<br/>\n";
    $c = new Crawler('http://www.xcentre.net/archives/category/gallery/page/' . $i);
    for ($j=1; $j<=5; $j++) {
        $c->go2linewhere('<div class="entry">');
        $c->readline();
        $page = $c->getbetween('href=', '>');
        if ($page) crawl1page($page);
    }
    $c->close();
}
?>
</body>
</html>