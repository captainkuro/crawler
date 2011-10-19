<?php
//require_once '../crawler.php';

class Spider_Fakku {
	public $url;
	
	public function __construct($url) {
		$this->url = $url;
	}
	
	public function go() {
		// http://www.fakku.net/viewonline.php?id=2589
		// pake curl
		$base = 'http://www.fakku.net';
		$this->url = str_replace('viewmanga.php', 'viewonline.php', $this->url);
		/*
		$ch = curl_init($this->url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		file_put_contents('fakku.temp', curl_exec($ch));
		curl_close($ch);
		*/
		$craw = new Crawler($this->url, array('use_curl' => true, 'agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13'));
		//print_r($craw);
        $craw->go_to('var data = {');
		//print_r($craw->curline);
        $json = Crawler::extract($craw->curline, ' = ', ';');
        $obj = json_decode($json);
		//print_r($obj);
        $craw->go_to('var mirror = ');
        $mirror = $craw->getbetween("'", "'");
        $craw->go_to('var mirror = ');
        $mirror2 = $craw->getbetween("'", "'");
		if ($mirror2) $mirror = $mirror2;
        $craw->close();
        foreach ($obj->thumbs as $key => $val) {
            $filename = Crawler::pad($key+1, 3) . '.jpg';
            $img = $mirror . '/' . $obj->meta->dir . 'images/' . $filename;
			$text = basename($obj->meta->dir);
            echo "<a href='$img'>$text</a><br/>\n";flush();
        }
	}
}