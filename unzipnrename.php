<?php
/*
Extract a zipped manga
Traverse in each directory, 1 directory is 1 volume
Rename files using pattern <PREFIX>-v<VOL>p<PART IN VOLUME>-<INCREMENTAL>.<FORMAT>
*/ 

if (!defined('KANWIL_AUTOLOADED')) {
	require_once 'class/text.php';
}

class Zip_Extractor {

	private $input_file;
	private $output_dir;
	
	public function __construct($input_filepath, $output_dirpath) {
		$this->input_file = $input_filepath;
		$this->output_dir = $output_dirpath;
	}

	public function new_directory_name() {
		$new_name = Text::create($this->input_file)->basename()->cut_rbefore('.')->to_s();
		return rtrim($this->output_dir, '/') . '/' . $new_name . 'extract';
	}

	public function perform() {
		$this->create_new_directory();
		$this->extract_zip();
	}

	private function create_new_directory() {
		mkdir($this->new_directory_name());
	}

	private function extract_zip() {
		exec($this->command_to_extract());
	}

	private function command_to_extract() {
		$zip = $this->input_file;
		$out = $this->new_directory_name();
		$command = "7z x -o\"{$out}\" \"{$zip}\" ";
		return $command;
	}

}

class File_Renamer {

	private $input_dir;
	private $prefix;
	private $output_dir;
	private $is_copied;
	
	private $prev_subpath = '';
	private $current_part = 0;
	private $current_page = 1;

	public function __construct($input_dirpath, $file_prefix, $output_dirpath, $copy = false) {
		$this->input_dir = $input_dirpath;
		$this->prefix = $file_prefix;
		$this->output_dir = $output_dirpath;
		$this->is_copied = $copy;
	}

	public function perform() {
		$all_files = $this->get_all_files();
		foreach ($all_files as $subpath => $files_in_dir) {
			foreach ($files_in_dir as $filepath) {
				$new_name = $this->new_name_for($subpath, $filepath);
				$this->move($filepath, $new_name);
			}
		}
	}

	private function get_all_files() {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($this->input_dir)
		);

		$result = array();
		while ($iterator->valid()) {
			if (!$iterator->isDot()) {
				$result[$iterator->getSubPath()][] = $iterator->key();
			}
			$iterator->next();
		}
		return $result;
	}

	private function new_name_for($subpath, $filepath) {
		if ($subpath != $this->prev_subpath) {
			$this->current_part++;
			$this->prev_subpath = $subpath;
			$this->current_page = 1;
		}
		$part = Text::create($this->current_part)->pad(2)->to_s();
		$page = Text::create($this->current_page++)->pad(3)->to_s();
		$extension = Text::create($filepath)->cut_rafter('.')->to_s();
		return "{$this->prefix}p{$part}-{$page}.{$extension}";
	}

	private function move($origin_path, $new_name) {
		$new_path = rtrim($this->output_dir, '/') . '/' . $new_name;
		if ($this->is_copied) {
			copy($origin_path, $new_path);
		} else {
			rename($origin_path, $new_path);
		}
	}
}

class Dir_Validator {

	private $input_dir;

	public function __construct($input_dirpath) {
		$this->input_dir = $input_dirpath;
	}

	public function need_renaming() {
		$count = $this->count_directory();
		return $count > 2;
	}

	private function count_directory() {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($this->input_dir, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);
		$count = 0;
		while ($iterator->valid()) {
			if ($iterator->isDir()) {
				$count++;
			}
			$iterator->next();
		}
		return $count;
	}
}

class Directory_Zipper {

	public function __construct($input_dirpath, $output_dirpath) {
		$this->input_dir = $input_dirpath;
		$this->output_dir = $output_dirpath;
	}

	public function perform() {
		exec($this->command_to_zip());
	}

	private function command_to_zip() {
		$source_dir = $this->input_dir;
		$new_name = basename($source_dir) . '.cbz';
		$dest_file = rtrim($this->output_dir, '/') . '/' . $new_name;
		$command = "7z a \"{$dest_file}\" \"{$source_dir}\\*\" -tzip -mx0".PHP_EOL;
		return $command;
	}
}

function remove_dir($dirPath) {
	foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
	    $path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
	}
	rmdir($dirPath);
}

class Prefix_Generator {

	private $prefix;
	private $pattern;

	public function __construct($prefix, $capture_pattern) {
		$this->prefix = $prefix;
		$this->pattern = $capture_pattern;
	}

	public function generate($dirname) {
		preg_match($this->pattern, $dirname, $m);
		$vol = Text::create($m[1])->pad(2)->to_s();
		return $this->prefix . '-v' . $vol;
	}
}

class Zip_To_Cbz {

	private $source;
	private $destination;
	private $prefix_gen;
	private $temp_dir = 'e:\Temp\unzip';

	private $current_file;
	private $unzip_dir;
	private $moved_dir;

	public function __construct($source_dir, $dest_dir, $prefix, $vol_pattern) {
		$this->source = $source_dir;
		$this->destination = $dest_dir;
		$this->prefix_gen = new Prefix_Generator($prefix, $vol_pattern);
	}

	public function run() {
		$to_extract = $this->all_archives();
		foreach ($to_extract as $zip_file) {
			$this->current_file = $zip_file;
			$this->extract_file();
			if ($this->need_renaming()) {
				$this->rename_them();
				$this->zip_them();
			} else {
				$this->directly_copy();
			}
			$this->cleanup();
		}
	}

	public function all_archives() {
		$dir = new DirectoryIterator($this->source);
		$allowed = array('zip', 'rar');
		$result = array();
		foreach ($dir as $fileinfo) {
			if ($fileinfo->isFile() && in_array($fileinfo->getExtension(), $allowed)) {
				$result[] = $fileinfo->getPathname();
			}
		}
		return $result;
	}

	private function extract_file() {
		$extractor = new Zip_Extractor($this->current_file, $this->temp_dir);
		$extractor->perform();
		$this->unzip_dir = $extractor->new_directory_name();
	}

	private function need_renaming() {
		$validator = new Dir_Validator($this->unzip_dir);
		return $validator->need_renaming();
	}

	// them = files in unzip_dir
	private function rename_them() {
		$new_name = Text::create($this->current_file)->basename()->cut_rbefore('.')->to_s();
		$new_dir = $this->temp_dir . '/' . $new_name;
		mkdir($new_dir);
		$this->moved_dir = $new_dir;

		$prefix = $this->prefix_gen->generate($new_name);
		$renamer = new File_Renamer($this->unzip_dir, $prefix, $new_dir);
		$renamer->perform();
	}

	private function zip_them() {
		$zipper = new Directory_Zipper($this->moved_dir, $this->destination);
		$zipper->perform();
	}

	private function directly_copy() {
		$filename = basename($this->current_file);
		$destination = rtrim($this->destination, '/') . '/' . $filename;
		copy($this->current_file, $destination);
	}

	private function cleanup() {
		if ($this->unzip_dir) remove_dir($this->unzip_dir);
		if ($this->moved_dir) remove_dir($this->moved_dir);
		$this->unzip_dir = '';
		$this->moved_dir = '';
	}
}

class Dir_To_Cbz {
	
	private $source;
	private $destination;
	private $prefix_gen;
	private $temp_dir = 'C:\MINE\Temp\unzip'; // make sure this exists!

	private $current_dir;
	private $moved_dir;

	public function __construct($source_dir, $dest_dir, $prefix, $vol_pattern) {
		$this->source = $source_dir;
		$this->destination = $dest_dir;
		$this->prefix_gen = new Prefix_Generator($prefix, $vol_pattern);
	}

	public function run() {
		$dirs = $this->all_directories();
		foreach ($dirs as $dir) {
			$this->current_dir = $dir;
			$this->create_new_directory();
			if ($this->need_renaming()) {
				$this->rename_them();
			} else {
				$this->copy_them();
			}
			$this->zip_them();
			$this->cleanup();
		}
	}

	private function all_directories() {
		$dir = new DirectoryIterator($this->source);
		$result = array();
		foreach ($dir as $fileinfo) {
			if ($fileinfo->isDir() && !$fileinfo->isDot()) {
				$result[] = $fileinfo->getPathname();
			}
		}
		return $result;
	}

	private function need_renaming() {
		$validator = new Dir_Validator($this->current_dir);
		return $validator->need_renaming();
	}

	private function create_new_directory() {
		$new_name = basename($this->current_dir);
		$new_dir = $this->temp_dir . '/' . $new_name;
		mkdir($new_dir);
		$this->moved_dir = $new_dir;
	}

	private function rename_them() {
		$new_name = basename($this->current_dir);
		$prefix = $this->prefix_gen->generate($new_name);
		$renamer = new File_Renamer($this->current_dir, $prefix, $this->moved_dir, true);
		$renamer->perform();
	}

	private function copy_them() {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($this->current_dir, FilesystemIterator::SKIP_DOTS)
		);
		while ($iterator->valid()) {
			if ($iterator->isFile()) {
				copy($iterator->getPathname(), $this->moved_dir . '/' . $iterator->getFilename());
			}
			$iterator->next();
		}
	}

	private function zip_them() {
		$zipper = new Directory_Zipper($this->moved_dir, $this->destination);
		$zipper->perform();
	}

	private function cleanup() {
		if ($this->moved_dir) remove_dir($this->moved_dir);
		$this->moved_dir = true;
	}
}

// Test run
$x = new Dir_To_Cbz('C:\MINE\To Be Kept\Detective Conan', 'C:\MINE\To Be Kept\Deto', 'Detective_Conan', '#v(\d{2})#');
$x->run();