<?php
// logout
fopen('http://login.melsahotspot.com/logout', 'r');

// login
/*
https://login.melsahotspot.com/login

POST /login HTTP/1.1
Host: login.melsahotspot.com
User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 ( .NET CLR 3.5.30729)
Accept: text/html,application/xhtml+xml,application/xml;q=0.9,* /*;q=0.8
Accept-Language: en-us,en;q=0.5
Accept-Encoding: gzip,deflate
Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7
Keep-Alive: 115
Connection: keep-alive
Referer: https://login.melsahotspot.com/login
Content-Type: application/x-www-form-urlencoded
Content-Length: 76
dst=&popup=true&username=$username&password=$password
HTTP/1.1 200 OK
Cache-Control: no-cache
Connection: Keep-Alive
Content-Length: 1239
Content-Type: text/html
Date: Tue, 25 May 2010 13:23:26 GMT
Expires: 0
*/
//http://peopleschoice.imaginecup.com/ClientBin/isvData.ashx
$username = 'captain_kuro%40melsahotspot.com';
$password = 'fr33p4sc4l';
$content = "dst=&popup=true&username={$username}&password={$password}";
$length = strlen($content);

$out = '';
$out .= "POST /login HTTP/1.1\r\n";
$out .= "Host: login.melsahotspot.com\r\n";
$out .= "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 ( .NET CLR 3.5.30729)\r\n";
$out .= "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n";
$out .= "Accept-Language: en-us,en;q=0.5\r\n";
$out .= "Accept-Encoding: gzip,deflate\r\n";
$out .= "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n";
$out .= "Keep-Alive: 115\r\n";
$out .= "Connection: keep-alive\r\n";
$out .= "Referer: https://login.melsahotspot.com/login\r\n";
$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
$out .= "Content-Length: {$length}\r\n";
$out .= "\r\n";
$out .= $content;

for ($i=1; true; ) {
	$fil = fopen('http://login.melsahotspot.com/logout', 'r');
	fclose($fil);
	sleep(1);
	$fil = @fsockopen('ssl://login.melsahotspot.com', 443, $errno, $errstr, 30);
	if ($fil) {
		fwrite($fil, $out);
		
		$line2 = '';
		$content_length = 0;
		echo $i++,":";
		while ($line2 = fgets($fil)) {
		//	$line = $line2;
			//echo $line2;flush();
			/*
			if (strpos($line2, 'Content-Length') !== false) {
				$content_length = (int)substr($line2, 16, 1);
			} else if (strpos($line2, 'Connection:') !== false) {
				fgets($fil);
				break;
			}
			*/
		}
		if ($content_length)
			echo $line2 = fgets($fil, $content_length+1);
		echo "\t";
		
		fclose($fil);
		//echo $i++,':',$line,' ';
	} else {
		echo 'GAGAL KONEK ';
	}
	flush();
	sleep(25*60);
}