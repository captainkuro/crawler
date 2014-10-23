<?php
include '_header.php';
?>
<div class="container">
	<h1>Extract [COLUMNS] from [N] last pages at [URL]</h1>
	<form method="post" class="form-horizontal" action="?">
		<div class="form-group">
			<label class="col-sm-2 control-label">COLUMNS</label>
			<div class="col-sm-10">
				<input type="text" name="columns" class="form-control" placeholder="title,desc,image" value="<?=@$_REQUEST['columns'];?>">
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label">N</label>
			<div class="col-sm-10">
				<input type="text" name="n" class="form-control" placeholder="10" value="<?=@$_REQUEST['n'];?>">
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-2 control-label">URL</label>
			<div class="col-sm-10">
				<input type="text" name="url" class="form-control" placeholder="http://www.rlsbb.com/category/tv-shows/" value="<?=@$_REQUEST['url'];?>">
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
				<tr id="row<?=$i;?>">
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

function merge_result($cols, $result) {
	// column with "+" means combined
	$has_plus = function($key) {
		return (strpos($key, '+') !== FALSE);
	};
	$cols_need_combined = array_filter($cols, $has_plus);
	$merger = function($row) use ($cols_need_combined) {
		foreach ($cols_need_combined as $key) {
			$keys = explode('+', $key);
			$temp = "";
			foreach ($keys as $k) {
				$temp .= $row[$k] . "<br>";
			}
			$row[$key] = $temp;
		}
		return $row;
	};
	if (count($cols_need_combined)) {
		$result = array_map($merger, $result);
	}
	return $result;
}

function expand_columns($cols) {
	$result = array();
	foreach ($cols as $c) {
		if (strpos($c, '+') !== FALSE) {
			$result = array_merge($result, explode('+', $c));
		} else {
			$result[] = $c;
		}
	}
	return array_unique($result);
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
				$result = $object->extract(expand_columns($cols), $s, $n, $url);
				$result = merge_result($cols, $result);
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

<script>
var item = -1;
function jump(n) {
	var url = location.href;
	location.href = "#row"+n;
	history.replaceState(null, null, url);
}

document.addEventListener('keyup', function (e) {
	if (e.target instanceof HTMLBodyElement) {
		var c = String.fromCharCode(e.which);
		console.log(item);
		if (c == 'J') {
			jump(++item);
		} else if (c == 'K' && item > 0) {
			jump(--item);
		}
	}
});
</script>
<?php
include '_footer.php';