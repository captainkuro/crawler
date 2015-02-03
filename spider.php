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
		$spider_name = isset($_REQUEST['spider']) ? $_REQUEST['spider'] : '';
		$active = $spider == $spider_name ? 'active' : '';
	?>
		<li class="dropdown <?=$active;?>">
			<a class="dropdown-toggle" data-toggle="dropdown" href="?spider=<?=$spider;?>" tabindex="-1">
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

	public static function print_radio_field($label, $name, $options, $value, $width=6) {
		?>
		<div class="col-md-<?=$width;?>">
			<div class="row">
				<label class="col-sm-4 control-label"><?=$label;?></label>
				<div class="col-sm-8">
					<?php foreach ($options as $choice) : ?>
						<label class="radio-inline">
							<input type="radio" name="<?=$name;?>" value="<?php echo $choice;?>" <?php echo $value==$choice?'checked':''; ?>> <?php echo $choice; ?>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}

	public static function print_submit_buttons($name_search = 'search', $name_prev = 'prev', $name_next = 'next') {
		?>
		<div class="controls">
			<button type="submit" class="btn btn-primary" name="<?php echo $name_search;?>">Search</button>
			<button type="submit" class="btn btn-info" name="<?php echo $name_prev;?>">&lt;&lt; Prev</button>
			<button type="submit" class="btn btn-info" name="<?php echo $name_next;?>">Next &gt;&gt;</button>
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

	public static function add_filter($q, $columns, $search) {
		$parsed = Text::parse_search_term($search);
		$partial_t = array(); // true condition
		$partial_f = array(); // false condition
		$n = count($columns);
		foreach ($columns as $c) {
			$partial_t[] = "$c LIKE ?";
			$partial_f[] = "$c NOT LIKE ?";
		}
		foreach ($parsed['include'] as $term) {
			$q->where_raw("(".implode(' OR ', $partial_t).")", array_fill(0, $n, "%{$term}%"));
		}
		foreach ($parsed['exclude'] as $term) {
			$q->where_raw("(".implode(' AND ', $partial_f).")", array_fill(0, $n, "%{$term}%"));
		}
	}
}
$s = new Spider_Manager();
$s->run();
