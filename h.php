<?php
/*
Support: (yg H semua)
fakku.net
gallery.hentaifromhell.net
lu.scio.us
imagefap.com
doujin-moe.us
bobx.com
imagearn.com
etc
*/
require_once "crawler.php";
extract($_POST);

function imagefap_realm($base) {
	//http://www.imagefap.com/pictures/2525382/Highly-Erotic-Nude-Models-HQ-Picture
	$sitename = "http://www.imagefap.com";
	$finish = false;
	$firstbase = $base;
	$dir = urldecode(basename($base));
	$i = 1;
	while (!$finish) {
		$c = new Crawler($base);
		echo $base."<br/>";
		$c->go_to(array('<table style=', ':: next ::'));
		if (Crawler::is_there($c->curline, ':: next ::')) {
			$finish = false;
			$urld = Crawler::extract($c->curline, 'href="', '"');
			$base = $firstbase.html_entity_decode($urld);
			$c->go_to('<table style=');
		} else {
			$finish = true;
		}
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, 'border=0')) {
				$img = Crawler::extract($line, 'src="', '"');
				$img = str_replace('/thumb/', '/full/', $img);
				$img = preg_replace('/\/x\d\./', '/', $img);
				$filename = basename($img);
				$ext = Crawler::cutfromlast($filename, '.');
				$text = Crawler::n($i++, 4);
				echo "<a href='$img'>$dir</a><br/>\n";
			} else if (Crawler::is_there($line, '</form>')) {
				break;
			}
		}
		$c->close();
	}
}

function imagefap_list($base) {
// http://www.imagefap.com/profile/jaichwieder/galleries?folderid=-1&page=
	$sitename = "http://www.imagefap.com";
	$p = new Page($base);
	$h = new simple_html_dom();
	$h->load($p->content());
	$table = $h->find('table.blk_galleries', 1);
	foreach ($table->find('a.blk_galleries') as $a) {
		$gal = $sitename . $a->href;
		if (strpos($gal, '/gallery/')) {
			$gal = str_replace('/gallery/', '/pictures/', $gal) . '/' . urlencode($a->text()) . '/';
			imagefap_realm($gal);
		}
	}
}

function imagefap_gate($base) {
	if (strpos($base, '/profile/')) {
		imagefap_list($base);
	} else {
		imagefap_realm($base);
	}
}

function doujinmoe_realm($start_url) {
	// http://www.doujin-moe.us/phpgraphy/index.php?dir=Artists%2FAmatarou%2FDaisy%20%28English%29
	// http://www.doujin-moe.us/phpgraphy/pictures/Artists/Amatarou/Daisy%20(English)/.thumbs/lr_Daisy%20-%20001.jpg
	// http://www.doujin-moe.us/phpgraphy/pictures/Artists/Amatarou/Daisy%20(English)/Daisy%20-%20001.jpg
	$base = 'http://www.doujin-moe.us/phpgraphy/';
	$c = new Crawler($start_url);
	$c->go_to('"slideshow_start_count"');
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, '"slideshow_path"')) {
			// pictures/Artists/Amatarou/Daisy (English)/.thumbs/lr_Daisy - 001.jpg
			$raw = Crawler::extract($line, '">', '</'); 
			// $raw = str_replace('/.thumbs/lr_', '/', $raw); // HQ
			$img = $base . $raw;
			$text = basename(dirname(dirname($raw)));
			// $text = basename($img);
			echo "<a href='$img'>$text</a><br/>\n";
		} else if (Crawler::is_there($line, '<table')) {
			break;
		}
	}
	$c->close();
}

function bobx_realm($start_url) {
	// http://www.bobx.com/av-idol/akiho-yoshizawa/series-akiho-yoshizawa-0-10-10.html
	$base_url = 'http://www.bobx.com/';
	// parse url
	preg_match('/(.*\D\-)(\d+)(\-.*)/', $start_url, $matches);
	$pra = $matches[1];
	$i = $matches[2];
	$pasca = $matches[3];
	$masih = false;
	do {
		$masih = false;
		echo "$start_url<br/>\n";
		$c = new Crawler($start_url, array(
			'use_curl' => true,
			'agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13',
		));
		
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, '<img src="/thumbnail/')) {
				$text = Crawler::extract($line, '<img src="', '"');
				$text = str_replace('/thumbnail/', '', $text);
				$text = str_replace('-preview', '', $text);
				$file = basename($text);
				echo "<a href='{$base_url}{$text}'>{$file}</a><br />\n";
				$masih = true; // Masih ada harapan next page
			} else if (Crawler::is_there($line, 'FULL NAVI:')) {
				break;
			}
		}
		
		$c->close();
		
		$i += 100;
		$start_url = $pra . $i. $pasca;
		//echo $start_url;
	} while ($masih);
}

function imagearn_realm($base) {
	// http://imagearn.com/gallery.php?id=102868
	// http://thumbs2.imagearn.com/05072010/4099544.jpg
	// http://img.imagearn.com/imags/05072010/4099544.jpg
	$sitename = "http://imagearn.com";
	$c = new Crawler($base);
	$c->go_to('class="galleries"');
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, 'src="')) {
			$img = Crawler::extract($line, 'src="', '"');
			$img = str_replace('thumbs2.', 'img.', $img);
			$img = str_replace('.com/', '.com/imags/', $img);
			$name = basename($img);
			echo "<a href='$img'>$name</a><br />\n";flush();
		} else if (Crawler::is_there($line, 'class="clear"')) {
			break;
		}
	}
	$c->close();
}

function ichan_realm($start_url) {
	// http://ichan.org/l/res/33005.html
	// <a href="http://c3a56840.linkbucks.com/url/http://ichan.org/l/src/128001023914.jpg">
	$c = new Crawler($start_url);
	$i = 0;
	$c->go_to('class="filesize"');
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, '<a href') && Crawler::is_there($line, 'http://ichan.org/') && Crawler::is_there($line, '/src/')) {
			$raw = Crawler::extract($line, '"', '"');
			$img = preg_replace('/http:\/\/[\w\.]+\/url\//', '', $raw);
			$text = Crawler::n(++$i, 3) . '.jpg';
			echo "<a href='$img'>$text</a><br/>\n";
		} else if (Crawler::is_there($line, '"footerbg"')) {
			break;
		}
	}
	$c->close();
}

function hbrowse_realm($start_url) {
	// http://www.hbrowse.com/10570
	// http://www.hbrowse.com/thumbnails/10570/c00000
	// http://www.hbrowse.com/data/10570/c99999/19.jpg
	// http://www.hbrowse.com/data/10570/c99999/zzz/19.jpg
	$c = new Crawler($start_url);
	// ambil title
	$c->go_to('>Title<');
	$title = trim(Crawler::cutfrom1($c->curline, ': '));
	$c->go_to('class="listEntry"');
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, '/thumbnails/')) {
			$link = Crawler::extract($line, 'class="thumbLink" href="', '"');
			echo "$link<br/>\n";
			$x = new Crawler($link);
			$x->go_to('id="main"');
			while ($line = $x->readline()) {
				if (Crawler::is_there($line, '/zzz/')) {
					$raw = Crawler::extract($line, 'src="', '"');
					$img = str_replace('/zzz/', '/', $raw);
					//$text = basename($link) . '-' . basename($img);
					$text = $title;
					echo "<a href='$img'>$text</a><br/>\n";
				} else if (Crawler::is_there($line, '</table>')) {
					break;
				}
			}
			$x->close();
		} else if (Crawler::is_there($line, '</table>')) {
			break;
		}
	}
	$c->close();
}

function doujintoshokan_realm($start_url) {
	// http://www.doujintoshokan.com/series/Naburi
	// http://www.doujintoshokan.com/read/Naburi/Desudesu/v._2
	$opt = array(
		'use_curl' => true,
		'agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13',
		'cookie' => 'filter=0; mt_sessionhash=1be0760d9c45201308fe98665f5d520f; mt_lastvisit=1295635463; mt_lastactivity=0; __utma=51216956.198413083.1295637122.1295637122.1295637122.1; __utmb=51216956.3.10.1295637122; __utmc=51216956; __utmz=51216956.1295637122.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none)',
	);
	$base = 'http://www.doujintoshokan.com';
	$title = basename($start_url);
	$c = new Crawler($start_url, $opt);
	// Get list of chapters
	$chs = array();
	$chaptertext = array();
	$c->go_to("class='cont_mid'");
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, 'oLoc("')) {
			$chs[] = Crawler::extract($line, 'oLoc("', '"');
			$line = $c->readline();
			$line = $c->readline();
			$chaptertext[] = Crawler::extract($line, '">', '</a>');
		} else if (Crawler::is_there($line, '</tr></table>')) {	
			break;
		}
	}
	$c->close();
	$chs = array_reverse($chs);
	$chaptertext = array_reverse($chaptertext);
	
	// Open each of them
	foreach ($chs as $i => $url) {
		$subtitle = $chaptertext[$i];
		$c = new Crawler($base . $url, $opt);
		$c->go_to('class="headerSelect"');
		$c->readline();
		$pages = Crawler::extract_to_array($c->curline, 'value="', '"');
		$c->close();
		
		foreach ($pages as $page) {
			$c = new Crawler($base . $page, $opt);
			$c->go_to('id="readerPage"');
			$img = Crawler::extract($c->curline, 'src="', '"');
			// $text = basename($img);
			$text = $subtitle;
			echo "<a href='$img'>$text</a><br />\n";
			$c->close();
		}
		
	}
}

// http://www.hentairules.net/gal/_2010/isao_majimeya_pack_of_6_works.html
function hentairules_realm($url) {
	$raw = file_get_contents($url);
	$hrefs = Crawler::extract_to_array($raw, 'href="', '"');
	foreach ($hrefs as $i => $href) {
		$link = str_replace('http://www.hentairules.net/gal/re.php?redir=', '', $href);
		echo "<a href='$link'>$i</a><br />\n";
	}
}

// http://gallery.ryuutama.com/view.php?manga=386
function ryuutama_realm($url) {
	$base = 'http://gallery.ryuutama.com/';
	// $api = "http://gallery.ryuutama.com/api.php?grab=manga&id=%s&page=1&cache=%s";
	$api = "http://gallery.ryuutama.com/api.php?grab=newManga&id=%s";
	$c = new Crawler($url);
	$c->go_to('manga = "');
	preg_match('/manga = "([^"]+)"/', $c->curline, $m);
	$id = $m[1];
	$c->go_to('total_pages = "');
	preg_match('/total_pages = "([^"]+)"/', $c->curline, $m);
	$n = $m[1];
	$c->close();
	// obtain all images
	$apiurl = sprintf($api, $id, $n);
	$c = new Crawler($apiurl);
	$images = json_decode($c->curline);
	$c->close();
	
	foreach ($images as $obj) {
		$src = $obj->picture_filename;
		$name = basename(dirname($src));
		echo "<a href='$base$src'>$name</a><br />\n";
	}
}

// http://www.animephile.com/hentai/full-metal-panic.html?lzkfile=Full+Metal+Panic!%2FFull+Metal%2F
function animephile_realm($url) {
	$base = 'http://www.animephile.com';
	$name = basename($url);
	$name = Crawler::cutuntil($name, '.');
	if (strpos($url, '/hentai-doujinshi/')) {
		$c = new Crawler($url);
		$c->go_to('id="mainimage"');
		preg_match('/"viewerLabel"> of (\d+)<\//', $c->curline, $m);
		$max = $m[1];
		for ($i=1; $i<=$max; $i++) {
			$c = new Crawler($url.'?page='.$i);
			$c->go_to('id="mainimage"');
			// current image
			preg_match('/id="mainimage" src="([^"]+)"/', $c->curline, $m);
			$r = $m[1];
			echo "<a href=\"$base$r\">$name</a><br />\n";
		}
	} else {
		$c = new Crawler($url);
		$c->go_to('id="gallery"');
		$raw = Crawler::extract_to_array($c->curline, 'src="', '"');
		foreach ($raw as $r) {
			$r = str_replace('/thumbs/', '/', $r);
			$name = basename(dirname($r));
			echo "<a href=\"$r\">$name</a><br />\n";
		}
	}
}

// http://thedoujin.com/index.php?page=post&s=list&tags=parent:715616
// http://www.thedoujin.com/thumbnails/462/thumbnail_ebe3a4ff53cf36125308e5760a72ae1567d37576.jpg?715616
// http://img1.thedoujin.com//images/462/ebe3a4ff53cf36125308e5760a72ae1567d37576.jpg?715616

function thedoujin_realm($url) {
	$c = new Crawler($url);
	$c->go_to('class="content"');
	while ($line = $c->readline()) {
		if (Crawler::is_there($line, 'src=')) {
			$thumb = Crawler::extract($line, 'src="', '"');
			$img = str_replace('/www.', '/img1.', $thumb);
			$img = str_replace('/thumbnails/', '/images/', $img);

			$img = str_replace('/thumbnail_', '/', $img);
			$name = basename($img);
			echo "<a href='$img'>$name</a><br />\n";
		} else if (Crawler::is_there($line, 'id="paginator"')) {
			break;
		}
	}
	$c->close();
}

function old_hfh_realm($url) {
	$name = basename($url);
	$c = new Crawler($url);
	$exp = Crawler::extract_to_array($c->curline, 'href="', '"');
	foreach ($exp as $e) {
		$img = preg_replace('/^.*redirect\.html\?/', '', $e);
		echo "<a href='$img'>$name</a><br/>\n";
	}
}

function rule34($url) {
	$text = rawurldecode(basename(dirname($url)));
	$site = 'http://rule34.paheal.net';
	$continue = true;
	while ($continue) {
		echo "$url<br/>";
		$c = new Crawler($url);
		$c->go_to("id='Navigationleft'");
		// $c->readline();
		// $c->readline();
		$line = $c->curline;
		if (preg_match('/<a href="([^\'"]+)">Next/', $line, $m)) {
			$url = $site . $m[1];
		} else {
			$continue = false;
		}
		$c->go_to("id='image-list'");
		while ($line = $c->readline()) {
			if (Crawler::is_there($line, '>Image Only<')) {
				$href = Crawler::extract($line, '<br><a href="', '"');
				echo "<a href='$href'>$text</a><br/>\n";
			} else if (Crawler::is_there($line, '<footer>')) {
				break;
			}
		}
	}
	
}

function sankakucomplex($url) {
	if (strpos($url, '/idol.')) {
		$base = 'https://idol.sankakucomplex.com';
	} else {
		$base = 'https://chan.sankakucomplex.com';
	}
	$page = 1;
	$tag = uniqid();
	$Turl = Text::create($url);
	if ($Turl->contain('tags=')) {
		$tag = $Turl->cut_after('tags=')->urldecode()->to_s();
	}
	do {
		if (isset($_GET['limit'])) {
			if ($page > $_GET['limit']) break;
		}
		$purl = $url.'&page='.$page;
		echo "$purl<br>\n";
		do {
			$P = new Page($purl, array('become_firefox'=>true));
			$T = new Text($P->content());
			sleep(3); // 429 too many requests
		} while ($T->contain('429 Too many requests'));
		$a = $T->extract_to_array('href="', '"');
		foreach ($a as $i => $e) {
			$E = new Text($e);
			if (!$E->contain('/post/show')) {
				unset($a[$i]);
			}
		}
		if (!count($a)) break;
		foreach ($a as $i => $e) {
			$E = new Text($e);
			$kurl = $base . $e;
			echo "$kurl<br>\n";flush();
			do {
				$P = new Page($kurl, array('become_firefox'=>true));
				$T = new Text($P->content());
				sleep(3); // 429 too many requests
			} while ($T->contain('429 Too many requests'));
			// $P->go_line('id="highres"');
			if (isset($_GET['hires'])) {
				$P->go_line('id=highres');
			} else {
				$P->go_line('id=lowres');
			}
			if ($P->end_of_line()) {
				$P->reset_line();
				$P->go_line('id=highres');
			}
			$img = $P->curr_line()->cut_between('href="', '"')->to_s();
			// $P->reset_line();
			// $P->go_line('id="post_old_tags"');
			// $tag = $P->curr_line()->cut_between('value="', '"')->substring(0, 150)->to_s(); // max 100 karakter
			if ($img) {
				echo "<a href='$img'>$tag</a><br />\n";flush();
			} else {
				echo "This is flash<br />\n";
			}
		}
		$page++;
	} while (true);
}

function readhentaionline($url) {
// http://readhentaionline.com/read-gohoushi-ayanami-san-hentai-manga-online/
	$base = 'http://readhentaionline.com';
	$chunk = basename($url);
	preg_match('/read-(.+)-hentai-manga-online/', $chunk, $m);
	$title = $m[1];
	
	$p = new Page($url);
	$p->go_line('id="gallery"');
	$url = $base . $p->next_line()->dup()
		->cut_between('href="', '"')
	->to_s();
	$m = $p->curr_line()->regex_match('/Total No of Images in Gallery: (\d+)/');
	$pages = $m[1];
	
	$p = new Page($url);
	$t_content = new Text($p->content());
	$raw = $t_content->extract_to_array('src="', '"');
	// search for image
	foreach ($raw as $e) {
		if (preg_match('/1\.jpg$/', $e)) {
			$src = $e;
			break;
		}
	}
	if (!isset($src)) throw new Exception('Image not found');
	$img_dir = dirname($src);
	for ($i=1; $i<=$pages; $i++) {
		echo "<a href='$img_dir/$i.jpg'>$title</a><br/>\n";
	}
}

function yandere($url) {
// https://yande.re/post?tags=rating%3Ae+uncensored+sex+
	$turl = $url;
	for ($i=1; $i<=16; $i++) {
		if ($i > 1) $turl = $url . '&page=' .$i;
		$p = new Page($turl, array(
			// CURLOPT_CERTINFO => true,
			CURLOPT_SSL_VERIFYPEER => false,
		));
		$p->go_line('Post.register(');
		do {if ($p->curr_line()->contain('Post.register')) {
			$json = $p->curr_line()->dup()
				->cut_between('Post.register(', '})')
				->to_s()
			;
			$obj = json_decode($json.'}');
			
			echo "<a href='{$obj->jpeg_url}'>{$obj->tags}.jpg</a><br>\n";
		}} while (!$p->next_line()->contain('</script>'));
		
	}
}

function hentaifromhell($url) {
// mungkin galeri langsung di halaman ini, http://hentaifromhell.net/shiden-akira-candy-girl/
// atau ada link ke galeri, // http://hentaifromhell.net/miyabi-tsuzuru-rough-sketch-rough-playing/
// atau tidak ada galeri, // http://hentaifromhell.net/1st-week-of-homestay/
	if (strpos($url, 'gallery.php')) {
		return hfh_gal($url);
	}
	$name = basename($url);
	$p = new Page($url);
	$p->go_line('alt="Gallery"');
	if ($p->curr_line()->contain('href="')) {
		$gal = $p->curr_line()->dup()
			->cut_between('href="', '"')
			->to_s();
		return hfh_gal($gal);
	}
	$p->reset_line();
	$p->go_line("id='gallery-1'");
	if ($p->curr_line()->contain("id='gallery-1'")) {
		do {
			$line = $p->curr_line();
			if ($line->contain('src="')) {
				$src = $line->dup()
					->cut_between('src="', '"')
					->regex_replace('/-\d+x\d+\./', '.')
					->to_s();
				echo "<a href='$src'>$name</a><br>\n";
			} else if ($line->contain('class="gallery_pages_list"')) {
				// grab last page
				// iterate from page 2
				$pages = $line->extract_to_array('">', '</');
				$last = (int)end($pages);
				for ($i=2; $i<=$last; $i++) {
					$aurl = $url.'?galleryPage='.$i;
					$ap = new Page($aurl);
					$ap->go_line("id='gallery-1'");
					do {
						$line = $ap->curr_line();
						if ($line->contain('src="')) {
							$src = $line->cut_between('src="', '"')
								->regex_replace('/-\d+x\d+\./', '.')
								->to_s();
							echo "<a href='$src'>$name</a><br>\n";
					}} while (!$ap->next_line()->contain('</div>'));
				}
			}
		} while (!$p->next_line()->regex_match('/^<\/div>/'));
		return;
	}
	echo "No gallery <a href='$url'>link</a>";
	return;
}

function hfh_gal($gal) {
	$origal = $gal;
	$name = rawurldecode(basename(dirname($gal)));
	$masih = true;
	$base = 'http://hentaifromhell.net';
	$pn = 0;
	$totimage = null;
	while ($masih) {
		echo $gal.'<br>';
		$g = new Page($gal);
		if (!isset($totimage)) {
			$g->go_line('Images <strong>');
			$m = $g->curr_line()->regex_match('/of <strong>(\d+)<\/strong>/');
			$totimage = $m[1];
			$g->reset_line();
		}
		$g->go_line('<img');
		do { if ($g->curr_line()->contain('src="')) {
			$src = $g->curr_line()->dup()
				->cut_between('src="', '"')
				->regex_replace('/_thmb\.jpg$/', '')
				->to_s();
			echo "<a href='$base$src'>$name</a><br>\n";
		}} while (!$g->next_line()->contain('</table>'));
		// @TODO
		if ($pn+20 > $totimage) {
			$masih = false;
		} else {
			$pn += 20;
			$gal = $origal.'?pn='.$pn;
		}
	}
}

function rule34xxx($url) {
// img2.rule34.xxx/rule34/thumbnails/1202/thumbnail_fc0d335a14ffbdbb861bdabb8afd8bd6.jpeg?1228439
// http://img.rule34.xxx/rule34//images/1202/fc0d335a14ffbdbb861bdabb8afd8bd6.jpeg

	$continue = true;
	$domain = 'http://rule34.xxx/';
	$base = 'http://rule34.xxx/index.php';
	$tags = Text::create($url)->regex_match('/tags=([^&]+)/');
	$tags = $tags[1];
	do {
		echo $url."<br>\n";
		$p = new Page($url);
		$p->go_line('class="thumb"');
		do { if ($p->curr_line()->contain('href="')) {
			$href = $p->curr_line()
				->cut_between('href="', '"')
				->to_s();
			$href = htmlspecialchars_decode($href);
			echo "$domain$href<br>\n";
			$p2 = new Page($domain . $href);
			$p2->go_line('Original image');
			$src = $p2->curr_line()
				->cut_between('href="http:', '"');
// echo '<pre>'.htmlspecialchars($p2->curr_line()).'</pre>';
			echo "<a href='$src'>$tags</a><br>\n";
		}} while (!$p->next_line()->contain('<center>'));
		$p->reset_line();
		$p->go_line('id="paginator"');
		if ($p->curr_line()->contain('alt="next"')) {
			$m = $p->curr_line()->regex_match('/href="([^"]+)" alt="next"/');
			$url = $base.html_entity_decode($m[1]);
		} else {
			$continue = false;
		}
	} while ($continue);
}

function pururin($url) {
// http://pururin.com/hentai-manga/1673/pai-zuri.html
// http://pururin.com/hentai-manga/1673/gallery/pai-zuri_1.html gallery 
// http://pururin.com/hentai-manga/1673/gallery/pai-zuri_2.html gallery next page
// http://pururin.com/hentai-manga/1673/view/pai-zuri_1.html view full image
	$base = 'http://pururin.com';
	$title = substr(basename($url), 0, -5);
	// if (strpos($url, '_1.html') === false) {
	// 	$url = dirname($url) . '/gallery/' . str_replace('.html', '_1.html', basename($url));
	// }
	$url = str_replace('/gallery/', '/thumbs/', $url);
	// collect more than 100 images
	$next = true;
	$i = 1;
	while ($next) {
		$p = new Page($url);
		$p->go_line('class="thumblist"');
		$hrefs = $p->next_line()->extract_to_array('href="', '"');
		foreach ($hrefs as $href) {
			$q = new Page($base.$href);
			$q->go_line('class="b"');
			$src = $q->curr_line()->cut_between('src="', '"');
			echo "<a href='$base$src'>$title</a><br>\n";
		}
		// $thumbs = $p->next_line()->extract_to_array('src="', '"');
		// foreach ($thumbs as $k => $v) {
		// 	$f = preg_replace('/([^-]+)t\//', '$1f/', $v);
		// 	echo "<a href='$base$f'>$title</a><br>\n";
		// }
		//now all in 1 page
		$next = false;
		/*
		$p->go_line('class="thumbnail_list"');
		do { if ($p->curr_line()->contain('class="pageNumber"')) {
			$href = $p->curr_line()->dup()->cut_between('href="', '"')->to_s();
			$p2 = new Page($base . $href);
			$p2->go_line('id="i1"');
			$src = $p2->curr_line()->dup()->cut_between('src="', '"')->to_s();
			echo "<a href='$base$src'>$title</a><br>\n";
		}} while (!$p->next_line()->contain('class="clear"'));
		if (strpos($p->content(), '">&rsaquo;</a>') === false) {
			$next = false;
		}
		*/
		$url = str_replace('_'.$i.'.html', '_'.($i+1).'.html', $url);
		$i++;
	}
}

function neechan($url) {
// http://neechan.net/Crystal+Break/1/
	$title = basename(dirname($url)).'-'.basename($url);
	$p = new Page($url);
	$p->go_line('Pages navigation:');
	$part = $p->curr_line()->cut_after('Pages navigation:');
	$pages = $part->extract_to_array('href="', '"');
	$pages = array_unique($pages);
	foreach ($pages as $puri) {
		if (strpos($puri, 'javascript') !== FALSE) continue;
		$q = new Page($puri);
		$q->go_line('id="img_mng_enl"');
		$src = $q->curr_line()->dup()->cut_between('id="img_mng_enl" src="', '"')->to_s();
		echo "<a href='$src'>$title</a><br>\n";
	}
	// $dir = dirname($src) . '/';
	// $p2 = new Page($dir);
	// $p2->go_line('href="');
	// while ($p2->next_line()->contain('href="')) {
	// 	$fname = $p2->curr_line()->dup()->cut_between('href="', '"')->to_s();
	// 	echo "<a href='$dir$fname'>$title</a><br>\n";
	// }
}

function therief_sextgem($url) {
	$p = new Page($url);
	$p->go_line('gbr apm');
	$ar = $p->curr_line()->extract_to_array('&url=', '"');
	foreach ($ar as $key => $value) {
		echo "<a href='$value'>therief</a><br>\n";
	}

}

// http://hentai2read.com/temple_is_best/1/
function hentai2read($url) {
	$title = basename(dirname($url)).'-'.basename($url);
	$p = new Page($url);
	$p->go_line('wpm_mng_rdr_img_lst');
	$json = $p->curr_line()->cut_between(' = ', ';')->to_s();
	$images = json_decode($json);
	foreach ($images as $src) {
		echo "<a href='$src'>$title</a><br>\n";
	}
}

// http://www.fakku.net/manga/a-sacrifice-to-the-lustbug-english/read
// http://www.fakku.net/manga/a-sacrifice-to-the-lustbug-english
// https://t.fakku.net/images/manga/a/%5BFan_no_Hitori%5D_Original_Work_-_A_Sacrifice_to_the_Lustbug/thumbs/001.thumb.jpg
// https://t.fakku.net/images/manga/a/%5BFan_no_Hitori%5D_Original_Work_-_A_Sacrifice_to_the_Lustbug/images/001.jpg
function fakku($url) {
	if (!preg_match('/\/read$/', $url)) {
		$url .= '/read';
	}
	$title = basename(dirname($url));
	$p = new Page($url);
	$content = new Text($p->content());
	$p->go_line('window.params.thumbs');
	$json = $p->curr_line()->cut_between('=', ';')->to_s();
	$js_thumbs = json_decode($json);
	foreach ($js_thumbs as $thumb) {
		$src = Text::create($thumb)->replace('.thumb.', '.')->replace('/thumbs/', '/images/')->to_s();
		echo "<a href='$src'>$title</a><br>\n";
	}
}

// http://nhentai.net/g/113127/
function nhentai($url) {
	$p = new Page($url);
	$h = new simple_html_dom();
	// echo htmlspecialchars($p->content());exit;
	$h->load($p->content());

	$title = $h->find('#info', 0)->find('h1', 0);
	$title = html_entity_decode($title->innertext());
	$title = substr($title, 0, 100);

	$container = $h->find('#thumbnail-container', 0);
	foreach ($container->find('.lazyload') as $spinner) {
		$src = $spinner->getAttribute('data-src');
		$src = Text::create($src)
			->regex_replace('#t\.(.{3})$#', '.${1}')
			->replace('t.nhentai.net', 'i.nhentai.net')
			->to_s();
		echo "<a href='$src'>$title</a><br>\n";
	}
}

// http://www.tsumino.com/Book/Info/164/1/toaru-majutsu-no-kyousei-jusei-1-1
function tsumino($url) {
	$domain = 'http://www.tsumino.com';
	$optionUrl = 'http://www.tsumino.com/Read/Load?q=';
	$imageUrl = 'http://www.tsumino.com/Image/Object?name=';

	$p = new Page($url);
	$body = $p->content();

	preg_match('#/Info/([^/]+)/#', $url, $m);
	$id = $m[1];
	preg_match('#/Info/[^/]+/1/(.+)$#', $url, $m);
	$title = $m[1];

	preg_match("#baseReaderUrl = '([^']+)'#", $body, $m);
	$baseReaderUrl = $m[1];
	preg_match("#replace = '([^']+)'#", $body, $m);
	$replace = $m[1];

	$objects = json_decode(file_get_contents($optionUrl . $id), true);
	foreach ($objects['reader_page_urls'] as $name) {
		$src = $imageUrl . urlencode($name);
		echo "<a href='$src'>$title</a><br>\n";
	}
}

// http://www.porncomix.info/shadbase-overwatch/
function porncomix($url) {
	$p = new Page($url);
	$h = new simple_html_dom();
	$h->load($p->content());
	// print_r($p->content())

	$title = $h->find('.post-title', 0)->innertext;

	foreach ($h->find('.attachment-thumbnail') as $img) {
		$src = $img->getAttribute('data-lazy-src');
		if (!$src) continue;
		$tsrc = new Text($src);
		$src = $tsrc->regex_replace('#-\d+x\d+\.#', '.')->to_s();
		echo "<a href='$src'>$title</a><br>\n";
	}
}

?>
<html>
<body>
	I support many H sites
	<form action="" method="post">
		Start url: <input type="text" name="start_url" value="<?=isset($start_url)?$start_url:''?>" />
		<input type="submit" value="Submit" />
	</form>
<?php 
if ($_POST) {
	$parsed = parse_url($start_url);
	// based on the $start_url, call appropriate function
	if (preg_match('/hentairules\.net\/gal\//', $start_url)) {
		hentairules_realm($start_url);
	} else {
		// Simple mapping host => function
		$map = array(
			'imagefap.com' => 'imagefap_gate',
			'doujin-moe.us' => 'doujinmoe_realm',
			'bobx.com' => 'bobx_realm',
			'imagearn.com' => 'imagearn_realm',
			'ichan.org' => 'ichan_realm',
			'hbrowse.com' => 'hbrowse_realm',
			'doujintoshokan.com' => 'doujintoshokan_realm',
			'gallery.ryuutama.com' => 'ryuutama_realm',
			'animephile.com' => 'animephile_realm',
			'thedoujin.com' => 'thedoujin_realm',
			'rule34.paheal.net' => 'rule34',
			'sankakucomplex.com' => 'sankakucomplex',
			'readhentaionline.com' => 'readhentaionline',
			'yande.re' => 'yandere',
			'hentaifromhell.net' => 'hentaifromhell',
			'rule34.xxx' => 'rule34xxx',
			'pururin.com' => 'pururin',
			'neechan.net' => 'neechan',
			'therief.sextgem.com' => 'therief_sextgem',
			'hentai2read.com' => 'hentai2read',
			'fakku.net' => 'fakku',
			'nhentai.net' => 'nhentai',
			'tsumino.com' => 'tsumino',
			'porncomix.info' => 'porncomix',
		);
		$found = false;
		foreach ($map as $host => $func) {
			if (Crawler::is_there($parsed['host'], $host)) {
				$func($start_url);
				$found = true;
				break;
			}
		}
		if (!$found) echo 'Not supported yet';
	}
}
?>
</body>
</html>