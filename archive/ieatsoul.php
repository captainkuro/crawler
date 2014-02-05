<?php
// require otomatis oleh index.php
extract($_POST);
$basic_url = 'http://reader.ieatsoul.com/en/read/';
$switch_url = 'http://reader.ieatsoul.com/en/switch-manga/';
$domain = 'http://reader.ieatsoul.com';
/*
URL awal bisa seperti ini
	http://reader.ieatsoul.com/en/read/defense-devil/610/100/1/
	defense-devil = manga title
	610 = chapter id
	100 = chapter value
	1 = current page
Untuk pindah chapter, harus mengirim POST ke url seperti ini
	http://reader.ieatsoul.com/en/switch-manga/defense-devil/610/7/1/100/
	7 = manga id
	POST data: 
		chapter_form[chapters] = 99 // chapter value tujuan
		chapter_form[_csrf_token] = 28e42bdc38eae8ec0957dd05fc60fea6 // cukup sekali ambil
Yang akan mengarahkan ke URL seperti ini
	http://reader.ieatsoul.com/en/read/defense-devil/557/99/1/
	557 = chapter id
Kenapa proses serumit ini? karena angka 557 ini ga ada di html

UPDATE: ternyata cukup mengunjungi halaman manganya maka akan dapat list chapter
	http://reader.ieatsoul.com/en/manga/7/defense-devil/
ga perlu proses di atas
*/

function break_url($url) {
	preg_match('/read\/([^\/]+)\/([^\/]+)\/([^\/]+)\/([^\/]+)/', $url, $m);
	list($all, $title, $chapter_id, $chapter_text, $page) = $m;
	return array(
		'all' => $all,
		'title' => $title,
		'chapter_id' => $chapter_id,
		'chapter_text' => $chapter_text,
		'page' => $page,
	);
}
?>

<html><body>
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

<h1>1</h1>
<form method="post">
	e.g.: http://reader.ieatsoul.com/en/manga/7/defense-devil/<br/>
	URL FOLDER: <input type="text" name="base" value="<?php echo $base; ?>"/><br />
	Prefix: <input type="text" name="prefix" value="<?php echo $prefix; ?>"/><br />
	Infix: <input type="text" name="infiks" value="<?php echo $infiks; ?>" /> if filled, means only 1 chapter<br/>
	<input type="submit" name="stage1"/>
</form>


<?php if (isset($stage1) || isset($stage2)) :?>
<h1>2</h1>
<form method="post">
	URL FOLDER: <input type="text" name="base" value="<?php echo $base; ?>"/><br />
	Prefix: <input type="text" name="prefix" value="<?php echo $prefix; ?>"/><br />
	<div>Choose volume/chapter to be downloaded:</div>
	<input type="checkbox" name="all" value="all" onclick="click_this()"/>All<br/>
	<table>
		<tr>
			<th>Chapter Name</th>
			<th>Infix</th>
		</tr>
	<?php
if (isset($stage1)) {
	// easier
	// http://reader.ieatsoul.com/en/manga/7/defense-devil/
	$P = new Page($base);
	$P->go_line('class="manga_list"');
	$list = array();
	do {if ($P->curr_line()->contain('href="')) {
		$line = $P->curr_line()->dup();
		$href = $line->dup()->cut_between('href="', '"');
		$desc = $line->dup()->cut_between('">', '</a');
		preg_match('/\/([^\/]+)\/1\/$/', $href, $m);
		$chap = $m[1];
		$list[$chap] = array(
			'href' => $domain.$href,
			'desc' => $desc,
			'chap' => $chap,
		);
	}} while (!$P->next_line()->contain('</ul>'));
	foreach ($list as $k => $v) {?>
		<tr>
			<td>
				<input type="checkbox" name="info[<?php echo $k; ?>][check]" value="<?php echo $k; ?>" />
				<?php echo $v['desc']; ?>
				<input type="hidden" name="info[<?php echo $k; ?>][url]" value="<?php echo $v['href']; ?>" />
				<input type="hidden" name="info[<?php echo $k; ?>][desc]" value="<?php echo $v['desc'] ?>" />
			</td>
			<td><input type="text" name="info[<?php echo $k; ?>][infix]" value="<?php echo $k; ?>" /></td>
		</tr>
	<?php }
} else {
	foreach ($info as $k => $v) {if (isset($v['check'])) {?>
		<tr>
			<td>
				<input type="checkbox" name="info[<?php echo $k; ?>][check]" value="<?php echo $k; ?>" />
				<?php echo $v['desc']; ?>
				<input type="hidden" name="info[<?php echo $k; ?>][url]" value="<?php echo $v['url']; ?>" />
				<input type="hidden" name="info[<?php echo $k; ?>][desc]" value="<?php echo $v['desc']; ?>" />
			</td>
			<td><input type="text" name="info[<?php echo $k; ?>][infix]" value="<?php echo $v['infix']; ?>" /></td>
		</tr>
	<?php } else {
		unset($info[$k]);
	}}
}
	?>
	</table>
	<input type="submit" name="stage2"/>
</form>

<?php endif; ?>

<?php if (isset($stage2)) : ?>
<h1>3</h1>
<?php foreach ($info as $k => $v) {
	$ifx = Text::create($v['infix'])->pad(3)->to_s();
	$b = break_url($v['url']);
	extract($b);
	
	$P = new Page($v['url']);
	// Grab all pages
	$pages = array();
	$P->go_line('id="Serie_pages"');
	do {if ($P->curr_line()->contain('<option')) {
		$pages[] = $P->curr_line()->dup()
			->cut_between('">', '</')->to_s();
	}} while(!$P->next_line()->contain('</select>'));
	array_shift($pages);
	// Grab this page's image
	$P->go_line('id="manga_img"');
	$src = $P->curr_line()->dup()
		->cut_between('src="', '"')->to_s();
	$name = basename($src);
	echo "<a href='$domain$src'>$prefix-$ifx-$name</a><br/>\n";
	// Now for the other pages
	foreach ($pages as $p) {
		$the_url = "$basic_url$title/$chapter_id/$chapter_text/$p/";
		$P = new Page($the_url);
		$P->go_line('id="manga_img"');
		$src = $P->curr_line()->dup()
			->cut_between('src="', '"')->to_s();
		$name = basename($src);
		echo "<a href='$domain$src'>$prefix-$ifx-$name</a><br/>\n";
	}
}
?>
<?php endif;?>

</body></html>