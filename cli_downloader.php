<?php

require '_autoload.php';

function download_it($img_url, $output_file) {
	$dir = dirname($output_file) . '\\';
	//exec("mkdir \"$dir\"");
	exec("wget -t 0 --retry-connrefused -O \"$output_file\" \"$img_url\"");
}

interface ADownloader {
	public function display();
	public function download();
}

class MainProgram {
	private $downloaders;
	private $active;

	public function __construct() {
		// loop through classes
		$scan = scandir('downloaders');
		$this->downloaders = array();
		
		foreach ($scan as $entry) {
			if ($pos = strpos($entry, '_Downloader.php')) {
				$classname = substr($entry, 0, $pos) . '_Downloader';
				include 'downloaders/'.$entry;
				$this->downloaders[] = new $classname;
			}
		}
	}

	private function print_menu() {
		$text = '';
		$i = 1;
		foreach ($this->downloaders as $downloader) {
			$text .= "[$i] {$downloader->display()}\n";
			$i++;
		}
		$text .= "[q]/[exit] Close\n\n";
		$text .= 'Choose: ';
		echo $text;
	}

	private function interpret_input($input) {
		echo "You chose [{$input}]\n\n";

		$downloader = $this->search_downloader($input);
		if ($input === 'exit' || $input === 'q') {
			$this->active = false;
			echo "Goodbye\n";
			return;
		} else if ($downloader) {
			echo "{$downloader->display()}\n==========================================\n\n";
			$downloader->download();
		} else {
			echo "UNRECOGNIZED\n";
		}
		echo "Press Enter...";
		fgets(STDIN);
	}

	private function search_downloader($input) {
		$key = $input - 1;
		if (isset($this->downloaders[$key])) {
			return $this->downloaders[$key];
		} else {
			return null;
		}
	}

	public function run() {
		$this->active = true;
		while ($this->active) {
			$this->print_menu();
			$input = fgets(STDIN);
			$this->interpret_input(trim($input));
		}
	}
}

$p = new MainProgram();
$p->run();