<?php
/*
Extract a zipped manga
Traverse in each directory, 1 directory is 1 volume
Rename files using pattern <PREFIX>-<PART IN VOLUME>-<INCREMENTAL>.<FORMAT>
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
		return rtrim($this->output_dir, '/') . '/' . $new_name;
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
	
	private $prev_subpath = '';
	private $current_part = 0;
	private $current_page = 1;

	public function __construct($input_dirpath, $file_prefix, $output_dirpath) {
		$this->input_dir = $input_dirpath;
		$this->prefix = $file_prefix;
		$this->output_dir = $output_dirpath;
	}

	public function perform() {
		$all_files = $this->get_all_files();
		foreach ($all_files as $subpath => $files_in_dir) {
			foreach ($files_in_dir as $filepath) {
				$new_name = $this->new_name_for($subpath, $filepath);
				echo $new_name.PHP_EOL;
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
		rename($origin_path, $new_path);
	}
}

class Dir_Validator {

	private $input_dir;

	public function __construct($input_dirpath) {
		$this->input_dir = $input_dirpath;
	}

	public function need_renaming() {
		$count = $this->count_directory();
		echo $count;
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
	}
}

// Test run
// $ex = new Zip_Extractor('E:\Temp Manga\Yu-Gi-Oh!\Yu-Gi-Oh! v07 c52-59.zip', 'E:\Temp');
// $ex->perform();
$vd = new Dir_Validator('E:\Temp\Yu-Gi-Oh! v07 c52-59');
var_dump($vd->need_renaming());
// $ren = new File_Renamer('E:\Temp\Yu-Gi-Oh! v01 c01-07', 'Yu-Gi-Oh_v01', 'E:\Temp\coba');
// $ren->perform();