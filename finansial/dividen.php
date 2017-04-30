<?php
require 'vendor/autoload.php';

// https://id.investing.com/equities/indonesia
// 1. open id.investing
// 2. get all stock links
// 3. for each stock links
// 4. open page, grab info ...
	// title
	// url
	// data-pair-id="101278"
// 5. sumber lebih reliable: http://akses.ksei.co.id/corporate_actions/downloads
// 6. Parse xls, group by year and saham
// 7. compile into list
// HATI2 SAMA STOCK SPLIT! https://www.sahamok.com/emiten/saham-stock-split-reverse/stock-split-stock-reverse-2016/
// Alt: http://chart.finance.yahoo.com/table.csv?s=HMSP.JK&a=0&b=1&c=2010&d=11&e=31&f=2016&g=v&ignore=.csv
// http://finance.yahoo.com/quote/HMSP.JK/history?period1=1451581200&period2=1483117200&interval=div%7Csplit&filter=split&frequency=1mo
// need research to parse: https://query2.finance.yahoo.com/v8/finance/chart/INAI.JK?formatted=true&crumb=L2o8uRPbeFo&lang=en-US&region=US&period1=1262278800&period2=1483117200&interval=1mo&events=div%7Csplit&corsDomain=finance.yahoo.com

function exporte($file, $value) {
	file_put_contents($file, '<?php return '.var_export($value, true).';');
}

function all_pairs() {
	$p = new Page('https://id.investing.com/equities/indonesia', array('become_firefox'=>true));
	$h = new simple_html_dom();
	$h->load($p->content());

	$spans = $h->find('.alertBellGrayPlus');
	$result = [];
	foreach ($spans as $span) {
		$name = $span->getAttribute('data-name');
		$id = $span->getAttribute('data-id');
		$url = 'https://id.investing.com' . $span->prev_sibling()->getAttribute('href');
		$result[$id] = [$name, $url];
	}
	return $result;
}
// exporte('all_pairs.out', all_pairs());

function all_dividens() {
	$files = ['raw-corporate_action-2014.xls', 'raw-corporate_action-2015.xls', 'raw-corporate_action-2016.xls'];
	$result = [];
	foreach ($files as $xlsfile) {
		$reader = new SpreadsheetReader($xlsfile);
		foreach ($reader as $i => $row) {
			if ($i <= 1) continue;
			// print_r($row);
			$name = $row[1];
			$code = $row[3];
			$exercise = $row[8];
			$date = $row[6];
			$proceed = $row[10];

			if ($code == 'JASS' || $code == '') continue;

			preg_match('#/(\d{4})#', $date, $m);
			$year = $m[1];
			$amount = floatval($proceed) / floatval($exercise);

			$result[$code]['name'] = $name;
			$result[$code]['code'] = $code;
			$result[$code]['dividen'][$year][] = $amount;
		}
	}

	return $result;
}
// all_dividens();
exporte('all_dividens.out', all_dividens());
// exit;

function standardize_dividen() {
	$dividens = include 'all_dividens.out';
	$financials = include 'finan_2016.out';
	$result = [];
	foreach ($dividens as $code => $data) {
		$name = $data['name'];
		$finan = $financials[$code];
		if (isset($data['dividen'][2016]) && isset($finan['4th Quarter 2016'])) {
			$dividen = array_sum($data['dividen'][2016]);
			$item = [
				'Name' => $name,
				'Code' => $code,
			];
			$item = $item + $finan['4th Quarter 2016'];
			$item['Dividen 2016'] = $dividen;
			$item['Rasio Dividen'] = sprintf('%.2f',
				floatval(str_replace(',', '', $dividen) * 100
				/
				floatval(str_replace(',', '', $item['Close Price']))
				)
			);
			$item['History Dividen'] = '';
			foreach ($data['dividen'] as $year => $yearly) {
				$item['History Dividen'] .= "$year : " . implode(' + ', $yearly) . ' / ';
			}

			$result[] = $item;
		}
	}
	return $result;
}

function search_dividen() {
	$dividens = standardize_dividen();
	usort($dividens, function ($a, $b) {
		return (floatval($a['Rasio Dividen']) > floatval($b['Rasio Dividen'])) ? -1 : 1;
	});
	return $dividens;
}
exporte('dividen.search_dividen.out', search_dividen());