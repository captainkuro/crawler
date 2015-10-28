<?php

require_once 'KomikJakartaAbstract.php';

class KomikJakartaHaryadhi_Downloader extends KomikJakartaAbstract {
	protected $dir;
	protected $home = 'http://komikjakarta.com/komik-haryadhi/';

	public function __construct() {
		$this->dir = DConfig::k_folder() . '/haryadhi/';
	}

	public function display() {
		return 'Komik Jakarta - Haryadhi';
	}
}