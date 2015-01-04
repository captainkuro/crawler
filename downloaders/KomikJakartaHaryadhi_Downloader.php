<?php

require_once 'KomikJakartaAbstract.php';

class KomikJakartaHaryadhi_Downloader extends KomikJakartaAbstract {
	protected $dir = '/home/khandar-gdp/tmp/haryadhi/';
	protected $home = 'http://komikjakarta.com/komik-haryadhi/';

	public function display() {
		return 'Komik Jakarta - Haryadhi';
	}
}