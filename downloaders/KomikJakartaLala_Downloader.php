<?php

require_once 'KomikJakartaAbstract.php';

class KomikJakartaLala_Downloader extends KomikJakartaAbstract {
	protected $dir;
	protected $home = 'http://komikjakarta.com/komik-lala/';

	public function __construct() {
		$this->dir = DConfig::k_folder() . '/lala/';
	}

	public function display() {
		return 'Komik Jakarta - Lala';
	}
}