<html>
	<body>
<?php
require_once('crawler.php');
if ($_POST) {
	foreach ($_POST as $key => $value) {
		$$key = $value;
	}
	$craw = new Crawler($starting);
	//ada 1 baris yg berisi seluruh informasi
	while (!feof($craw->stream)) {
		$craw->readline();
		echo $craw->curline;
	}
	//pertama ambil link2 ke semua halaman
	//lalu loop
}
/*
public function get_AA() {
	return $this->db->select('HOUR(start_time) as jam, MINUTE(start_time) as menit, SECOND(start_time) as detik')->where('id', $this->id)->get('restaurants');
}
*/

//http://allaboutidol.blogspot.com/2007/09/yukie-nakama-gallery-1.html
?>
		<form method="post" action="">
			<input type="text" name="starting" value="<?=isset($starting)?$starting:''?>" />
			<input type="submit" name="btn_submit" />
		</form>
		<?php
			echo $craw->curline;
	$craw->close();

		?>
	</body>
</html>