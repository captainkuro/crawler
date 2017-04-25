<?php
require 'vendor/autoload.php';
// @TODO
$stocks = include 'start-all_codes.out';
$financials = include 'start-all_standardized.out';

class Filtrasi {
	public function setStocks($stocks) {
		$this->stocks = $stocks;
	}

	public function setFinancials($financials) {
		$this->financials = $financials;
	}

	public function filter($function) {
		$result = [];
		foreach ($this->financials as $row) {
			$profile = $this->stocks[$row['code']];
			if ($function($profile, $row)) {
				$result[] = $row;
			}
		}
		$this->financials = $result;
	}

	public function sort($function) {
		usort($this->financials, $function);
	}

	public function save($file) {
		$formatted = [];
		foreach ($this->financials as $row) {
			$item = $this->stocks[$row['code']];
			foreach ($row as $key => $value) {
				if (preg_match('#\d{5,}#', $value)) {
					$value = number_format($value);
				}
				if (Common::has_key($key)) {
					$item[Common::key2label($key)] = $value;
				} else {
					$item[$key] = $value;
				}
			}
			$formatted[] = $item;
		}
		file_put_contents($file, print_r($formatted, true));
	}
}

$f = new Filtrasi;
$f->setStocks($stocks);
$f->setFinancials($financials);

$f->filter(function ($p, $r) {
	return /*$p['sector'] == 'CONSUMER'
		&& */$r['year'] == 2016
		&& $r['quarter'] == 3;
});
$f->sort(function ($a, $b) {
	return ($a['roe'] > $b['roe']) ? -1 : 1;
});
$f->save('search-sort-by-roe-desc-CONSUMER.out');