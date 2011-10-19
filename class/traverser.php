<?php
/**
 * Class Traverser
 * 
 * simplify the process of browsing through an "Index of /" page
 */
 
require_once 'page.php';

class Traverser {
	protected $url = ''; // initial place to start traversing
	protected $traversed = array(); // result from traversing $url
	protected $traverse_opts = array(
		'start_sign' => '<a href="',
		'end_sign' => '"',
		'n_skip' => 5,
		'debug' => false,
	);
	protected $processed = array(); // result from processing $traversed
	protected $process_opts = array(
		'debug' => false,
	);
	
	public function __construct($url) {
		$this->url = $url;
	}
	
	/**
	 * Getter - Setter
	 */
	public function url() {return $this->url;}
	public function traversed() {return $this->traversed;}
	public function processed() {return $this->processed;}
	public function set_traverse_opt($key, $val) {
		$this->traverse_opts[$key] = $val;
		return $this;
	}
	public function set_process_opt($key, $val) {
		$this->process_opts[$key] = $val;
		return $this;
	}
	
	/**
	 * Traversing
	 */
	// The recursive method
	public function start_traverse() {
		$this->traversed = $this->traverse(
			$this->url, $this->traverse_opts['start_sign'], 
			$this->traverse_opts['end_sign'], $this->traverse_opts['n_skip']
		);
	}
	
	public function traverse($url, $start_sign = '<a href="', $end_sign = '"', $n_skip = 5) {
		if ($this->traverse_opts['debug']) echo "In $url <br />\n";
		$p = new Page($url);
		$r = array();
		$l = Text::factory($p->content())->extract_to_array($start_sign, $end_sign);
		$n = count($l);
		for ($i = $n_skip; $i < $n; $i++) {
			if (strrpos($l[$i], '/') == (strlen($l[$i])-1)) {
				if ($this->traverse_dir($l[$i])) {
					$r[$l[$i]] = $this->traverse($url . $l[$i], $start_sign, $end_sign, $n_skip);
				}
			} else if ($this->traverse_file($l[$i])) {
				$r[$l[$i]] = $l[$i];
			}
		}
		return $r;
	}
	
	// True/false to continue this directory
	public function traverse_dir($dirname) {
		return true;
	}
	
	// True/false to continue this file
	public function traverse_file($filename) {
		return true;
	}
	
	/**
	 * Processing
	 */
	public function start_process() {
		$this->process($this->traversed, $this->url);
	}
	
	// The recursive method
	public function process($ar, $pre = '') {
		if ($this->process_opts['debug']) echo "In $pre <br />\n";
		foreach ($ar as $k => $v) {
			if (is_array($v)) {
				if ($this->process_dir($k)) { // dir is OK to process
					$this->process($v, $pre . $k);
				}
			} else if ($this->process_file($k)) { // file is OK to process
				$this->processed["$pre/$v"] = $v;
			}
		}
	}
	
	// True/false to continue this directory
	public function process_dir($dirname) {
		return true;
	}
	
	// True/false to continue this file
	public function process_file($filename) {
		return true;
	}
	
	/**
	 * Misc.
	 */
	// How to echo it
	public function print_processed() {
		foreach ($this->processed as $k => $v) {
			echo "<a href='$k'>$v</a><br />\n";
		}
	}
	
	// The full run
	public function run() {
		$this->start_traverse();
		$this->start_process();
		$this->print_processed();
	}
}