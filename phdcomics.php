<?php
// URL: http://www.phdcomics.com/comics/archive.php?comicid=1295
require_once "crawler.php";
extract($_POST);

class Phdcomics_Crawler {
    private $base_url = 'http://www.phdcomics.com/comics/archive.php?comicid=';
    private $i_from, $i_to;
    
    public static function factory($ifrom, $ito) {
        return new Phdcomics_Crawler($ifrom, $ito);
    }
    
    public function __construct($ifrom, $ito) {
        $this->i_from = $ifrom;
        $this->i_to = $ito;
    }
    
    // ini nih fungsi utama
    public function start() {
        for ($i = $this->i_from; $i <= $this->i_to; $i++) {
            $this->crawl_page($this->base_url . $i);
        }
    }
    
    // untuk 1 page
    public function crawl_page($url) {
        // crawl_page
        $c = new Crawler($url);
		// get title
		$c->go_to('<title>');
		$title = Crawler::extract($c->curline, 'PHD Comics: ', '</title>');
		$title = preg_replace('/\\W/', '_', $title);
        // get the date
        $c->go_to('date_left.gif');
        $c->readline(2);
        $line = $c->curline;
        preg_match('/([0-9]+)\\/([0-9]+)\\/([0-9]+)/mi', $line, $matches);
        //print_r($matches);flush();
        list($full, $month, $date, $year) = $matches;
        if (strlen($date) < 2) $date = '0'.$date;
        if (strlen($month) < 2) $month = '0'.$month;
        $fileprefix = "{$year}_{$month}_{$date}_{$title}";
        // get the img url
        $c->go2linewhere('<td bgcolor=#FFFFFF');
        $line = $c->curline;
        preg_match('/<img src=["\']?([^ ]+)["\']?/i', $line, $matches);
        $img = $matches[1];
        $filename = basename($img);
        $ext = substr($filename, strrpos($filename, '.'));
        echo "<a href='$img'>".$fileprefix.$ext."</a><br/>";
        flush();
        $c->close();
        unset($c);
    }
}

?>
<html>
<body>
	<form action="" method="post">
		From: <input type="text" name="ifrom" value="<?=isset($ifrom)?$ifrom:''?>" /><br/>
		To: <input type="text" name="ito" value="<?=isset($ito)?$ito:''?>" /><br/>
		<input type="submit" value="Submit" />
	</form>
<?php
if (isset($ifrom) && isset($ito)) {
    Phdcomics_Crawler::factory($ifrom, $ito)->start();
}
?>
</body>
</html>