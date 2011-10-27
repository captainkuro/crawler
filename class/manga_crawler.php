<?php

class Manga_Crawler {
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
		</script>
		<?php
	}
	
	public function display_stage_1() {
		echo 
		X::h2('1'),
		X::form(array('method'=>'post'),
			'Manga: ',X::input(array('type'=>'text','name'=>'base','value'=>@$this->base)),X::br(),
			'Prefix: ',X::input(array('type'=>'text','name'=>'prefix','value'=>@$this->prefix)),X::br(),
			X::input(array('type'=>'submit','name'=>'stage1'))
		)
		;
	}
	
	public function display_stage_2() {
		echo
		X::h2('2'),
		X::_o('form', array('method'=>'post')),
			'Manga: ',X::input(array('type'=>'text','name'=>'base','value'=>@$this->base)),X::br(),
			'Prefix: ',X::input(array('type'=>'text','name'=>'prefix','value'=>@$this->prefix)),X::br(),
			X::div('Choose chapter:'),
			X::input(array('type'=>'checkbox','name'=>'all','onclick'=>'click_this()')),'All',X::br(),
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
				X::input(array('type'=>'checkbox','name'=>"info[$i][check]",'value'=>$i)),
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
	public function extract_info($base) {}
	
	// must be overridden, echo html of links
	// $v contain [url,desc,infix]
	public function crawl_chapter($v) {}
}