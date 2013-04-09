<?php
// Generate Zip archive batch
$title = 'Zip My Folders';
include '_header.php'; // loaded with bootstrap
?>
<ul class="nav nav-tabs">
	<li><a>Main</a></li>
</ul>

<form class="form-horizontal" method="post">
	<div class="control-group">
		<div class="span6">
			<label class="control-label">Directory</label>
			<div class="controls">
				<input type="text" name="path" value="">
			</div>
		</div>
	</div>
	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn" name="search">Zip it</button>
		</div>
	</div>
</form>

<pre>
<?php
if ($_POST) {
	$path = $_POST['path'];
	echo $path.PHP_EOL;
	$dir = new DirectoryIterator($path);
	$batch = '';
	while ($dir->valid()) {
		if ($dir->isDir() && !$dir->isDot()) {
			$source_dir = $dir->getPathname();
			$dest_file = $source_dir.'.zip';
			$command = "7z a \"{$dest_file}\" \"{$source_dir}\\*\" -tzip -mx0".PHP_EOL;
			
			echo $command;
			$batch .= $command;
		}
		$dir->next();
	}
	$final = $path.'\\'.'zip.bat';
	echo $final.PHP_EOL;
	var_dump(file_put_contents($final, $batch));
}
?>
</pre>
<?php
include '_footer.php'; // loaded with bootstrap
?>