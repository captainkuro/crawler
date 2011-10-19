<?php
extract($_POST);
?>
<html>
<head>
<style type="text/css">
	body{font-family:verdana;font-size:12px;padding:15px;background-color:#757C8C}
	#content {width:800px; margin:0 auto; background-color:#181F29;color:#FFF;}
	#resultbox {padding:20px}
	table {padding:10px; margin:10px; background-color:#C7CBD4; color:#000;font-family:verdana; font-size:11px;}
	input {width:175px}
	.button{background-color:#363D45;border:1px solid #FFF;color:#FFF;font-weight:bold;font-family:verdana;font-size:11px;width:100%;height:25px}
	.howto{float:left;background-color:#757C8C;padding:10px 10px 10px 10px;margin:10px;font-size:10px; width:400px}
	.about{padding:4px;text-align:center;height:14px;background-color:#0E67A1;color:#FFF;font-family:verdana;font-size:12px;font-weight:bold}
	#footer{font-family:padding:10px;margin:10px;verdana;font-size:11px;width:750px;margin:0 auto}
	.links a{font-family:verdana;font-size:12px;color:#CCCCCC}
</style>

</head>
<body>

<div id="content"> 
	<div style="float:left">
	<form method="POST" action="">
		<table align="center">
		<tr>
		<td width="100px">Folder URL </td><td width="10px">:</td><td><input type="text" name="base" value="<?=$base;?>" /></td>
		</tr>
		<tr>
		<td>From Chapter</td><td>:</td><td><input type="text" name="start" value="<?=$start;?>" /></td>
		</tr>
		<tr>
		<td>Until Chapter</td><td>:</td><td><input type="text" name="finish" value="<?=$finish;?>" /></td>
		</td></tr>
		<tr>
		<td>Prefix</td><td>:</td><td><input type="text" name="prefix" value="<?=$prefix;?>" /><td>
		</tr>
		<tr><td colspan="3">
			<div style="text-align:center; padding:10px"><input type="submit" class="button" value="GO!" /></div>
		</td></tr>
		</table>
	</form>
	</div>
	<div class="howto">
		<div class="about">OneManga's Leecher by Khandar William</div>
		<br />How To :<br /><br />
		Assume you wanna to download Pluto Manga from Chapter 55 to 56, <br />
		1. Fill the Folder URL with http://www.onemanga.com/Pluto/<br />
		2. Start from 55<br />
		3. Until 56<br />
		4. Prefix to give prefix to your downloaded page<br />
		5. Submit<br />
		6. Wait until this page generate all links to pages<br />
		7. Download all using DownThemAll (recommended)<br />
	</div>
	<div style="clear:both"></div>
	
	<div id="resultbox">
<?
$start = $_POST['start'];
$finish  = $_POST['finish'];
$base = $_POST['base'];
$prefix = $_POST['prefix'];
$sitename = "http://www.onemanga.com";

if (isset($base)) {
	for ($i = $start; $i<=$finish; $i++) {
		echo 'Chapter', $i, ":<br />\n";
		$url = $base.$i.'/';
		echo 'URL:',$url,'<br />';
		$fil = fopen($url, 'r');
		//if (!$fil) {
		//	echo 'gagal buka', $url, '<br />', "\n";
		//} else {
		while (!$fil) {$fil = fopen($url, 'r');}
		$phase0 = false;
		while (!$phase0 && !feof($fil)) {
			$line = fgets($fil);
			if ($pos = strpos($line, 'Begin reading')) {
				$phase0 = true;
			}
		}
		
		if ($phase0) {
			fclose($fil);
			$pos = strpos($line, '"');
			$potong = substr($line, $pos+1);
			$pos2 = strpos($potong, '"');
			$potong = substr($potong, 0, $pos2);
			$url = $sitename.$potong;
			echo 'URL:',$url,'<br />';
			
			$fil = fopen($url, 'r');
			while (!$fil) {$fil = fopen($url, 'r');}
			
			$phase1 = false;
			while (!$phase1 && !feof($fil)) {
				$line = fgets($fil);
				if ($pos = strpos($line, '<select name="page" id="id_page_select" class="page-select"')) {
					$phase1 = true;
				}
			}
			
			if ($phase1) {
				$phase2 = false;
				$count = 0;
				$array = array('');
				while (!$phase2 && !feof($fil)) {
					if (strpos($line, '<option') !== false) {
                        $pecah = explode('<option value="', $line);
                        foreach ($pecah as $baris) {
                            if (!$baris) continue;
                            $pos = strpos($baris, '"');
                            $potong = substr($baris, 0, $pos);
                            $array[$count] = $potong;
                            $count++;
                        }
                        /*
						$pos = strpos($line, '"');
						$potong = substr($line, $pos+1);
						$pos2 = strpos($potong, '"');
						$potong = substr($potong, 0, $pos2);
						$array[$count] = $potong;
						$count++;
                            */
					} else if (strpos($line, '</select>')) {
						$phase2 = true;
					}
					$line = fgets($fil);
				}
				
				if ($phase2) {
					$selesai = false;
					while (!$selesai && !feof($fil)) {
						if (strpos($line, '<img class="manga-page" src=')) {
							$pos = strpos($line, 'http://');
							$potong = substr($line, $pos);
							$pos2 = strpos($potong, '"');
							$potong = substr($potong, 0, $pos2);
							$hal = basename($potong);
							$pos = strpos($potong, $hal);
							
							$pageurl = substr($potong, 0, $pos);
							$pos2 = strpos($hal, '.');
							$ext = substr($hal, $pos2+1);
							$selesai = true;
						}
						$line = fgets($fil);
					}
					
					if ($selesai) {
						fclose($fil);
						$n = count($array);
						$chap = $i;
						
						for ($j=strlen($i); $j<3; $j++) {
							$chap = '0'.$chap;
						}
						for ($j=0; $j<$n; $j++) {
							echo '<a href="';
							echo $pageurl,$array[$j],'.',$ext;
							echo '">', $prefix, '-', $chap, '-', $array[$j], '.',$ext, '</a><br />', "\n";
						}
						flush();
					}
				}
				
			}
		}
	}
}
?>
</div>
	<div id="footer"><strong>OneManga's Leecher by Khandar William</strong><br/><span style="font-size:10px">css-ed by Dominikus D Putranto</span><br/></div>
</div>
</body>
</html>