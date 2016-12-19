<?php

class StupidConfig {
	private $filename;
	private $config;

	public function __construct($filename) {
		$this->filename = $filename;
		$json = file_get_contents($filename);
		$this->config = json_decode($json);
	}

	public function c() {
		return $this->config;
	}

	public function config() {
		return $this->config;
	}

	public function save() {
		file_put_contents($this->filename, json_encode($this->config, JSON_PRETTY_PRINT));
	}
}