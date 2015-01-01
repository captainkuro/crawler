<?php

require_once 'KomikJakartaAbstract.php';

class KomikJakartaLala_Downloader extends KomikJakartaAbstract {
	protected $dir = '/home/khandar-gdp/tmp/lala/';
	protected $home = 'http://komikjakarta.com/komik-lala/';

	public function display() {
		return 'Komik Jakarta - Lala';
	}
}