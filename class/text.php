<?php
/**
 * Class Text
 * @author captain_kuro
 *
 * to wrap a String
 */

class Text {
	protected $s = '';
	
	public static function factory($s) {
		return new Text($s);
	}
	
	public static function create($s) {
		return new Text($s);
	}
	
	public function __construct($s) {
		$this->s = (string)$s;
	}
	
	public function __toString() {
		return $this->s;
	}
	
	public function set($str) {
		$this->s = $str;
		return $this;
	}
	
	public function to_s() {
		return $this->s;
	}
	
	public function to_string() {
		return $this->s;
	}
	
	public function dup() { // duplicate
		return new Text($this->s);
	}
	
	public function trim() {
		$this->s = trim($this->s);
		return $this;
	}
	
	public function length() {
		return strlen($this->s);
	}
	
	/**
	 * Return position of $needle in this string
	 * 'r' = last occurence; 'i' = not case-sensitive
	 */
	public function pos($needle, $offset = 0) {
		return strpos($this->s, $needle, $offset);
	}
	
	public function rpos($needle, $offset = 0) {
		return strrpos($this->s, $needle, $offset);
	}
	
	public function ipos($needle, $offset = 0) {
		return stripos($this->s, $needle, $offset);
	}
	
	public function ripos($needle, $offset = 0) {
		return strripos($this->s, $needle, $offset);
	}
	
	public function exist($str) {
		return $this->pos($str) !== false;
	}
	
	public function contain($str) {
		return $this->pos($str) !== false;
	}
	
	/**
	 * Get a portion of string
	 * "This is a tring yo"
	 * $str = "is"
	 * cut_before = "Th"
	 * cut_until = "This"
	 * cut_after = " is a tring yo"
	 * cut_from = "is is a tring yo"
	 * cut_between("is", "tring") = " a "
	 * 'r' = last occurence
	 */
	public function substring($start, $length = null) {
		if ($length !== null) {
			$this->s = substr($this->s, $start, $length);
		} else {
			$this->s = substr($this->s, $start);
		}
		return $this;
	}
	
	public function cut_before($str) {
		$pos = $this->pos($str);
		return $this->substring(0, $pos);
	}
	
	public function cut_rbefore($str) {
		$pos = $this->rpos($str);
		return $this->substring(0, $pos);
	}
	
	public function cut_until($str) {
		$pos = $this->pos($str);
		return $this->substring(0, $pos + strlen($str));
	}
	
	public function cut_runtil($str) {
		$pos = $this->rpos($str);
		return $this->substring(0, $pos + strlen($str));
	}
	
	public function cut_after($str) {
		$pos = $this->pos($str);
		return $this->substring($pos + strlen($str));
	}
	
	public function cut_rafter($str) {
		$pos = $this->rpos($str);
		return $this->substring($pos + strlen($str));
	}
	
	public function cut_from($str) {
		$pos = $this->pos($str);
		return $this->substring($pos);
	}
	
	public function cut_rfrom($str) {
		$pos = $this->rpos($str);
		return $this->substring($pos);
	}
	
	public function cut_between($from, $to) {
		$this->cut_after($from);
		$this->cut_before($to);
		return $this;
	}
	
	/**
	 * Pad until $length $with, if $ischapter then "305.5",4 will become "0305.5"
	 */
	public function pad($length, $with = '0', $pad_type = STR_PAD_LEFT, $ischapter = false) {
		if ($ischapter && ($this->pos('.') !== false)) {
			$temp = $this->dup()->cut_before('.');
			$temp->pad($length, $with, $pad_type, $ischapter);
			$this->s = $temp->to_s() . $this->dup()->cut_from('.');
		} else {
			$this->s = str_pad($this->s, $length, $with, $pad_type);
		}
		return $this;
	}
	
	/**
	 * Repeat this string $multi times
	 */
	public function repeat($multi) {
		$this->s = str_repeat($this->s, $multi);
		return $this;
	}
	
	public function replace($search, $replace) {
		$this->s = str_replace($search, $replace, $this->s);
		return $this;
	}
	
	/**
	 * Perform regular expression on this string
	 */
	public function regex_match($pattern) {
		if (preg_match($pattern, $this->s, $match)) {
			return $match;
		} else {
			return null;
		}
	}
	
	public function regex_match_all($pattern) {
		preg_match_all($pattern, $this->s, $match);
		return $match;
	}
	
	public function regex_replace($pattern, $replace) {
		$this->s = preg_replace($pattern, $replace, $this->s);
		return $this;
	}
	
	/**
	 * Misc functions
	 */
	// perform rawurlencode, but leave the slash '/' intact
	public function url_encode() {
		$r = explode('/', $this->s);
		$r = array_map('rawurlencode', $r);
		$this->s = implode('/', $r);
		return $this;
	}

	// change 'asdf_1.jpg' to 'asdf_01.jpg'
	public function fix_filename() {
		$this->s = preg_replace('/(\\D)(\\d){1}\\./', '${1}0${2}.', $this->s);
		return $this;
	}
	
	// convert text to array containing all text between $from and $to
	public function extract_to_array($from, $to) {
		$ledak = explode($from, $this->s);
		$n = count($ledak);
		$temp = array();
		for ($i=1; $i<$n; ++$i) {
			$temp[] = Text::factory($ledak[$i])->cut_before($to)->to_s();
		}
		return $temp;
	}
	
	// fungsi2 string bawaan, parameter pertama adalah string ini
	public function __call($name, $args) {
		if (function_exists($name)) {
			$this->s = call_user_func_array($name, array_merge(array($this->s), $args));
		}
		return $this;
	}
	
	// ======================= STATIC FUNCTIONS ================================
	
	// generate $n random characters
	public static function random_char($n=1) {
		$pool = 'abcdefghijklmnopqrstuvwxyz0123456789';
		$t = '';
		while ($n--) {
			$t .= $pool[rand(0, strlen($pool)-1)];
		}
		return $t;
	}
	
	// generate random email
	public static function random_email() {
		$user = Text::random_char(rand(3,15));
		$domain = Text::random_char(rand(4,10));
		$pool_tld = array('com','net','org','in','co.id','us','ru','gov','com.sg','sg','asia','biz','edu','info', 'cn');
		$tld = $pool_tld[rand(0, count($pool_tld)-1)];
		$email = "$user@$domain.$tld";
		return $email;
	}
}