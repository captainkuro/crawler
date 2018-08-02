<?php
require '../vendor/autoload.php';

$best_result = require '../search-best-buku.out';
$codes = [];

foreach ($best_result as $stock) {
	if (in_array($stock['Score'], ['9,00', '8,00', '7,00', '6,00'])) {
		$codes[] = $stock['code'];
	}
}

if (!is_file('prices-score-6-above.json')) {
	$prices_collection = [];
	foreach ($codes as $code) {
		echo "Page $code\n";
		$YPage = new Page("https://finance.yahoo.com/quote/{$code}.JK/history?period1=1096304400&period2=1530032400&interval=1mo&filter=history&frequency=1mo");
		$YPage->go_line('HistoricalPriceStore');

		$raw = Text::create($YPage->curr_line());
		$cut_json = $raw->cut_between('"HistoricalPriceStore":{"prices":', ',"isPending"');

		$prices = json_decode($cut_json, true);
		$prices_collection[$code] = $prices;
	}

	file_put_contents('prices-score-6-above.json', json_encode($prices_collection, JSON_PRETTY_PRINT));
} else {
	$prices_collection = json_decode(file_get_contents('prices-score-6-above.json'), true);
}

$closing_price_sums = [];
$dividend_sums = [];
foreach ($prices_collection as $code => $prices) {
	echo "Prices $code\n";
	$divider = 1;
	foreach ($prices as $row) {
		$year = date('Y', $row['date']);
		if (isset($row['type']) && $row['type'] == 'DIVIDEND') {
			$dividend_sums[$code][$year][] = $row['data'] / $divider;
		} else if (isset($row['type']) && $row['type'] == 'SPLIT') {
			$divider = $divider * $row['denominator'] / $row['numerator'];
		} else {
			// must be price
			$closing_price_sums[$code][$year][] = $row['close'];
		}
	}
}

$closing_price_avgs = [];
foreach ($closing_price_sums as $code => $per_year) {
	foreach ($per_year as $year => $amounts) {
		$closing_price_avgs[$code][$year] = array_sum($amounts) / count($amounts);
	}
}
$dividend_percents = [];
foreach ($dividend_sums as $code => $per_year) {
	foreach ($per_year as $year => $amounts) {
		$dividend_percents[$code][$year] = array_sum($amounts) / $closing_price_avgs[$code][$year] * 100;
	}
}

/*
@TODO show data:
- avg price yearly
- divicen amount
- per semester: dividen percent & avg price 
*/
$report = [];
foreach ($closing_price_avgs as $code => $per_year) {
	foreach ($per_year as $year => $closing_avg) {
		$dividend_sum = isset($dividend_sums[$code][$year]) ? array_sum($dividend_sums[$code][$year]) : 0;
		$dividend_percent = isset($dividend_percents[$code][$year]) ? $dividend_percents[$code][$year] : 0;
		$report[$code][$year] = array(
			'dividend_sum' => $dividend_sum,
			'dividend_%' => $dividend_percent,
			'close_avg' => $closing_avg,
		);
	}
}

uasort($report, function ($a, $b) {
	if (isset($a['2017']['dividend_%'])) {
		if (!isset($b['2017']['dividend_%'])) {
			return -1;
		} else {
			return ($a['2017']['dividend_%'] > $b['2017']['dividend_%']) ? -1 : 1; 
		}
	} else {
		if (isset($b['2017']['dividend_%'])) {
			return 1;
		} else {
			return 0;
		}
	}
});

file_put_contents('dividends-score-6-above.json', json_encode($report, JSON_PRETTY_PRINT));
