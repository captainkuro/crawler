<?php
require_once "crawler.php";
extract($_POST);

class Moko_Crawler {
    private $start_url;
    private $base_url = 'http://www.moko.cc';
    
    public static function factory($start) {
        return new Moko_Crawler($start);
    }
    
    public function __construct($start) {
        $this->start_url = $start;
    }
    
    // ini nih fungsi utama
    public function start() {
        $masih = true;
        $links = array();
        while ($masih) {
            $c = new Crawler($this->start_url, true);
            while ($line = $c->readline()) {
                if (preg_match('/mainDashedOn/i', $line)) {
                    $line = $c->readline();
                    preg_match('/<a [^>]*href="([^"]+)">([^<]+)<\\/a>/i', $line, $matches);
                    $link = $matches[1];
                    $text = $matches[2];
                    $text = basename($link);
                    $links[$text] = $link;
                } else if (preg_match('/pagination/i', $line)) {
                    if (preg_match('/<a class="down l" href="([^"]+)">/i', $line, $matches)) {
                        $next = $matches[1];
                        $this->start_url = $this->base_url . $next;
                    } else {
                        $masih = false;
                    }
                    //print_r($links);flush();    //debug
                    break;
                }
            }
            $c->close();
            unset($c);
        }
        //print_r($links);flush();    //debug
        foreach ($links as $text => $url) {
            $this->crawl_page($this->base_url . $url, $text);
        }
    }
    
    // ternyata index pagenya ada versi lain
    public function start2() {
        $masih = true;
        $links = array();
        $date = '';
        while ($masih) {
            $c = new Crawler($this->start_url, true);
            while ($line = $c->readline()) {
                if (preg_match('/([0-9]{2}\\/[0-9]{2}\\/[0-9]{2})/i', $line, $matches)) {
                    $date = str_replace('/', '-', $matches[1]);
                } else if (preg_match('/READ MORE/i', $line)) {
                    preg_match('/href=\'([^\']+)\'/i', $line, $matches);
                    $link = $matches[1];
                    //$text = basename($link);
                    $text = $date;
                    $links[$text] = $link;
                } else if (preg_match('/pagination/i', $line)) {
                    if (preg_match('/<a class="down l" href="([^"]+)">/i', $line, $matches)) {
                        $next = $matches[1];
                        $this->start_url = $this->base_url . $next;
                    } else {
                        $masih = false;
                    }
                    //print_r($links);flush();    //debug
                    break;
                }
            }
            $c->close();
            unset($c);
        }
        print_r($links);flush();    //debug
        foreach ($links as $text => $url) {
            $this->crawl_page($this->base_url . $url, $text);
        }
    }
    
    // crawl 1 page
    public function crawl_page($url, $text) {
        echo "Entering '$url'<br/>\n";flush();
        $c = new Crawler($url, true);
        $dah_gambar = false;
        $i = 1;
        while ($line = $c->readline()) {
            if (preg_match('/pic dashedOn/i', $line)) {
                $dah_gambar = true;
                $line = $c->readline();
                preg_match('/<img src="([^"]+)"/i', $line, $matches);
                $img = $matches[1];
                $tempi = Crawler::n($i++, 3) . substr($img, strrpos($img, '.'));
                echo "<a href='$img'>$text</a><br/>\n";flush();
            } else if (preg_match('/commentButton/i', $line) && $dah_gambar) {
                break;
            }
        }
        $c->close();
        unset($c);
    }
}
?>
<html>
<body>
	<form action="" method="post">
		Starting URL: <input type="text" name="start_url" value="<?=isset($start_url)?$start_url:''?>" />
		<input type="submit" value="Submit" />
	</form>
<?php
if (isset($start_url)) {
    Moko_Crawler::factory($start_url)->start2();
}
?>
</body>
</html>