<?php
/*
Split to Folder
Detect filename with v[XX], then put them in folders [Name + vXX]
Then cbz-zip them up
*/
if (!defined('KANWIL_AUTOLOADED')) {
	require_once 'class/text.php';
}

class G {
	public static $vol_regex = '#v(\d{1,2})#';
	// Make sure flat folder
	public static $source = 'C:\MINE\To Be Kept\in2';
	// Keep it empty
	public static $destination = 'C:\MINE\To Be Kept\out';
	public static $prefix = 'One-Punch Man';
}

class Actual_Job {
	public function run() {
		$dir = new DirectoryIterator(G::$source);
		
		// Move files
		// $debug_idx = 0;
		foreach ($dir as $fileinfo) {
			if ($fileinfo->isFile() && !$fileinfo->isDot()) {
				$this->process_a_file($fileinfo->getPathname());
				// if ($debug_idx++ > 300) break; //debug
			}
		}

		// Cbz-zip all folders
		$this->produce_bat();
	}

	private function process_a_file($filepath) {
		$filename = basename($filepath);
		if (!preg_match(G::$vol_regex, $filename, $matches)) {
			echo "WARNING: Cannot determine vol from filename {$filename}\n";
			$vol = 0;
		} else {
			$vol = $matches[1];
		}
		$vol_dir = G::$prefix.' v'.Text::create($vol)->pad(2);

		$full_dir = $this->create_new_directory($vol_dir);
		copy($filepath, $full_dir . '/' . $filename);
	}

	private function create_new_directory($dir_name) {
		$new_dir = G::$destination . '/' . $dir_name;
		if (!is_dir($new_dir)) {
			echo "Creating new Dir {$new_dir}\n";
			mkdir($new_dir);
		}
		return $new_dir;
	}

	private function produce_bat() {
		$dir = new DirectoryIterator(G::$destination);
		$batch = '';
		while ($dir->valid()) {
			if ($dir->isDir() && !$dir->isDot()) {
				$source_dir = $dir->getPathname();
				$dest_file = $source_dir.'.cbz';
				$command = "7z a \"{$dest_file}\" \"{$source_dir}\\*\" -tzip -mx0".PHP_EOL;
				
				echo $command;
				$batch .= $command;
			}
			$dir->next();
		}
		$final = G::$destination.'\\'.'zip.bat';
		echo $final.PHP_EOL;
		var_dump(file_put_contents($final, $batch));
	}
}

$x = new Actual_Job();
$x->run();