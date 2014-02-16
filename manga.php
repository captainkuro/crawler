<?php
$title = 'Manga Reader Crawler';

include '_header.php';

interface Manga_Crawler {

	// bool
	public function is_supported($url);

	// bool
	public function is_single_chapter($url);

	// string
	public function get_infix($url);

	// array
	public function get_info($base);

	// array
	public function get_images($chapter_url, $prefix, $infix);

}

function get_crawler($url) {
	// loop through classes
	$scan = scandir('mangas');
	$done = FALSE;

	foreach ($scan as $entry) {
		if ($pos = strpos($entry, '.php')) {
			$classname = substr($entry, 0, $pos);
			include_once 'mangas/'.$entry;
			$object = new $classname;
			if ($object->is_supported($url)) {
				return $object;
			}
		}
	}

	throw new Exception('NOT SUPPORTED');
}

class Manga_Pattern_Manager {

	const FILE = 'manga.pattern.json';

	public $patterns = array();

	public function __construct() {
		$json = json_decode(file_get_contents(self::FILE), TRUE);
		if ($json) {
			$this->patterns = $json;
		}
	}

	public function all() {
		return $this->patterns;
	}

	public function convert_and_add($patterns, $prefixes) {
		$ar_pattern = explode("\n", $patterns);
		$ar_prefix = explode("\n", $prefixes);
		foreach ($ar_pattern as $i => $pattern) {
			$this->patterns[] = array(
				'pattern' => $pattern,
				'prefix' => $ar_prefix[$i],
			);
		}
		$this->save();
	}

	public function del($id) {
		array_splice($this->patterns, $id, 1);
		$this->save();
	}

	public function save() {
		file_put_contents(self::FILE, json_encode($this->patterns));
	}

	public function get_prefix($url) {
		foreach ($this->patterns as $p) {
			if (strpos($url, $p['pattern']) !== FALSE) {
				return $p['prefix'];
			}
		}
		return '';
	}
}

$stage_pattern = FALSE;
$stage_1 = TRUE;
$stage_2 = FALSE;
$stage_3 = FALSE;

if (@$_REQUEST['action'] === 'infix') {
	$url = $_REQUEST['url'];
	$crawler = get_crawler($url);
	echo $crawler->get_infix($url);
	exit;
} else if (@$_REQUEST['action'] === 'prefix') {

	exit;
} else if (@$_REQUEST['action'] === 'pattern') {
	$stage_1 = FALSE;
	$stage_pattern = TRUE;

	$manager = new Manga_Pattern_Manager();
	if (array_key_exists('del_id', $_REQUEST)) {
		$id = $_REQUEST['del_id'];
		$manager->del($id);
	} else if ($_POST) {
		$patterns = $_POST['patterns'];
		$prefixes = $_POST['prefixes'];
		$manager->convert_and_add($patterns, $prefixes);
	}
	$all_patterns = $manager->all();
} else {
	// run
	
}

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

document.addEventListener('DOMContentLoaded', function(){
// When the URL is blurred = grab prefix and infix
});
</script>

<ul class="nav nav-tabs">
	<li><a href="?action=">Manga</a></li>
	<li><a href="?action=pattern">Pattern</a></li>
</ul>
<div class='container'>
	<?php if ($stage_1): ?>
	<form class='form-horizontal' method='post'>
		<fieldset>
			<legend>1</legend>
			<div class='form-group row'>
				<label class='col-sm-3 control-label'>Manga URL</label>
				<div class='col-sm-9 controls'>
					<input type='text' class='form-control' name='base' value="<?=@$_POST['base'];?>" />
				</div>
			</div>
			<div class='form-group row'>
				<label class='col-sm-3 control-label'>Prefix</label>
				<div class='col-sm-4 controls'>
					<input type='text' class='form-control' name='prefix' value="<?=@$_POST['prefix'];?>" />
				</div>
			</div>
			<div class='form-group row'>
				<label class='col-sm-3 control-label'>Infix</label>
				<div class='col-sm-4 controls'>
					<input type='text' class='form-control' name='singlefix' value="<?=@$_POST['singlefix'];?>" placeholder="only for single chapter" />
				</div>
			</div>
			<div class='col-md-offset-3'>
				<button class='btn btn-primary' type='submit' name='stage1'>Submit</button>
			</div>
		</fieldset>
	</form>
	<?php endif; ?>



	<?php if ($stage_2): ?>
	<form class='form-horizontal' method='post'>
		<fieldset>
			<legend>2</legend>
			<div class='form-group row'>
				<label class='col-sm-3 control-label'>Manga URL</label>
				<div class='col-sm-9 controls'>
					<input type='text' class='form-control' name='base' value="<?=@$_POST['base'];?>" />
				</div>
			</div>
			<div class='form-group row'>
				<label class='col-sm-3 control-label'>Prefix</label>
				<div class='col-sm-4 controls'>
					<input type='text' class='form-control' name='prefix' value="<?=@$_POST['prefix'];?>" />
				</div>
			</div>
			<div class='form-group row'>
				<label class='col-sm-3 control-label'>Choose chapter</label>
				<div class='col-sm-8 controls'>
					<div class="col-xs-1">
						<label class='checkbox'>
							<input type='checkbox' name='all' onclick='click_this()' /> All
						</label>
					</div>
					<div class="col-xs-4">
						<input id='parse_it' class='form-control' type='text' />
					</div>
					<div class="col-xs-4">
						<button type='button' onclick='check_it()' class='btn btn-default'>Check</button>
						<button type='button' onclick='uncheck_it()' class='btn btn-default'>Uncheck</button>
					</div>
					<br>
					<table class='table table-condensed'>
						<thead>
							<tr>
								<th class='col-sm-1'>#</th>
								<th class='col-sm-7'>Chapter Name</th>
								<th>Infix</th>
							</tr>
						</thead>
						<tbody>
						<?php
						


						?>
						</tbody>
					</table>
				</div>
			</div>
			<div class='col-md-offset-3'>
				<button class='btn btn-primary' type='submit' name='stage2'>Submit</button>
			</div>
		</fieldset>
	</form>
	<?php endif; ?>



	<?php if ($stage_3): ?>
	<fieldset>
		<legend>3</legend>
	</fieldset>
	<div class='row-fluid'>
		<div class='col-sm-4'>
			asdf
		</div>
	</div>
	<?php endif; ?>
	


	<?php if ($stage_pattern): ?>
	<table class="table">
		<thead>
			<tr>
				<th>#</th>
				<th>Pattern</th>
				<th>Prefix</th>
				<th>X</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($all_patterns as $i => $row): ?>
				<tr>
					<td><?php echo $i; ?></td>
					<td><?php echo $row['pattern']; ?></td>
					<td><?php echo $row['prefix']; ?></td>
					<td><a href="?action=pattern&amp;del_id=<?=$i;?>">Delete</a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<form method="post" action="?action=pattern">
		<div class="col-xs-7">
			<textarea class="form-control" name="patterns" placeholder="URLs" rows="10"></textarea>
		</div>
		<div class="col-xs-4">
			<textarea class="form-control" name="prefixes" placeholder="Prefix-es" rows="10"></textarea>
		</div>
		<button class='btn btn-primary' type='submit' name='add'>Add</button>
	</form>
	<?php endif; ?>

</div>

<?php
include '_footer.php';
