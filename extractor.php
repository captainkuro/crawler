<?php
include '_header.php';
?>
<div class="container">
	<h1>Extract [COLUMNS] from [N] last pages at [URL]</h1>
	<form method="post" class="form-horizontal">
		<div class="form-group">
			<label class="col-sm-2 control-label">COLUMNS</label>
			<div class="col-sm-10">
				<input type="text" name="columns" class="form-control" placeholder="title,desc,image" value="<?=@$_POST['columns'];?>">
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label">N</label>
			<div class="col-sm-10">
				<input type="text" name="n" class="form-control" placeholder="10" value="<?=@$_POST['n'];?>">
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label">URL</label>
			<div class="col-sm-10">
				<input type="text" name="url" class="form-control" placeholder="http://www.rlsbb.com/category/tv-shows/" value="<?=@$_POST['url'];?>">
			</div>
		</div>
		<div class="col-sm-offset-2">
			<button class="btn btn-primary">Go</button>
		</div>
	</form>

<?php

interface Extractor {

	public function can_extract($url);

	public function extract($columns, $s, $n, $url);
}

function print_result($cols, $result) {
?>
	<table class="table table-hover">
		<thead>
			<tr>
				<th>#</th>
				<?php foreach ($cols as $col): ?>
					<th><?=$col;?></th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($result as $i => $row): ?>
				<tr>
					<td><?=$i+1;?></td>
					<?php foreach ($cols as $col): ?>
						<td><?=$row[$col];?></td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php
}

if ($_POST) {

	extract($_POST);
	$cols = array_map('trim', explode(',', $columns));
	if (strpos($n, ',')) {
		list($s, $n) = explode(',', $n);
	} else {
		$s = 1;
	}

	// loop through classes
	$scan = scandir('extractors');
	$done = FALSE;

	foreach ($scan as $entry) {
		if ($pos = strpos($entry, '.php')) {
			$classname = substr($entry, 0, $pos);
			include 'extractors/'.$entry;
			$object = new $classname;
			if ($object->can_extract($url)) {
				echo 'Calling '.$classname;
				$result = $object->extract($cols, $s, $n, $url);
				print_result($cols, $result);
				$done = TRUE;
				break;
			}
		}
	}

	if (!$done) {
		echo 'NOT SUPPORTED';
	}

}
?>

</div>

<?php
include '_footer.php';