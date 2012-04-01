<?php
/**
 * Abstract class for specific manga reader spider
 * @uses X
 */
abstract class Manga_Crawler {
	//enable single chapter crawling
	protected $enable_single_chapter = false;
	
	public static function factory() {
		$class = get_called_class();
		return new $class;
	}
	
	public function display_header() {
		echo 
		X::_o('html'),
			X::_o('body')
		;
		?>
		<script type="text/javascript">
		var global_check = false;
		function click_this() {
			global_check = !global_check;
			var tags = document.getElementsByTagName("input");
			for (i in tags) {
				if (tags[i].type == "checkbox") {
					tags[i].checked = global_check;
				}
			}
		}
		
		function parse_it() {
			var val = document.getElementById("parse_it").value;
			val.match(/^(\w+)-/)
			var from = parseInt(RegExp.$1);
			val.match(/-(\w+)$/);
			var to = parseInt(RegExp.$1);
			return [from, to];
		}
		
		function check_it() {
			var list = parse_it();
			for (var i=list[0]; i<=list[1]; i++) {
				var el = document.getElementById("check-"+i);
				el.checked = true;
			}
		}
		
		function uncheck_it() {
			var list = parse_it();
			for (var i=list[0]; i<=list[1]; i++) {
				var el = document.getElementById("check-"+i);
				el.checked = false;
			}
		}
		</script>
		<?php
	}
	
	public function display_stage_1() {
		echo 
		X::h2('1'),
		X::form(array('method'=>'post'),
			'Manga URL: ',X::input(array('type'=>'text','name'=>'base','value'=>@$this->base)),X::br(),
			'Prefix: ',X::input(array('type'=>'text','name'=>'prefix','value'=>@$this->prefix)),X::br(),
			$this->enable_single_chapter 
				? 'Infix: '.X::input(array('type'=>'text','name'=>'singlefix','value'=>@$this->singlefix)).' only for single chapter'.X::br()
				: ''
			,
			X::input(array('type'=>'submit','name'=>'stage1'))
		)
		;
	}
	
	public function display_stage_2() {
		echo
		X::h2('2'),
		X::_o('form', array('method'=>'post')),
			'Manga URL: ',X::input(array('type'=>'text','name'=>'base','value'=>@$this->base)),X::br(),
			'Prefix: ',X::input(array('type'=>'text','name'=>'prefix','value'=>@$this->prefix)),X::br(),
			X::div('Choose chapter:'),
			X::input(array('type'=>'checkbox','name'=>'all','onclick'=>'click_this()')),'All',X::br(),
			X::input(array('id'=>'parse_it')),
			X::input(array('type'=>'button','onclick'=>'check_it()','value'=>'Check')),
			X::input(array('type'=>'button','onclick'=>'uncheck_it()','value'=>'Uncheck')),
			X::br(),
			X::_o('table'),
				X::tr(
					X::th('Chapter Name'),
					X::th('Infix')
				)
		;
		if (isset($this->stage1)) {
			$list = $this->extract_info($this->base);
			foreach ($list as $i => $v) {
				$this->print_choice($i, $v);
			}
		} else {
			// from POST
			foreach ($this->info as $i => $v) { if (isset($v['check'])) {
				$this->print_choice($i, $v);
			} else {
				unset($this->info[$i]);
			}}
		}
		echo
			X::_c('table'),
			X::input(array('type'=>'submit','name'=>'stage2')),
		X::_c('form')
		;
	}
	
	public function display_stage_3() {
		foreach ($this->info as $v) {
			$this->crawl_chapter($v);
		}
	}
	
	public function display_footer() {
		echo
			X::_c('body'),
		X::_c('html')
		;
	}
	
	// complete process
	public function run() {
		// extract POST
		foreach ($_POST as $k => $v) $this->$k = $v;
		
		$this->display_header();
		$this->display_stage_1();
		
		if (isset($this->stage1) || isset($this->stage2)) {
			// if single chapter, skip stage2 and stage3
			if ($this->enable_single_chapter && $this->url_is_single_chapter($this->base)) {
				if ($this->singlefix === '') {
					$this->singlefix = $this->grab_chapter_infix($this->base);
				}
				$this->crawl_chapter(array(
					'url' => $this->base,
					'infix' => $this->singlefix,
					'desc' => '',
				));
				exit;
			}
			$this->display_stage_2();
		}
		
		if (isset($this->stage2)) {
			$this->display_stage_3();
		}
		
		$this->display_footer();
	}
	
	public function print_choice($i, $v) {
		echo
		X::tr(
			X::td(
				X::input(array('type'=>'checkbox','name'=>"info[$i][check]",'value'=>$i, 'id'=>'check-'.$v['infix'])),
				$v['desc'],
				X::input(array('type'=>'hidden','name'=>"info[$i][url]",'value'=>$v['url'])),
				X::input(array('type'=>'hidden','name'=>"info[$i][desc]",'value'=>$v['desc']))
			),
			X::td(
				X::input(array('type'=>'text','name'=>"info[$i][infix]",'value'=>$v['infix']))
			)
		)
		;
	}
	
	// need to be overridden, return array[desc,url,infix]
	// $base is URL submitted
	abstract public function extract_info($base);
	
	// must be overridden, echo html of links
	// $v contain [url,desc,infix]
	abstract public function crawl_chapter($v);
	
	// must be overridden if want to enable single chapter crawl
	public function url_is_single_chapter($url) {
		return false;
	}
	
	// must be overriden if want to enable automatic infix
	public function grab_chapter_infix($url) {
		return 0;
	}
	
	/*** CURL MULTITHREAD ***/
	public static function addHandle(&$curlHandle,$url) {
		$cURL = curl_init();
		curl_setopt($cURL, CURLOPT_URL, $url);
		curl_setopt($cURL, CURLOPT_HEADER, 0);
		curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($cURL, CURLOPT_BINARYTRANSFER, 1);
		curl_multi_add_handle($curlHandle,$cURL);
		return $cURL;
	}
	
	public static function execHandle(&$curlHandle) {
		/* yg ini bikin 100% CPU
		$flag=null;
		do {
			//fetch pages in parallel
			curl_multi_exec($curlHandle,$flag);
		} while ($flag > 0);
		*/
		$active = null;
		//execute the handles
		do {
			$mrc = curl_multi_exec($curlHandle, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);

		while ($active && $mrc == CURLM_OK) {
			if (curl_multi_select($curlHandle) != -1) {
				do {
					$mrc = curl_multi_exec($curlHandle, $active);
				} while ($mrc == CURLM_CALL_MULTI_PERFORM);
			}
		}
	}
	
	/**
	 * Mendownload $size buah link sekaligus, lalu diproses oleh fungsi $function
	 * @param integer $size jumlah thread yg diinginkan
	 * @param array   $pages_url array of full url
	 * @param string  $function nama fungsi yang dipanggil untuk memproses 1 halaman,
	 *                  fungsinya minimal punya 1 parameter (Page $P)
	 * @param array   $params parameter tambahan ke fungsi
	 */
	public static function multiProcess($size, $pages_url, $function, $params = false) {
		$n = 0;
		$curlHandle = null;
		$curlList = array();
		foreach ($pages_url as $aurl) {
			if ($n == 0) {
				$curlHandle = curl_multi_init();
			}
			$curlList[$aurl] = self::addHandle($curlHandle, $aurl);
			$n++;
			if ($n >= $size) {
				self::execHandle($curlHandle);
				foreach ($curlList as $theurl => $curlEl) {
					$html = curl_multi_getcontent($curlEl);
					// Ada kemungkinan gagal retrieve
					if (trim($html)) {
						$P = new Page();
						$P->fetch_text($html);
					} 
					// In that case, it must retrieve the HTML by itself
					else {
						$P = new Page($theurl);
					}
					if ($params) {
						call_user_func_array($function, array_merge(array($P), $params));
					} else {
						$function($P);
					}
					curl_multi_remove_handle($curlHandle, $curlEl);
				}
				curl_multi_close($curlHandle);
				$n = 0;
				$curlList = array();
			}
		}
		if ($curlList) {
			self::execHandle($curlHandle);
			foreach ($curlList as $theurl => $curlEl) {
				$html = curl_multi_getcontent($curlEl);
				// Ada kemungkinan gagal retrieve
				if (trim($html)) {
					$P = new Page();
					$P->fetch_text($html);
				} 
				// In that case, it must retrieve the HTML by itself
				else {
					$P = new Page($theurl);
				}
				if ($params) {
					call_user_func_array($function, array_merge(array($P), $params));
				} else {
					$function($P);
				}
				curl_multi_remove_handle($curlHandle, $curlEl);
			}
			curl_multi_close($curlHandle);
		}
	}
}