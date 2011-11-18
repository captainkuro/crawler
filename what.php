<?php
require_once 'class/text.php';
require_once 'class/page.php';
require_once 'class/traverser.php';

class HalfAsians extends Page {
	public function process() {
		$this->go_line('class="post"');
		do {
			if ($this->curr_line()->exist('<h2>')) {
				$m = $this->curr_line()->regex_match('/<a href="([^"]+)"[^>]*>/');
				$name = $this->curr_line()->dup()->cut_between('">', '</a>');
				echo $name . "<br />";
				$url = $m[1];
				$p = new Page($url);
				$p->go_line('class="entry"');
				$post = '';
				do {
					$post .= $p->curr_line()->to_s();
				} while(!$p->next_line()->exist('<!-- adsense code'));
				$m = Text::factory($post)->regex_match_all('/href=["\']([^"\']+)["\']/');
				$clean = array();
				foreach ($m[1] as $e) {
					if (Text::factory($e)->exist('/img/')) {
						$clean[] = $e;
						echo "<a href='$e'>$name</a><br />\n";
					}
				}
			}
		} while (!$this->next_line()->exist('class="navigation"'));
	}

	public function has_next() {
		$this->reset_line();
		$this->go_line("class='pages'");
		return $this->curr_line()->exist('nextpostslink');
	}
	
	public function get_next() {
		$m = $this->curr_line()->regex_match('/<a href="([^"]+)" class="nextpostslink"/');
		return $m[1];
	}
}
// $a = new HalfAsians('http://half-asians.com/');
// $a->run();

class Xcentre extends Page {
	public static $base = 'http://www.xcentre.net';
	// http://www.xcentre.net/archives/category/gallery/
	private $lim = 0;
	
	public function process() {
		$this->go_line('<div class="post">');
		do {
			if ($this->curr_line()->exist('<h5 ')) {
				$m = $this->curr_line()->regex_match(self::REG_A_HREF);
				$url = $m[1];
				$name = basename($url);
				echo "$name<br />\n";
				$p = new XcentreSub($url);
				$p->run();
			}
		} while (!$this->next_line()->exist('class="wp-pagenavi"'));
	}
	
	public function has_next() {
		//if ($this->lim > 10) return false;
		$this->reset_line();
		$this->go_line('class="wp-pagenavi"');
		$this->lim++;
		return $this->next_line()->exist('class="nextpostslink"');
	}
	
	public function get_next() {
		$m = $this->curr_line()->regex_match('/'.self::REG_HREF.' class="nextpostslink"/');
		return $m[1];
	}
}

class XcentreSub extends Page {
	// http://www.xcentre.net/archives/yoko-kumada-image-tv-march-11/
	public function process() {
		$this->go_line('class="entry"');
		do {
			if ($this->curr_line()->exist('href=') && $this->curr_line()->exist('/xgallery/')) {
				$m = $this->curr_line()->regex_match('/' . self::REG_HREF . '/');
				$img = $m[1];
				$name = Text::factory($img)->dirname()->basename()->to_s();
				echo "<a href='$img'>$name</a><br />\n";
			}
		} while (!$this->next_line()->exist('class="sociable"'));
	}
	
	public function has_next() {
		$this->reset_line();
		$this->go_line('class="sociable"');
		return $this->prev_line()->exist('class="next"');
	}
	
	public function get_next() {
		$m = $this->curr_line()->regex_match('/class="next" '.self::REG_HREF.'/');
		return Xcentre::$base . $m[1];
	}
}
// http://www.xcentre.net/archives/category/gallery/
// $a = new XcentreSub('http://www.xcentre.net/archives/ai-nanase-school/');//test
// $a = new Xcentre('http://www.xcentre.net/archives/category/gallery/');
// $a->run();

class Echnie extends Page {
	// http://babes.echnie.nl/models/
	public function process() {
		$this->go_line('Hayley Marie');
		do {
			if ($this->curr_line()->exist('href=')) {
				$m = $this->curr_line()->regex_match('/' . self::REG_HREF . '\\s+title=["\']([^"\']*)["\']/');
				$url = $m[1];
				$name = $m[2];
				if (!$name) $name = 'asdf' . $this->current_i;
				$p = new Page($url);
				$p->go_line("id='form'");
				do {
					if ($p->curr_line()->exist("href='")) {
						$img = $p->curr_line()->dup()->cut_between("href='", "'");
						if ($img->exist('imageboss.net')) {
							$img->replace('/view/', '/img/')->replace('-', '/');
						}
						echo "<a href='$img'>$name</a><br />\n";
					}
				} while (!$p->next_line()->exist('</form>'));
			}
		} while (!$this->next_line()->exist('id="vr_nav"'));
	}
}

// $a = new Echnie('http://babes.echnie.nl/models/');
// $a->run();

class Firstbabes extends Traverser {
	public function process_file($f) {
		return !preg_match('/\\d+x\\d+\\./', $f);
	}
}
// $a = new Firstbabes('http://firstbabes.com/wp-content/uploads/2010/');
// $a->set_traverse_opt('n_skip', 1);
// $a->run();

// http://www.dakota-fanning.org/gallery/albums/photoshoots/
class DakotaFanningOrg extends Traverser {
	public function process_file($f) {
		return !preg_match('/^normal_/', $f) 
			&& !preg_match('/^thumb_/', $f)
			&& !preg_match('/^mini_/', $f)
		;
	}
	
	public function print_processed() {
		foreach ($this->processed as $k => $v) {
			$text = Text::factory($k)->dirname()->basename();
			echo "<a href='$k'>$text</a><br />\n";
		}
	}
}
// $a = new DakotaFanningOrg('http://www.dakota-fanning.org/gallery/albums/photoshoots/');
// $a->set_traverse_opt('n_skip', 1)->set_traverse_opt('debug', true);
// $a->run();

// http://www.dakota-fanning.org/gallery/index.php
// http://wonderfuldakota.com/cpg135/
class Coppermine extends Page {
	protected $base = '';
	
	public function is_category() {
		return preg_match('/\\?cat=/', $this->url);
	}
	
	public function is_album() {
		return preg_match('/\\?album=/', $this->url);
	}
	
	public function contain_categories() {
		return preg_match('/class="catlink"/', $this->content);
	}
	
	public function contain_albums() {
		return preg_match('/class="alblink"/', $this->content);
	}
	
	public function album_process() {
		$res = array();
		$bomb = Text::factory($this->content)->extract_to_array('src="', '"');
		foreach ($bomb as $i => $v) {
			if (!preg_match('/\\/thumb_/', $v)) {
				unset($bomb[$i]);
			} else {
				$res[] = $this->base . $bomb[$i];
			}
		}
		return $res;
	}
	
	public function album_has_next() {
		return false;
	}
	
	public function album_get_next() {
	}
	
	public function cat_process() {
		$links = array();
		$this->go_line('class="alblink"');
		do {
			if ($this->curr_line()->contain('class="alblink"')) {
				$m = $this->curr_line()->regex_match('/href=["\']([^"\']+\\?album=[^"\']+)["\'][^>]*>(.+)<\\/a>/');
				$links[strip_tags($m[2])] = $this->base . $m[1];
			}
		} while (!$this->next_line()->contain('End standard table'));
		return $links;
	}
	
	public function cat_has_next() {
		return false;
	}
	
	public function cat_get_next() {
	}
	
	// mekanisme run() ga normal, define sendiri
	public function my_run($url) {
		echo "$url\n";
		$this->fetch_url($url);
		$res = array();
		if ($this->is_album()) {
			/*
			album bisa multi halaman
			berisi thumbnail2 gambar
			cukup ambil src image thumbnail remove "^thumb_"
			*/
			$res = array_merge($res, $this->album_process());
			while ($this->album_has_next()) {
				$this->fetch_url($this->album_get_next());
				$res = array_merge($res, $this->album_process());
			}
		} else { // assume category
			/*
			category hanya 1 halaman
			berisi link2 ke category/album
			*/
			$links = array();
			if ($this->contain_categories()) {
				$this->go_line('class="catlink"');
				do {
					if ($this->curr_line()->contain('class="catlink"')) {
						$m = $this->curr_line()->regex_match('/href=["\']([^"\']+\\?cat=[^"\']+)["\'][^>]*>(.+)<\\/a>/');
						$links[strip_tags($m[2])] = $this->base . $m[1];
					}
				} while (!$this->next_line()->contain('End standard table'));
			} else if ($this->contain_albums()) { // contains albums only
				/*
				contains albums only
				multi page
				*/
				$links = $links + $this->cat_process();
				while ($this->cat_has_next()) {
					$this->fetch_url($this->cat_get_next());
					$links = $links + $this->cat_process();
				}
			}
			
			foreach ($links as $k => $v) {
				$res[$k] = $this->my_run($v);
			}
		}
		return $res;
	}
	
	public function run() {
		$this->base = Text::factory($this->url)->cut_runtil('/')->to_s();
		return $this->my_run($this->url);
	}
	
	/*
	kode di atas perlu direwrite
	faktanya adalah:
	- dalam 1 halaman mungkin terdapat category
			mungkin terdapat album
			mungkin multi halaman
	- tapi pasti paling duluan category
			berikutnya album
			terakhir link ke halaman lain
	- link halaman
			dimulai dari 1
			tidak ada link next, hanya halaman ke-n
	*/
}

// $a = new Coppermine('http://wonderfuldakota.com/cpg135/');
// $b = $a->run();
// var_export($b);

// http://itmanagement.earthweb.com/cnews/article.php/3929766/Tech-Comics-How-to-Detect-a-Geek.htm
// http://itmanagement.earthweb.com/cnews/article.php/12035_3929766_2/Tech-Comics-How-to-Detect-a-Geek.htm
// http://itmanagement.earthweb.com/article.php/31771_3699661_4/Tech-Comics-How-to-Rescue-a-Project.htm
class TechComic extends Page {
	private $links = array();
	private $base = 'http://itmanagement.earthweb.com';
	// retrieve list of all links
	public function run() {
		// grabs
		$this->go_line('Previous');
		$this->links[] = $this->url;
		do {
			if ($m = $this->curr_line()->regex_match('/' . self::REG_HREF . '/')) {
				$this->links[] = $m[1];
			}
		} while (!$this->next_line()->contain('content_stop'));
		//print_r($this->links);exit;
		// iterates
		foreach ($this->links as $k => $v) {
			$this->fetch_url($v);
			$this->process();
			while ($this->has_next()) {
				$this->fetch_url($this->get_next());
				$this->process();
			}
		}
	}
	
	// retrieve urls of all comics per link (sometimes multipages)
	public function process() {
		// get the date
		$this->go_line('class="arti_content_photoholder"');
		$raw = $this->curr_line()->cut_between('">', '<');
		$frmtd = date('Y-m-d', strtotime($raw));
		// get the image
		$this->go_line_regex('/' . self::REG_SRC . '\\s+align="center"/');
		$m = $this->curr_line()->regex_match('/' . self::REG_SRC . '/');
		$link = $m[1];
		$text = $frmtd . '_' . basename($link);
		echo "<a href='$link'>$text</a><br />\n";
	}
	
	public function has_next() {
		$this->go_line('<!--content_stop-->');
		$this->go_line('class="pageing2"');
		//echo $this->curr_line()->contain('>Next Page<'); exit;
		return $this->curr_line()->contain('>Next Page<');
	}
	
	public function get_next() {
		$m = $this->curr_line()->regex_match('/' . self::REG_HREF . '>Next Page</');
		return $this->base . $m[1];
	}
}
// $a = new TechComic('http://itmanagement.earthweb.com/cnews/article.php/3929766/Tech-Comics-How-to-Detect-a-Geek.htm');
// $a->run();

// http://tieba.baidu.com/f/tupian?kw=angelababy
// angelababy.xanga.com/ 
// 楊穎 杨颖 Yáng Yǐng February 28, 1989 

/*
http://www.coolpretty.com
http://www.coolpretty.com/%E6%98%8E%E6%98%9F/%E4%B8%AD%E5%9C%8B
http://www.coolpretty.com/%E6%98%8E%E6%98%9F/%E9%A6%99%E6%B8%AF
http://www.coolpretty.com/%E6%98%8E%E6%98%9F/%E6%97%A5%E6%9C%AC
http://www.coolpretty.com/%E6%98%8E%E6%98%9F/%E5%8D%97%E9%9F%93
http://www.coolpretty.com/%E6%98%8E%E6%98%9F/%E6%96%B0%E5%8A%A0%E5%9D%A1
http://www.coolpretty.com/%E6%98%8E%E6%98%9F/%E5%8F%B0%E7%81%A3
http://en.coolpretty.com/
*/ 

/*
http://jailbaitgallery.com/main_gallery.php
http://jailbaitgallery.com/thumbs/JBG02rkmfqbyj.jpg
http://jailbaitgallery.com/resized/JBG02rkmfqbyj.jpg
*/
class JailbaitGallery extends Page {
	public function process() {
	}
	
	public function has_next() {
	}
	
	public function get_next() {
	}
	
}
// $a = new JailbaitGallery('http://jailbaitgallery.com/main_gallery.php');
// $a->run();

// http://regretfulmorning.com/wp-content/gallery/
class RegretfulMorning extends Traverser {
	public function traverse_dir($dirname) {
		if (preg_match('/thumbs/', $dirname)) return false;
		return true;
	}
}
// $a = new RegretfulMorning('http://regretfulmorning.com/wp-content/gallery/');
// $a->set_traverse_opt('n_skip', 1);
// $a->start_traverse();
// $a->run();

// http://www.collegehumor.com/cutecollegegirl/
// http://thechive.com/category/girls/

// http://reonkadena.org/images/
// $a = new Traverser('http://reonkadena.org/images/');
// $a->set_traverse_opt('n_skip', 5);
// $a->run();

class SomeManga extends Page {
	public $prefix;
	public $infix;
	
	public function process() {
		$this->go_line('var pages=');
		$json = $this->curr_line()->dup()->cut_between('[', '];');
		$extract = $json->extract_to_array("img_src:'", "'");
		// print_r($extract);
		foreach ($extract as $e) {
			$name = basename($e);
			echo "<a href='$e'>{$this->prefix}-{$this->infix}-$name</a><br />\n";
		}
	}

}
/* TO ACTIVATE PUT " * / " BELOW
*
$a = new SomeManga('http://somemanga.com/manga/Yakitate_Japan/242/', array(
	'login_first' => array(
		'url' => 'http://somemanga.com/forum/member.php',
		'post' => 'action=do_login&url=http%3A%2F%2Fsomemanga.com%2F&username=captain_kuro&password=fr33p4sc4l&loginsubmit=Login',
	),
));
$a->prefix = 'Yakitate';
$a->infix = '242';
$a->run();
// */

// http://megalife.com.ua/erotic/
// http://megalife.com.ua/erotic/page/2/
function megalife() {
	$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
	$url = "http://megalife.com.ua/erotic/page/$page/";
	$P = new Page($url);
	$P->go_line("id='dle-info'");
	do {if ($P->curr_line()->contain('<h1>')) {
		$puri = $P->curr_line()->dup()
			->cut_between('href="', '"')->to_s();
		$name = Text::create(basename($puri))->cut_before('.')->to_s();
		$Q = new Page($puri);
		$Q->go_line("id='news-id-");
		$raw = $Q->curr_line()->dup()
			->extract_to_array('href="', '"');
		foreach ($raw as $r) {
			echo "<a href='$r'>$name</a><br />\n";
		}
	}} while (!$P->next_line()->contain('</td>'));
	echo "<hr/><a href='?page=".($page-1)."'>Prev</a> || <a href='?page=".($page+1)."'>Next</a>";
}
// megalife();

// http://igologis.blogspot.com/
// http://sekseh-sekali.blogspot.com/
// http://plus-plus17.blogspot.com/
// http://igo-cantik.blogspot.com/
// http://sexilogs.blogspot.com/
function blogspot($url) {
	$selesai = false;
	$cookie = array(
		CURLOPT_COOKIE => '__unam=5458c72-12cbe8ce635-2873791-14; _fc_cookie_323826=1; __qca=P0-1589961172-1300108885430; _fc_cookie_281706=1; __utma=113736600.1712055252.1305685322.1305685322.1305685322.1; __utmz=113736600.1305685322.1.1.utmcsr=plurk.com|utmccn=(referral)|utmcmd=referral|utmcct=/t/Indonesia; _fc_cookie_=1; meebo-cim=channel%3D215; ARMM=%7B%7D; GI=ePcEkjEBAAA.AJBkoyhnlzNv-Yn4SbGHi65EnNl1GjV1AfZD0us9ric.MCJETwFP7wP1EhDofYA2tw; blogger_TID=b621615edda6e096',
		'become_firefox' => true, // blogspot pakai perlindungan user agent
	);
	/*
	// mekanisme save and resume pagination (optional)
	if (is_file('blogspot.page.save')) {
		$url = file_get_contents('blogspot.page.save');
	}
	*/
	
	$entries = array();
	$P = null;
	while (!$selesai) {
		file_put_contents('blogspot.page.save', $url);
		
		if ($P) {
			$P->fetch_url($url);
		} else {
			$P = new Page($url, $cookie);
		}
		
		// echo '<pre>';
		// print_r(htmlentities($P->content()));
		// echo '</pre>';
		
		$P->go_line("id='main-wrapper'");
		do {if ($P->curr_line()->contain("class='post-title")) {
			$line = $P->next_line()->dup();
			$uri = $line->cut_between("href='", "'")->to_s();
			echo $uri."<br/>";
			$entries[] = $uri;
		}} while(!$P->next_line()->contain("id='blog-pager"));

		// file_put_contents('blogspot.title.save', implode("\n",$entries)."\n", FILE_APPEND);
		
		$P->go_line("class='blog-pager-older-link'");
		if ($P->curr_line()->contain("class='blog-pager-older-link'")) {
			$selesai = false;
			$url = $P->curr_line()->dup()
				->cut_between("href='", "'")->to_s();
		} else {
			$selesai = true;
		}
	}
	// unlink('blogspot.page.save');
	
	// ambil entries dari file (optional)
	// $entries = file('blogspot.title.save', FILE_IGNORE_NEW_LINES);
	
	// file_put_contents('igologis.dump', var_export($entries, true));
	foreach ($entries as $e) {
		if ($P) {
			$P->fetch_url($e);
		} else {
			$P = new Page($e, $cookie);
		}
		$title = Text::create(basename($e))->cut_before('.')->to_s();
		$P->go_line("id='main-wrapper'");
		$P->go_line("class='post-body");
		$isi = '';
		do {
			$isi .= $P->curr_line()->to_s();
		} while (!$P->next_line()->contain("class='post-footer"));
		$raw = Text::create($isi)->extract_to_array('href="', '"');
		foreach ($raw as $r) {
			echo "<a href='$r'>$title</a><br/>\n";
		}
	}
}
// blogspot('http://sekseh-sekali.blogspot.com/');

// http://sonokeling.wordpress.com/category/girls-girls-girls/
function sonokeling($u) {
	$from = $_REQUEST['from'] ? $_REQUEST['from'] : 1;
	$many = $_REQUEST['many'] ? $_REQUEST['many'] : 32;
	for ($i = $from; $i < $from+$many; $i++) {
		if ($i == 1) {
			$P = new Page("$u/");
		} else {
			$P = new Page("$u/page/$i/");
		}
		echo "$i ";
		$P->go_line('id="content"');
		$P->go_line('class="pagetitle"');
		$uri = $P->curr_line()->dup()
			->cut_between('href="', '"')->to_s();
		$name = basename($uri);
		echo "$uri<br />\n";
		$Q = new Page($uri);
		$Q->go_line('id="content"');
		$Q->go_line('class="pagetitle"');
		$isi = '';
		do {
			$isi .= $Q->curr_line()->to_s();
		} while (!$Q->next_line()->contain('class="sharing_label"'));
		$raw = Text::create($isi)->extract_to_array('src="', '"');
		foreach ($raw as $r) {
			$Tr = Text::create($r);
			if ($Tr->contain('?')) $Tr->cut_before('?');
			$r = $Tr->to_s();
			echo "<a href='$r'>$name</a><br />\n";
		}
	}
}
// sonokeling('http://sonokeling.wordpress.com/category/art-nude');

function updaterus() {
	$p = new Page('http://www.updaterus.com/');
	$hasil = array();
	include 'updaterus.dump';
	for ($h=0; $h<=23; $h++) {
		for ($m=0; $m<=59; $m++) {
			$p->fetch_url("http://www.updaterus.com/index/upcoming_person/$h/$m");
			$t = json_decode($p->content());
			if (is_array($t)) $hasil = array_merge($hasil, $t);
		}
	}
	$unik = array_unique($hasil);
	file_put_contents('updaterus.dump', "<?php\n\$hasil=".var_export($unik, true).";");
}
function updaterus2() {
	include 'updaterus.dump';
	foreach ($hasil as $e) {
		echo "<a href='http://www.updaterus.com/images/users/$e.jpg'>$e</a><br />\n";
		/* */
		$e = str_replace('/1', '/2', $e);
		echo "<a href='http://www.updaterus.com/images/users/$e.jpg'>$e</a><br />\n";
		$e = str_replace('/2', '/3', $e);
		echo "<a href='http://www.updaterus.com/images/users/$e.jpg'>$e</a><br />\n";
		/* */
	}
}
// updaterus2();

function comicartcommunity($base_url) {
	$site = 'http://www.comicartcommunity.com/gallery/';
	$continue = true;
	$page = 1;
	while ($continue) {
		$url = $base_url . "&page=$page";
		echo "$url<br/>";
		$p = new Page($url);
		$p->go_line('class="imagerow1"');
		do { if ($p->curr_line()->contain('src="') 
				&& $p->curr_line()->contain('href="')
				&& !$p->curr_line()->contain('<!--')) 
			{
			$src = $p->curr_line()->dup()
				->cut_between('src="', '"')
				->replace('/thumbnails/', '/media/')
				->to_s();
			echo "<a href='$site$src'>asdf</a><br />\n";
		}} while(!$p->next_line()->contain('class="paging"'));
		if ($p->curr_line()->contain('class="paging">&raquo;')) {
			
		} else {
			$continue = false;
		}
		$page++;
	}
}
// comicartcommunity('http://www.comicartcommunity.com/gallery/categories.php?cat_id=75');