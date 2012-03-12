<?php
/**
 * Class Page
 * @author captain_kuro
 *
 * an attempt to rewrite the Crawler class
 * after years of experience in using Crawler
 */

// require_once 'text.php';

class Page {
	protected $url = ''; // the current url
	protected $prev_url = '';
	public $opts = array(); 
	protected $content = ''; // whole page string
	protected $lines = array(); //.array of strings
	protected $current_line = null; // a Text instance
	protected $current_i = 0;
	protected $prev_i = 0; // i terakhir sebelum reset_line()
	protected $ch = null;
	public static $use_proxy = true;
	public static $proxy = array(
		'name' => '10.1.1.2',  // CHANGEME
		'port' => 8080, // CHANGEME
		'user' => "",   // CHANGEME
		'pass' => "",   // CHANGEME
	);
	// Some regex template
	const REG_A_HREF = '/<a\\s+href="([^"]+)"[^>]*>/';
	const REG_HREF = 'href=["\']([^"\']+)["\']';
	const REG_SRC = 'src=["\']([^"\']+)["\']';
	
	public function __construct($url = null, $opts = null) {
		if (isset($url)) {
			$this->fetch_url($url, $opts);
		}
	}
	
	public function url() {return $this->url;}
	public function opts() {return $this->opts;}
	public function content() {return $this->content;}
	public function curl() {return $this->ch;}
	
	/**
	 * Global scope
	 */
	public function fetch_url($url, $opts = null) {
		$this->prev_url = $this->url;
		$this->url = $url;
		if (is_array($opts)) $this->opts = $this->opts + $opts;
		// Reuse the CURL connection
		if (!$this->ch) {
			$ch = curl_init();
		} else {
			$ch = $this->ch;
		}
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_ENCODING, "");
		curl_setopt($ch, CURLOPT_TIMEOUT, 150);
		// CURLOPT_COOKIE, CURLOPT_REFERER, CURLOPT_USERAGENT
		if (self::$use_proxy) {
			curl_setopt($ch, CURLOPT_PROXY, self::$proxy['name']);
			curl_setopt($ch, CURLOPT_PROXYPORT, self::$proxy['port']);
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, self::$proxy['user'] . ":" . self::$proxy['pass']); 
		}
		if (@$this->opts['become_firefox']) {
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:6.0.2) Gecko/20100101 Firefox/6.0.2');
			unset($this->opts['become_firefox']);
		}
		if (@$this->opts['login_first']) {
			$d = $this->opts['login_first'];
			$login_url = $d['url'];
			$login_post = $d['post'];
			curl_setopt($ch, CURLOPT_URL, $login_url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $login_post);
			curl_setopt($ch, CURLOPT_COOKIEJAR, '');
			$store = curl_exec($ch);
			
			unset($this->opts['login_first']);
		}
		if ($this->opts) curl_setopt_array($ch, $this->opts);
		// Load the page specified by $url
		curl_setopt($ch, CURLOPT_URL, $url);
		$retry = 10;
		$this->content = curl_exec($ch);
		while (!$this->content && $retry--) {
			$this->content = curl_exec($ch);
		}
		if (!$this->content) throw new Exception("Unable to retrieve $url");
		$this->ch = $ch;
		
		// Break content per line
		$this->lines = explode("\n", $this->content);
		$this->current_i = $this->prev_i = 0;
		$this->current_line = new Text($this->lines[0]);
	}
	
	/**
	 * Fill $this->content directly, not by retrieveing a URL
	 */
	public function fetch_text($text) {
		$this->content = $text;
		// Break content per line
		$this->lines = explode("\n", $this->content);
		$this->current_i = $this->prev_i = 0;
		$this->current_line = new Text($this->lines[0]);
	}
	
	public function __destruct() {
		if ($this->ch) curl_close($this->ch);
	}
	
	// go to the line indicating next url
	public function has_next() {
		return false;
	}
	
	// retrieve the next url from current url
	public function get_next() {
		return $this->url;
	}
	
	// fetch the new url to this Page
	public function go_next() {
		$this->fetch_url($this->get_next());
	}
	
	// what to do with the current Page
	public function process() {
	}
	
	// general overall process, can be overridden
	public function run() {
		$this->process();
		while ($this->has_next()) {
			$this->go_next();
			$this->process();
		}
	}

	/**
	 * Line scope
	 */
	public function curr_line() {
		return $this->current_line;
	}
	
	public function next_line($n = 1) {
		if ($this->end_of_line()) throw new Exception('End of line');
		$this->current_i += $n;
		$this->current_line->set($this->lines[$this->current_i]);
		return $this->current_line;
	}
	
	public function prev_line($n = 1) {
		$this->current_i -= $n;
		if ($this->current_i < 0) throw new Exception('Less than zero');
		$this->current_line->set($this->lines[$this->current_i]);
		return $this->current_line;
	}
	
	public function reset_line($i = 0) {
		$this->prev_i = $this->current_i;
		$this->current_i = $i;
		$this->current_line->set($this->lines[$this->current_i]);
	}
	
	public function last_line() {
		$this->current_i = count($this->lines) - 1;
		$this->current_line->set($this->lines[$this->current_i]);
		return $this->current_line;
	}
	
	public function end_of_line() {
		return $this->current_i >= (count($this->lines)-1);
	}
	
	public function go_line($search) {
		if (is_array($search)) {
			
		} else {
			while (!$this->end_of_line() && $this->current_line->pos($search) === false) {
				$this->next_line();
			}
		}
	}
	
	public function go_line_regex($regex) {
		while (!$this->end_of_line() && !$this->current_line->regex_match($regex)) {
			$this->next_line();
		}
	}
	
	public function curl_getinfo($opt = null) {
		if ($opt) {
			return curl_getinfo($this->ch, $opt);
		} else {
			return curl_getinfo($this->ch);
		}
	}
	
	/**
	 * Static methods
	 */
}
ob_implicit_flush(true);