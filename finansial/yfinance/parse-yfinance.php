<?php
require '../vendor/autoload.php';

$best_result = require '../search-best-buku.out';
$codes = [];

foreach ($best_result as $stock) {
	if (in_array($stock['Score'], ['9,00', '8,00', '7,00'])) {
		$codes[] = $stock['code'];
	}
}

if (!is_file('prices-score-7-above.json')) {
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

	file_put_contents('prices-score-7-above.json', json_encode($prices_collection, JSON_PRETTY_PRINT));
} else {
	$prices_collection = json_decode(file_get_contents('prices-score-7-above.json'), true);
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
			$divider = $divider * $row['denominator'];
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

$report = [];
uasort($dividend_percents, function ($a, $b) {
	if (isset($a['2017'])) {
		if (!isset($b['2017'])) {
			return -1;
		} else {
			return ($a['2017'] > $b['2017']) ? -1 : 1; 
		}
	} else {
		if (isset($b['2017'])) {
			return 1;
		} else {
			return 0;
		}
	}
});

file_put_contents('dividends-score-7-above.json', json_encode($dividend_percents, JSON_PRETTY_PRINT));
