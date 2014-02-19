<?php
// Generate Zip archive batch
$title = 'Zip My Folders';
include '_header.php'; // loaded with bootstrap
?>
<ul class="nav nav-tabs">
	<li><a>Main</a></li>
</ul>

<div class='container'>
	<form class="form-horizontal" method="post">
		<div class="form-group">
			<label class="col-sm-1 control-label">Directory</label>
			<div class="col-sm-6 controls">
				<input type="text" name="path" value="">
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-1 control-label">Ext</label>
			<div class="col-sm-6 controls">
				<input type="radio" name="ext" value="zip" <?php if (@$_POST['ext']=='zip') echo 'checked'; ?>> ZIP
				<input type="radio" name="ext" value="cbz" <?php if (@$_POST['ext']=='cbz') echo 'checked'; ?>> CBZ
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-offset-1 controls">
				<button type="submit" class="btn" name="search">Zip it</button>
			</div>
		</div>
	</form>
</div>

<pre>
<?php
if ($_POST) {
	$path = $_POST['path'];
	$ext = isset($_POST['ext']) ? $_POST['ext'] : 'zip';
	echo $path.PHP_EOL;
	$dir = new DirectoryIterator($path);
	$batch = '';
	while ($dir->valid()) {
		if ($dir->isDir() && !$dir->isDot()) {
			$source_dir = $dir->getPathname();
			$dest_file = $source_dir.'.'.$ext;
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