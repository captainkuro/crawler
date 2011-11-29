<?php
/**
 * My Personal Scraper/Crawler
 * http://pastebin.com/fHe7Wwys
 */
class Crawler {
	public $stream;
	public $curline;
	public $url;
	public static $use_proxy = false;
	public static $proxy = array(
		'name' => 'proxy.mydomain.de',  // ganti nih
		'port' => 8080, // ganti nih
		'user' => "",   // ganti nih
		'pass' => "",   // ganti nih
	);
	
	public function __destruct() {
		//@fclose($this->stream);
	}
	
	public function eof() {
		return feof($this->stream);
	}
	
	public function close() {
		@fclose($this->stream);
	}
	
	public function readline($n = 1) {
		$ret = $this->curline;
		if ($this->stream)
			while ($n--) {
				$this->curline = fgets($this->stream);
			}
		return $ret;
	}
	
	public function strpos($find, $offset = 0) {
		return strpos($this->curline, $find, $offset);
	}
	
	public function strrpos($find, $offset = 0) {
		return strrpos($this->curline, $find, $offset);
	}
	
	public function go2linewhere($where) {
		$this->curline = fgets($this->stream);
		while (strpos($this->curline, $where) === false && !feof($this->stream)) {
			$this->curline = fgets($this->stream);//echo htmlentities($this->curline);flush();
		}
	}
	
	public function go2lineregex($reg, $c = 0) {
		$this->curline = fgets($this->stream);
		if (!$c) {
			while (preg_match($reg, $this->curline, $a) === 0 && !feof($this->stream)) {
				$this->curline = fgets($this->stream);
			}
		} else {
			while (preg_match_all($reg, $this->curline, $a) != $c && !feof($this->stream)) {
				$this->curline = fgets($this->stream);
			}
		}
	}
	
	//sementara saja ini
	public function go2lineregexor($regs) {
		do {
			$this->curline = fgets($this->stream);
		} while (Crawler::checkregexes($this->curline, $regs) === false && !$this->eof());
	}
	
	public function go2lineor($wheres) {
		$this->curline = fgets($this->stream);
		while (Crawler::checkposes($this->curline, $wheres) === false && !feof($this->stream)) {
			$this->curline = fgets($this->stream);
		}
	}
	
	public function getbetween($begin, $end = null) {
		return Crawler::extract($this->curline, $begin, $end);
	}
	
	public function getbetweenlast($begin, $end) {
		return Crawler::extractlast($this->curline, $begin, $end);
	}
	
	public function parse_http_header() {
		$header = array();
		while ($header[] = trim($this->readline())) {
		}
	}
	
	public function getalllineswhere($str) {
		$h = array();
		while (!feof($this->stream)) {
			$this->go2linewhere($str);
			if (strpos($this->curline, $str) !== false)
				$h[] = $this->curline;
		}
		return $h;
	}
	
	/* STATIC FUNCTIONS: */
	
	public static function checkposes($line, $wheres) {
		foreach ($wheres as $where) {
			if (strpos($line, $where) !== false) return true;
		}
		return false;
	}
	
	public static function checkregexes($line, $regs) {
		foreach ($regs as $reg) {
			if (preg_match($reg, $line) === 1) return true;
		}
		return false;
	}
	
	public static function cutfrom($line, $string) {
		$pos = strpos($line, $string);
		return substr($line, $pos);
	}

	public static function cutfrom1($line, $string) {
		$pos = strpos($line, $string);
		return substr($line, $pos+strlen($string));
	}
	
	public static function cutfromlast($line, $string) {
		$pos = strrpos($line, $string);
		return substr($line, $pos);
	}
	
	public static function cutfromlast1($line, $string) {
		$pos = strrpos($line, $string);
		return substr($line, $pos+1);
	}
	
	public static function cutafter($line, $string) {
		$pos = strpos($line, $string);
		return substr($line, $pos+strlen($string));
	}
	
	public static function cutafterlast($line, $string) {
		$pos = strrpos($line, $string);
		return substr($line, $pos+strlen($string));
	}
	
	public static function cutuntil($line, $string) {
		$pos = strpos($line, $string);
		return substr($line, 0, $pos);
	}
	
	public static function cutuntillast($line, $string) {
		$pos = strrpos($line, $string);
		return substr($line, 0, $pos);
	}
	
	public static function extract($line, $begin, $end=null) {
		//if (!isset($end)) $end = $begin;
		$string1 = Crawler::cutafter($line, $begin);
		if (isset($end))
			$string1 = Crawler::cutuntil($string1, $end);
		return $string1;
	}
	
	public static function extractlast($line, $begin, $end=null) {
		if (!isset($end)) $end = $begin;
		$string1 = Crawler::cutafterlast($line, $begin);
		$string1 = Crawler::cutuntil($string1, $end);
		return $string1;
	}
	
	public static function makennumber($number, $n) {
		return Crawler::n($number, $n);
	}
	
	public static function site_name($full) {
		return Crawler::extract($full, 'http://', '/');
	}
	
	public static function back_url($full) {
		return Crawler::cutafter($full, Crawler::site_name($full));
	}
	
	//format: 1(1-4)+2(5-6)
	//atau: (10-20)
	public static function pecahkan_format($formt) {
		$ar1 = explode('+', $formt);
		$ar2 = array();
		foreach ($ar1 as $one) {
			$pos1 = strpos($one, '(');
			$vol = substr($one, 0, $pos1);
			$chap_range = substr($one, $pos1+1, strrpos($one, ')')-$pos1-1);
			//list($start_chap, $end_chap) = explode('-', $chap_range);
			$ar2[$vol] = explode('-', $chap_range);
		}
		return $ar2;
	}

	//make N digits
	public static function n($num, $l) {
		while (strlen($num) < $l) {
			$num = '0'.$num;
		}
		return $num;
	}
	
	public static function parse_url($url, $component = -1) {
		$hasil = parse_url($url, $component);
		$hasil['filename'] = basename($hasil['path']);
		$hasil['extension'] = Crawler::cutfromlast($hasil['filename'], '.');
		$hasil['filename_noext'] = Crawler::cutuntillast($hasil['filename'], '.');
		return $hasil;
	}
	
	public static function is_image($str) {
		return preg_match('/.jpg$/', $str) || preg_match('/.png$/', $str)
			|| preg_match('/.jpeg$/', $str) || preg_match('/.gif$/', $str);
	}
	
	public static function xml_file($url, $desc = '', $refer = '') {
		$filename = basename($url);
		return '
		<file name="'.$filename.'" a0:num="322" a0:startDate="1272698032560" a0:referrer="'.$refer.'">
			<description>'.$desc.'</description>
			<resources>
				<url type="http" preference="100" a0:usable="'.$url.'">
					'.$url.'
				</url>
			</resources>
		</file>';
	}
	
	public static function xmlize($array, $refer) {
		$xml = '<?xml version="1.0"?>
<metalink xmlns="http://www.metalinker.org/" type="static" version="3.0" generator="DownThemAll! 1.1 &lt;http://downthemall.net/&gt;" a0:version="1.1.9" xmlns:a0="http://www.downthemall.net/properties#" pubdate="Sat, 01 May 2010 07:14:14 GMT"><!--metalink as exported by DownThemAll!
may contain DownThemAll! specific information in the DownThemAll! namespace: http://www.downthemall.net/properties#-->'.
			'<files>';
		foreach ($array as $desc => $url) {
			$xml .= Crawler::xml_file($url, $desc, $refer);
		}
		$xml .= '</files></metalink>';
		return $xml;
	}
	
	public static function download($string, $filename) {
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-Disposition: attachment; filename=$filename");
		header("Content-Type: application/zip");
		header("Content-Transfer-Encoding: binary");
		echo $string;
	}
	
	// --- BETTER FUNCTION NAMES --- //
	public function go_to($search, $op = 'OR', $regex = false) {
		if (is_array($search)) {
			switch ($op) {
				case 'OR': 
					if ($regex) $this->go2lineregexor($search); else $this->go2lineor($search);
					break;
				case 'AND': 
					// belum ada
					break;
			}
		} else {
			if ($regex) $this->go2lineregex($search); else $this->go2linewhere($search);
		}
	}
	
	/**
	 * $n: number to be padded
	 * $l: goal length
	 * $ic: is chapter? if true then "305.5",4 will become "0305.5"
	 */
	public static function pad($n, $l, $ic = true) {
		if ($ic) {
			if (strpos($n, '.') !== false) {
				$temp = Crawler::cutuntil($n, '.');
				return Crawler::n($temp, $l) . Crawler::cutfrom($n, '.');
			} else {
				return Crawler::n($n, $l);
			}
		} else {
			return Crawler::n($n, $l);
		}
	}
	
	// change 'asdf_1.jpg' to 'asdf_01.jpg'
	public static function fix_filename($fname) {
		return preg_replace('/(\\D)(\\d){1}\\./', '${1}0${2}.', $fname);
	}
	
	public static function is_there($line, $part) {
		return strpos($line, $part) !== false;
	}
	
	public static function extract_to_array($string, $from = null, $to = null) {
		$ledak = explode($from, $string);
		$n = count($ledak);
		$temp = array();
		for ($i=1; $i<$n; ++$i) {
			$temp[] = Crawler::cutuntil($ledak[$i], $to);
		}
		return $temp;
	}
	
	public static function explore($url, $start_sign = '<a href="', $end_sign = '"', $n_skip = 5) {
		echo "Currently $url\n";flush();
		$s = file_get_contents($url);
		$r = array();
		$l = Crawler::extract_to_array($s, $start_sign, $end_sign);
		$n = count($l);
		for ($i=$n_skip; $i<$n; $i++) {
			if (strrpos($l[$i], '/') == (strlen($l[$i])-1)) {
				$r[$l[$i]] = Crawler::explore($url . $l[$i], $start_sign, $end_sign, $n_skip);
			} else {
				$r[$l[$i]] = $l[$i];
			}
		}
		return $r;
	}
	
	public static function url_encode($url) {
		$r = explode('/', $url);
		$r = array_map('rawurlencode', $r);
		return implode('/', $r);
	}
	
	/*** CURL MULTITHREAD ***/
	public static function addHandle(&$curlHandle,$url) {
		$cURL = curl_init();
		curl_setopt($cURL, CURLOPT_URL, $url);
		curl_setopt($cURL, CURLOPT_HEADER, 0);
		curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($cURL, CURLOPT_BINARYTRANSFER, 1);
		curl_multi_add_handle($curlHandle,$cURL);
		return $cURL;
	}
	
	public static function execHandle(&$curlHandle) {
		/* yg ini bikin 100% CPU
		$flag=null;
		do {
			//fetch pages in parallel
			curl_multi_exec($curlHandle,$flag);
		} while ($flag > 0);
		*/
		$active = null;
		//execute the handles
		do {
			$mrc = curl_multi_exec($curlHandle, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);

		while ($active && $mrc == CURLM_OK) {
			if (curl_multi_select($curlHandle) != -1) {
				do {
					$mrc = curl_multi_exec($curlHandle, $active);
				} while ($mrc == CURLM_CALL_MULTI_PERFORM);
			}
		}
	}
	
	/**
	 * Mendownload $size buah link sekaligus, lalu diproses oleh fungsi $function
	 * @param integer $size jumlah thread yg diinginkan
	 * @param array   $pages_url array of full url
	 * @param string  $function nama fungsi yang dipanggil untuk memproses 1 halaman,
	 *                  fungsinya punya 2 parameter (resource $fil, string $url)
	 * @param array   $params parameter tambahan ke fungsi
	 */
	public static function multiProcess($size, $pages_url, $function, $params = false) {
		$n = 0;
		$curlHandle = null;
		$curlList = array();
		foreach ($pages_url as $aurl) {
			if ($n == 0) {
				$curlHandle = curl_multi_init();
			}
			$curlList[$aurl] = Crawler::addHandle($curlHandle, $aurl);
			$n++;
			if ($n >= $size) {
				Crawler::execHandle($curlHandle);
				foreach ($curlList as $theurl => $curlEl) {
					$html = curl_multi_getcontent($curlEl);
					// Ada kemungkinan gagal retrieve
					if (trim($html)) {
						$fil = tmpfile();
						fwrite($fil, $html);
					} 
					// In that case, Crawler must retrieve the HTML by itself
					else {
						$fil = $theurl;
					}
					if ($params) {
						call_user_func_array($function, array_merge(array($fil, $theurl), $params));
					} else {
						$function($fil, $theurl);
					}
					curl_multi_remove_handle($curlHandle, $curlEl);
				}
				curl_multi_close($curlHandle);
				$n = 0;
				$curlList = array();
			}
		}
		if ($curlList) {
			Crawler::execHandle($curlHandle);
			foreach ($curlList as $theurl => $curlEl) {
				$html = curl_multi_getcontent($curlEl);
				// Ada kemungkinan gagal retrieve
				if (trim($html)) {
					$fil = tmpfile();
					fwrite($fil, $html);
				} 
				// In that case, Crawler must retrieve the HTML by itself
				else {
					$fil = $theurl;
				}
				if ($params) {
					call_user_func_array($function, array_merge(array($fil, $theurl), $params));
				} else {
					$function($fil, $theurl);
				}
				curl_multi_remove_handle($curlHandle, $curlEl);
			}
			curl_multi_close($curlHandle);
		}
	}

	public function __construct($url, $opts = false, $post_data = false) {
		$this->url = $url;
		$retry = 0;
		
		if (Crawler::$use_proxy) {
			$proxy_name = Crawler::$proxy['name'];
			$proxy_port = Crawler::$proxy['port'];
			$proxy_user = Crawler::$proxy['user'];
			$proxy_pass = Crawler::$proxy['pass'];
			$request_url = $url;

			$proxy_fp = @fsockopen($proxy_name, $proxy_port);
			if (!$proxy_fp)
				throw new Exception('Gagal konek at '.$url);
			@fputs($proxy_fp, "GET $request_url HTTP/1.0\r\nHost: $proxy_name\r\n");
			@fputs($proxy_fp, "Proxy-Authorization: Basic ". base64_encode ("$proxy_user:$proxy_pass")."\r\n\r\n"); 
			$this->stream = $proxy_fp;
		} else if (is_resource($url)) {
			fseek($url, 0);
			$this->stream = $url;
		} else if ($opts && $opts['use_curl']) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_ENCODING, "");
			curl_setopt($ch, CURLOPT_TIMEOUT, 150);
			if (@$opts['cookie']) curl_setopt($ch, CURLOPT_COOKIE, $opts['cookie']);
			if (@$opts['referrer']) curl_setopt($ch, CURLOPT_REFERER, $opts['referrer']);
			if (@$opts['agent']) curl_setopt($ch, CURLOPT_USERAGENT, $opts['agent']);
			$fil = tmpfile();
			$asdf = curl_exec($ch);
			//print_r($asdf);
			//file_put_contents('gehentai-gelo.txt', "URL:$url\n$asdf\n\n", FILE_APPEND);
			fwrite($fil, $asdf);
			curl_close($ch);
			fseek($fil, 0);
			$this->stream = $fil;
		} else if ($opts || $post_data) {
			$host = Crawler::site_name($url);
			$url = Crawler::back_url($url);
			$out = '';
			if ($post_data) {
				$out .= "POST $url HTTP/1.1\r\n";
			} else {
				$out .= "GET $url HTTP/1.1\r\n";
			}
			$out .= "Host: $host\r\n".
				"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 (.NET CLR 3.5.30729)\r\n".
				"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*"."/"."*;q=0.8\r\n".
				"Accept-Language: en-us,en;q=0.5\r\n".
				"Accept-Encoding: gzip,deflate\r\n".
				"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n".
				"Keep-Alive: 3000\r\n";
			if (is_array($opts) && count($opts)) foreach ($opts as $key => $value) {
				$out .= "$key: $value\r\n";
			}
			if ($post_data) {
				$out .= "Content-Type: application/x-www-form-urlencoded\r\n".
					"Content-Length: ".strlen($post_data)."\r\n".
					"\r\n".
					$post_data;
			} else {
				$out .= "\r\n";
			}
			$this->stream = @fsockopen($host, 80, $errno, $errstr, 150);
			if (!$this->stream) die('Gagal konek at '.$url);
			//echo '<pre>'.$out.'</pre>';
			@fwrite($this->stream, $out);
		} else {
			$this->stream = @fopen($url, 'r');
			while (!$this->stream && $retry++ < 30) {$this->stream = @fopen($url, 'r');}
			if (!$this->stream) {
				throw new Exception('Gagal konek at '.$url);
			}
		}
		$this->readline();
		//echo htmlentities($this->curline);flush();
	}
}
ob_implicit_flush(true);

/*
/// TEST MULTITHREAD
$list = array(
	'a\\b\\01.jpg' => 'http://disneycomics.free.fr/Ducks/Barks/1942/W_OS_9-02/01.jpg',
	'a\\b\\02.jpg' => 'http://disneycomics.free.fr/Ducks/Barks/1942/W_OS_9-02/02.jpg',
	'a\\b\\03.jpg' => 'http://disneycomics.free.fr/Ducks/Barks/1942/W_OS_9-02/03.jpg',
	'a\\b\\04.jpg' => 'http://disneycomics.free.fr/Ducks/Barks/1942/W_OS_9-02/04.jpg',
	'a\\b\\05.jpg' => 'http://disneycomics.free.fr/Ducks/Barks/1942/W_OS_9-02/05.jpg',
	'a\\b\\06.jpg' => 'http://disneycomics.free.fr/Ducks/Barks/1942/W_OS_9-02/06.jpg',
	'a\\b\\07.jpg' => 'http://disneycomics.free.fr/Ducks/Barks/1942/W_OS_9-02/07.jpg',
	'a\\b\\08.jpg' => 'http://disneycomics.free.fr/Ducks/Barks/1942/W_OS_9-02/08.jpg',
	'a\\b\\09.jpg' => 'http://disneycomics.free.fr/Ducks/Barks/1942/W_OS_9-02/09.jpg',
	'a\\b\\10.jpg' => 'http://disneycomics.free.fr/Ducks/Barks/1942/W_OS_9-02/10.jpg',
	'a\\b\\11.jpg' => 'http://disneycomics.free.fr/Ducks/Barks/1942/W_OS_9-02/11.jpg',
	'a\\b\\12.jpg' => 'http://disneycomics.free.fr/Ducks/Barks/1942/W_OS_9-02/12.jpg',
	'a\\b\\13.jpg' => 'http://disneycomics.free.fr/Ducks/Barks/1942/W_OS_9-02/13.jpg',
	'a\\b\\14.jpg' => 'http://disneycomics.free.fr/Ducks/Barks/1942/W_OS_9-02/14.jpg',
	'a\\b\\15.jpg' => 'http://disneycomics.free.fr/Ducks/Barks/1942/W_OS_9-02/15.jpg',
	'a\\b\\16.jpg' => 'http://disneycomics.free.fr/Ducks/Barks/1942/W_OS_9-02/16.jpg',
	'a\\b\\17.jpg' => 'http://disneycomics.free.fr/Ducks/Barks/1942/W_OS_9-02/17.jpg',
);

$n = 0;
$size = 3;// mau berapa concurrent download at a time
$curlHandle = null;
$curlList = array();
foreach ($list as $output => $url) {
	if ($n == 0) {
		$curlHandle = curl_multi_init();
	}
	$curlList[$output] = Crawler::addHandle($curlHandle, $url);
	$n++;
	if ($n >= $size) {
		Crawler::execHandle($curlHandle);
		foreach ($curlList as $outfile => $curlEl) {
			exec("mkdir \"".dirname($outfile)."\\\"");
			file_put_contents($outfile, curl_multi_getcontent($curlEl));
			curl_multi_remove_handle($curlHandle, $curlEl);
		}
		curl_multi_close($curlHandle);
		$n = 0;
		$curlList = array();
	}
}
// the last call
Crawler::execHandle($curlHandle);
foreach ($curlList as $outfile => $curlEl) {
	exec("mkdir \"".dirname($outfile)."\\\"");
	file_put_contents($outfile, curl_multi_getcontent($curlEl));
	curl_multi_remove_handle($curlHandle, $curlEl);
}
curl_multi_close($curlHandle);
*/

// --- Driver fix_filename() --- //
/*
$names = array(
	'asdf-1.jpg',
	'blabla-21.jpg',
	'kekk_10_5.png',
	'kdofioi-100-01.llp',
	'kdofioi-100-1.llp',
);
foreach ($names as $name) {
	echo Crawler::fix_filename($name) . "\n";
}
*/