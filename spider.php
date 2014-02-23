<?php

include 'class/idiorm.php';
include 'class/paris.php';
include 'class/simple_html_dom.php';

interface Spider {

	public function get_title();

	public function get_db_path();

	public function create_database();

	public function action_update();

	public function action_search();

}

class Spider_Manager {

	private function get_spider($name) {
		$file = 'spiders/'.$name.'.php';
		if (is_file($file)) {
			include_once $file;
			return new $name;
		}
		return null;
	}

	private function print_menu($spider) {
	?>
		<li class="dropdown">
			<a class="dropdown-toggle" data-toggle="dropdown" href="?spider=<?=$spider;?>">
				<?=$spider;?> <span class="caret"></span>
			</a>
			<ul class="dropdown-menu">
				<li><a href="?spider=<?=$spider;?>&amp;action=search">Search</a></li>
				<li><a href="?spider=<?=$spider;?>&amp;action=update">Update</a></li>
			</ul>
		</li>
	<?php
	}

	
	public function run() {
		$spider_name = isset($_REQUEST['spider']) ? $_REQUEST['spider'] : '';
		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
		$title = 'Spider';

		$spider = $this->get_spider($spider_name);
		if ($spider) {
			$title = $spider->get_title();
			// DB
			$dbpath = $spider->get_db_path();
			$empty_database = false;
			if (!is_file($dbpath)) {
				touch($dbpath);
				$empty_database = true;
			}
			$dbpath = realpath($dbpath);
			ORM::configure('sqlite:' . $dbpath);
			if ($empty_database) $spider->create_database();

		}

		// header
		include '_header.php'; // loaded with bootstrap
		
		// process
	?>
		<ul class="nav nav-tabs">
			<?php $this->print_menu('Fakku'); ?>
			<?php $this->print_menu('Hbrowse'); ?>
			<?php $this->print_menu('HentaiMangaOnline'); ?>
			<?php $this->print_menu('FreeHManga'); ?>
		</ul>
		<div class="container">
	<?php
		if ($spider) {
			$method = 'action_'.$action;
			if (method_exists($spider, $method)) {
				$spider->$method();
			} else {
				echo 'Choose something';
			}
		} else {
			echo 'Select Spider';
		}
	?>
		</div>
	<?php
		// footer
		include '_footer.php';
	}
}

// H Helper
class HH {
	public static function print_form_field($label, $name, $value, $width=6) {
		?>
		<div class="col-md-<?=$width;?>">
			<div class="row">
				<label class="col-sm-4 control-label"><?=$label;?></label>
				<div class="col-sm-8">
					<input type="text" class="form-control" name="<?=$name;?>" value="<?=$value;?>">
				</div>
			</div>
		</div>
		<?php
	}

	public static function url($spider, $query) {
		$name = get_class($spider);
		return "?spider={$name}&{$query}";
	}

	public static function print_downloads($alt, $thumbnails, $pages, $style = '') {
	?>
		<ul class="thumbnails">
		<?php foreach ($thumbnails as $i => $th) : ?>
			<li style="<?php echo $style; ?>">
				<a href="<?php echo $pages[$i]; ?>">
					<img src="<?php echo $th; ?>" alt="<?php echo $alt; ?>">
				</a>
			</li>
		<?php endforeach; ?>
		</ul>
		<?php for ($i=count($thumbnails), $n=count($pages); $i<$n; $i++) : ?>
			<a href="<?php echo $pages[$i]; ?>"><?php echo $alt; ?></a>
		<?php endfor; ?>
	<?php
	}
}
$s = new Spider_Manager();
$s->run();