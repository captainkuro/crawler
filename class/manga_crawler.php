<?php
/**
 * Abstract class for specific manga reader spider
 * @uses X
 */
abstract class Manga_Crawler {
	//enable single chapter crawling
	protected $enable_single_chapter = false;
	protected $column_span = 4;
	
	public static function factory() {
		$class = get_called_class();
		return new $class;
	}
	
	public function display_header() {
		include '_header.php';
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
		
		function check_this_row(el) {
			var suspects = el.getElementsByTagName('input');
			suspects[0].click();
		}
		</script>
		
		<?php
		echo X::_o('div', array('class'=>'container'));
	}
	
	public function display_stage_1() {
		echo 
		X::form(array('class'=>'form-horizontal', 'method'=>'post'),
			X::fieldset(
				X::legend('1'),
				X::div(array('class'=>'control-group'),
					X::label(array('class'=>'control-label'), 'Manga URL'),
					X::div(array('class'=>'controls'),
						X::input(array('type'=>'text','name'=>'base','value'=>@$this->base))
					)
				),
				X::div(array('class'=>'control-group'),
					X::label(array('class'=>'control-label'), 'Prefix'),
					X::div(array('class'=>'controls'),
						X::input(array('type'=>'text','name'=>'prefix','value'=>@$this->prefix))
					)
				),
				$this->enable_single_chapter 
					? X::div(array('class'=>'control-group'),
						X::label(array('class'=>'control-label'), 'Infix'),
						X::div(array('class'=>'controls'),
							X::input(array('type'=>'text','name'=>'singlefix','value'=>@$this->singlefix)),
							X::p(array('class'=>'help-block'), 'only for single chapter')
						)
					)
					: ''
				,
				X::div(array('class'=>'form-actions'),
					X::button(array('class'=>'btn btn-primary','type'=>'submit','name'=>'stage1'), 'Submit')
				)
			)
		)
		;
	}
	
	public function display_stage_2() {
		echo
		X::_o('form', array('class'=>'form-horizontal', 'method'=>'post')),
			X::_o('fieldset'),
				X::legend('2'),
				X::div(array('class'=>'control-group'),
					X::label(array('class'=>'control-label'), 'Manga URL'),
					X::div(array('class'=>'controls'),
						X::input(array('type'=>'text','name'=>'base','value'=>@$this->base))
					)
				),
				X::div(array('class'=>'control-group'),
					X::label(array('class'=>'control-label'), 'Prefix'),
					X::div(array('class'=>'controls'),
						X::input(array('type'=>'text','name'=>'prefix','value'=>@$this->prefix))
					)
				),
				X::_o('div', array('class'=>'control-group')),
					X::label(array('class'=>'control-label'), 'Choose chapter'),
					X::_o('div', array('class'=>'controls')),
						X::label(array('class'=>'checkbox'),
							X::input(array('type'=>'checkbox','name'=>'all','onclick'=>'click_this()')),' All'
						),
						X::div(array('class'=>'input-append'),
							X::input(array('id'=>'parse_it', 'type'=>'text')),
							X::button(array('type'=>'button','onclick'=>'check_it()','class'=>'btn'), 'Check'),
							X::button(array('type'=>'button','onclick'=>'uncheck_it()','class'=>'btn'), 'Uncheck')
						),
						X::br(),
						X::_o('table', array('class'=>'table table-condensed')),
							X::thead(
								X::tr(
									X::th(array('class'=>'span1'), '#'),
									X::th(array('class'=>'span4'), 'Chapter Name'),
									X::th('Infix')
								)
							),
							X::_o('tbody')
					
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
							X::_c('tbody'),
						X::_c('table'),
					X::_c('div'),
				X::_c('div'),
				X::div(array('class'=>'form-actions'),
					X::button(array('class'=>'btn btn-primary','type'=>'submit','name'=>'stage2'), 'Submit')
				),
			X::_c('fieldset'),
		X::_c('form')
		;
	}
	
	public function display_stage_3() {
		$i = 1;
		echo "<div class='row-fluid'>\n";
		foreach ($this->info as $v) {
			echo "<div class='span{$this->column_span}'>\n";
			$this->crawl_chapter($v);
			echo "</div>\n";
			$limit = (int)(12 / $this->column_span);
			if ($i % $limit === 0) {
				echo "</div>\n";
				echo "<div class='row-fluid'>\n";
			}
			$i++;
		}
		echo "</div>\n";
	}
	
	public function display_footer() {
		echo X::_c('div');
		include '_footer.php';
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
		X::tr(array('onclick'=>'check_this_row(this)'),
			X::td(
				X::input(array('type'=>'checkbox','name'=>"info[$i][check]",'value'=>$i, 'id'=>'check-'.$v['infix'], 'onclick'=>'event.stopPropagation()'))
			),
			X::td(
				$v['desc'],
				X::input(array('type'=>'hidden','name'=>"info[$i][url]",'value'=>$v['url'])),
				X::input(array('type'=>'hidden','name'=>"info[$i][desc]",'value'=>$v['desc']))
			),
			X::td(
				X::input(array('class'=>'span1','type'=>'text','name'=>"info[$i][infix]",'value'=>$v['infix'], 'onclick'=>'event.stopPropagation()'))
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
	
	/*** File Management ***/
	// open folder of pages, parse filename, move to corresponding volumes
	public static function move_pages_to_volumes($path, $list, $cur_vol = 1) {
		// $path = 'D:\temp\Katekyo Hitman Reborn\\';//contoh
		$cur_pages = array();
		foreach (scandir($path) as $fname) {
			if (preg_match('/-(\d{3})-/', $fname, $m)) {
				$chap = (int)$m[1];
				if ($chap >= $list[$cur_vol][0] && $chap <= $list[$cur_vol][1]) {
					$cur_pages[] = $fname;
				} elseif ($chap > $list[$cur_vol][1]) {
					// make dir
					$vname = 'Vol '.Text::create($cur_vol)->pad(2)->to_s();
					if (!is_dir($path.$vname)) mkdir($path.$vname);
					foreach ($cur_pages as $p) {
						rename($path.$p, $path.$vname.'/'.$p);
					}
					$cur_vol++;
					$cur_pages = array($fname);
				}
			}
		}
	}
	
	public static function create_batch_zip($path) {
		// $path = 'D:\temp\manga\hajime\\'; // contoh
		$bat = 'cd "'.$path.'"'.PHP_EOL;
		foreach (scandir($path) as $dname) {
			if (is_dir($path.$dname) && $dname != '.' && $dname != '..') {
				$bat .= '7z a "'.$dname.'.zip" ".\\'.$dname.'\*" -tzip -mx0'.PHP_EOL;
			}
		}
		return $bat;
	}
}