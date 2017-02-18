<?php
$stocks = include 'all_codes.out';
$financials = include 'all_standardized.out';

$map = array(
	'code' => 'Code',
	'year' => 'Year',
	'quarter' => 'Q',
	'total_sales' => 'Total Sales',
	'cost_of_good_sold' => 'Cost of Good Sold',
	'gross_profit' => 'Gross Profit',
	'operation_expenses' => 'Operation Expenses',
	'ebit' => 'EBIT',
	'other_income_expenses' => 'Other Income/Expenses',
	'earning_before_tax' => 'Earning Before Tax',
	'net_income_after_tax' => 'Net Income After Tax',
	'minority_interest' => 'Minority Interest',
	'net_income' => 'Net Income(NI)',
	'eps' => 'Earning Per Share(EPS)',
	'bv' => 'Book Value Per Share(BV)',
	'close_price' => 'Close Price',
	'per' => 'PER(Close Price/EPS*)',
	'pbv' => 'PBV(Close Price/BV)',
);

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
		global $map;
		$formatted = [];
		foreach ($this->financials as $row) {
			$item = $this->stocks[$row['code']];
			foreach ($row as $key => $value) {
				if (preg_match('#\d{5,}#', $value)) {
					$value = number_format($value);
				}
				if (isset($map[$key])) {
					$item[$map[$key]] = $value;
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
	return $p['sector'] == 'CONSUMER' 
		&& $r['year'] == 2016
		&& $r['quarter'] == 3;
});
$f->sort(function ($a, $b) {
	return ($a['eps'] > $b['eps']) ? -1 : 1;
});
$f->save('sort-by-eps-desc-CONSUMER.out');