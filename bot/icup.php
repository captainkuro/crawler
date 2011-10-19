<?
//bot imagine cup
 //reference
// POST /ClientBin/isvData.ashx HTTP/1.1
// Host: peopleschoice.imaginecup.com
// User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.1) Gecko/20090624 Firefox/3.5
// Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8
// Accept-Language: en-us,en;q=0.5
// Accept-Encoding: gzip,deflate
// Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7
// Keep-Alive: 300
// Connection: keep-alive
// Content-Length: 111
// Content-Type: text/xml

// <Data><Mode>SubmitVote</Mode><MediaFile>Team_BigBang_presentation.wmv</MediaFile><Title>Big Bang</Title></Data>

//http://peopleschoice.imaginecup.com/ClientBin/isvData.ashx

$out = '';
$out .= "POST /ClientBin/isvData.ashx HTTP/1.1\r\n";
$out .= "Host: peopleschoice.imaginecup.com\r\n";
$out .= "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.1) Gecko/20090624 Firefox/3.5\r\n";
$out .= "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n";
$out .= "Accept-Language: en-us,en;q=0.5\r\n";
$out .= "Accept-Encoding: gzip,deflate\r\n";
$out .= "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n";
$out .= "Keep-Alive: 300\r\n";
$out .= "Connection: keep-alive\r\n";
$out .= "Content-Length: 111\r\n";
$out .= "Content-Type: text/xml\r\n";
$out .= "\r\n";
$out .= "<Data><Mode>SubmitVote</Mode><MediaFile>Team_BigBang_presentation.wmv</MediaFile><Title>Big Bang</Title></Data>";

for ($i=1; true; ) {
	$fil = @fsockopen('peopleschoice.imaginecup.com', 80, $errno, $errstr, 10);
	if ($fil) {
		fwrite($fil, $out);
		$line2 = '';
		$content_length = 0;
		echo $i++,":";
		while ($line2 = fgets($fil)) {
		//	$line = $line2;
			//echo $line2;flush();
			if (strpos($line2, 'Content-Length') !== false) {
				$content_length = (int)substr($line2, 16, 1);
			} else if (strpos($line2, 'Connection:') !== false) {
				fgets($fil);
				break;
			}
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
	//sleep(2);
}