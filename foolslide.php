<?php
/* Foolslide
http://mangacurse.info/reader/reader/series/soul_eater/
http://manga.redhawkscans.com/reader/series/hayate_no_gotoku/
http://reader.imperialscans.com/reader/series/historys_strongest_disciple_kenichi/
*/
class Foolslide extends Manga_Crawler {
	// need to be overridden, return array[desc,url,infix]
	// $base is URL submitteds
	public function extract_info($base) {
		// crawl chapters
		$p = new Page($base);
		$p->go_line('class="list"');
		$list = array();
		do {if ($p->curr_line()->contain('class="title"') && $p->curr_line()->contain('title=')) {
			$line = $p->curr_line()->dup();
			$href = $line->dup()->cut_between('href="', '"')->to_s();
			$desc = $line->dup()->cut_between('title="', '">')->to_s();
			$infix = basename($href);
			$list[] = array(
				'url' => $href,
				'desc' => $desc,
				'infix' => $infix,
			);
		}} while (!$p->next_line()->contain('</article>'));
		return $list;
	}
	
	// must be overridden, echo html of links
	// $v contain [url,desc,infix]
	public function crawl_chapter($v) {
		$ifx = Text::create($v['infix'])->pad(3)->to_s();
		$p = new Page($v['url']);
		// grab list of pages
		$p->go_line('="changePage(');
		$pages = $p->curr_line()->extract_to_array('href="', '"');
		// grab current image
		$this->crawl_page($p, $ifx);
		
		array_shift($pages);
		foreach ($pages as $purl) {
			$this->crawl_page(new Page($purl), $ifx);
		}
	}
	
	public function crawl_page($p, $ifx) {
		$prefix = $this->prefix;
		$p->go_line('class="open"');
		$img = $p->curr_line()->dup()->cut_between('src="', '"')->to_s();
		$iname = urldecode(basename($img));
		echo "<a href=\"$img\">$prefix-$ifx-$iname</a><br/>\n";
	}
}
$f = new Foolslide();
$f->run();
exit;

extract($_POST);

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
// echo X::pre(print_r($supported, true));
// stage 1
echo 
X::h2('1'),
X::form(array('method'=>'post'),
	'Manga: ',X::input(array('type'=>'text','name'=>'base','value'=>@$base)),X::br(),
	'Prefix: ',X::input(array('type'=>'text','name'=>'prefix','value'=>@$prefix)),X::br(),
	X::input(array('type'=>'submit','name'=>'stage1'))
)
;

// stage 2
if (isset($stage1) || isset($stage2)) {
// 
$parsed = parse_url($base);

echo
X::h2('2'),
X::_o('form', array('method'=>'post')),
	'Manga: ',X::input(array('type'=>'text','name'=>'base','value'=>@$base)),X::br(),
	'Prefix: ',X::input(array('type'=>'text','name'=>'prefix','value'=>@$prefix)),X::br(),
	X::div('Choose chapter:'),
	X::input(array('type'=>'checkbox','name'=>'all','onclick'=>'click_this()')),'All',X::br(),
	X::_o('table'),
		X::tr(
			X::th('Chapter Name'),
			X::th('Infix')
		)
;

function print_choice($i, $v) {
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

	if (isset($stage1)) {
		// crawl chapters
		$p = new Page($base);
		$p->go_line('class="list"');
		$list = array();
		do {if ($p->curr_line()->contain('class="title"') && $p->curr_line()->contain('title=')) {
			$line = $p->curr_line()->dup();
			$href = $line->dup()->cut_between('href="', '"')->to_s();
			$desc = $line->dup()->cut_between('title="', '">')->to_s();
			$infix = basename($href);
			$list[] = array(
				'url' => $href,
				'desc' => $desc,
				'infix' => $infix,
			);
		}} while (!$p->next_line()->contain('</article>'));
		
		foreach ($list as $i => $v) {
			print_choice($i, $v);
		}
	} else {
		// from POST
		foreach ($info as $i => $v) { if (isset($v['check'])) {
			print_choice($i, $v);
		} else {
			unset($info[$i]);
		}}
	}

echo
	X::_c('table'),
	X::input(array('type'=>'submit','name'=>'stage2')),
X::_c('form')
;
}

// stage 3
if (isset($stage2)) {

function crawl_page($p) {
	global $prefix, $ifx;
	$p->go_line('class="open"');
	$img = $p->curr_line()->dup()->cut_between('src="', '"')->to_s();
	$iname = urldecode(basename($img));
	echo "<a href=\"$img\">$prefix-$ifx-$iname</a><br/>\n";
}

	foreach ($info as $v) {
		$ifx = Text::create($v['infix'])->pad(3)->to_s();
		$p = new Page($v['url']);
		// grab list of pages
		$p->go_line('="changePage(');
		$pages = $p->curr_line()->extract_to_array('href="', '"');
		// grab current image
		crawl_page($p);
		
		array_shift($pages);
		foreach ($pages as $purl) {
			crawl_page(new Page($purl));
		}
	}
}

echo
	X::_c('body'),
X::_c('html')
;
