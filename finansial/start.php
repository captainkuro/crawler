<?php
require 'vendor/autoload.php';
/*
Reference:
http://www.idx.co.id/id-id/beranda/perusahaantercatat/profilperusahaantercatat.aspx
http://www.bareksa.com/id/stock/sector
http://infopersada.com/investasi/saham/

 */

function exporte($file, $value) {
	file_put_contents($file, '<?php return '.var_export($value, true).';');
}

function importo($file) {
	return include $file;
}

function dom_from_url($url) {
	$p = new Page($url);
	$h = new simple_html_dom();
	$h->load($p->content());
	return $h;
}

// @TODO refactor many many ways to get stock data
// @todo sector info

// return list index info
function get_all_indexes() {
	$output = 'start-all_indexes.out';
	if (is_file($output)) {
		return importo($output);
	}

	$h = dom_from_url('http://infopersada.com/investasi/saham/');

	$result = [];
	foreach ($h->find('.ia-cat') as $div) {
		$a = $div->find('a', 0);
		$url = $a->href;
		preg_match('#Indeks Saham (\w+)#', $a->text(), $m);
		$index = $m[1];

		$dom_list = dom_from_url($url);
		$url_post = $dom_list->find('.ia-item__title', 0)->find('a', 0)->href;
		$dom_article = dom_from_url($url_post);

		$table = $dom_article->find('.ia-item-view__body', 0)->find('table', 0);
		foreach ($table->find('tr') as $i => $tr) {
			if ($i == 0) continue;
			$td_code = $tr->find('td', 1);
			$result[$index][] = trim($td_code->text());
		}
	}
	exporte($output, $result);
	return $result;
}

// return array, list of index contain $code
function find_index($code, $indexes) {
	$result = [];
	foreach ($indexes as $index => $list) {
		if (array_search($code, $list) !== false) {
			$result[] = $index;
		}
	}
	return $result;
}

function get_all_sectors() {
	$dom_sectors = dom_from_url('http://www.bareksa.com/id/stock/sector');
	$table = $dom_sectors->find('#TableBEI', 0);
	$result = [];

	foreach ($table->find('tr.colTab') as $tr) {
		$code = trim($tr->find('td', 1)->text());
		$sector = trim($tr->find('td', 3)->text());
		$result[$code] = $sector;
	}

	return $result;
}

// return list of saham kode, combined
function get_all_codes() {
	$output = 'start-all_codes.out';
	if (is_file($output)) {
		return importo($output);
	}

	$h = new simple_html_dom();
	$h->load(file_get_contents('raw-Company-Profile.xls'));
	$indexes = get_all_indexes();
	$sectors = get_all_sectors();
	$result = [];

	foreach ($h->find('tr') as $i => $tr) {
		if ($i <= 0) continue;
		$code = trim($tr->find('td', 1)->text());
		$name = trim($tr->find('td', 2)->text());
		$join_date = trim($tr->find('td', 3)->text());

		$result[$code] = [
			'code' => $code,
			'name' => $name,
			'join_date' => $join_date,
			'sector' => $sectors[$code],
			'index' => implode(', ', find_index($code, $indexes)),
		];
	}
	exporte($output, $result);
	return $result;
}
// get_all_codes();exit;


function extract_fin($text) {
	$text = preg_replace('#\+\s+<td#', '</th><td', $text);
	$h = new simple_html_dom();
	$h->load($text);

	$table = $h->find('.align07', 0);
	$periodTr = $table->find('tr', 1);
	$periods = [];

	for ($i=0; $i<=3; $i++) {
		$td = $periodTr->find('td', $i);
		if (trim($td->text())) {
			$periods[] = trim($td->text());
		}
	}

	// Given table, extract data into array
	$crawl_table = function ($table, $row) use ($periods) {
		$result = [];
		while ($dataTr = $table->find('tr', $row++)) {
		$label = trim($dataTr->find('th', 0)->text());
			for ($i=0; $i<=3; $i++) {
				$td = $dataTr->find('td', $i);
				$amount = trim($td->text());
				if (isset($periods[$i])) $result[$periods[$i]][$label] = $amount;
			}
		}
		return $result;
	};

	$row = 2;
	$result = $crawl_table($table, $row);


	// Key Ratios
	$table = $h->find('.align07', 2);
	$row = 9;
	$result = array_merge_recursive($result, $crawl_table($table, $row));

	return $result;
}
// $text = file_get_contents('view-source_dwsec-id.com_hmpg_quote_quoteMain-finan.do.html');
// $x = extract_fin($text);
// print_r($x);
// exit;

function fetch_html($code, $year) {
	$url = 'http://miraeasset.co.id/hmpg/quote/quoteMain-finan.do';
	$body = "tabQuoteFlag=01&tabClickYn=Y&loadM_01=2&searchQuart=3&searchYear={$year}&stcd={$code}";
	$p = new Page($url, [
		CURLOPT_POST => 1,
		CURLOPT_POSTFIELDS => $body,
	]);
	return $p->content();
}
// $text = fetch_html('AALI', 2016);
// print_r(extract_fin($text));

function fin_in_year($year) {
	$codes = get_all_codes();
	$result = [];
	foreach ($codes as $row) {
		$code = $row['code'];
		echo "$code $year\n";
		$text = fetch_html($code, $year);
		$result[$code] = extract_fin($text);
	}
	return $result;
}

// how to use: exit start and end index to year u want
// for ($y=2016; $y<=2017; $y++) {
// 	$result = fin_in_year($y);
// 	exporte("start-finan_$y.out", $result);
// }
// exit;

// show chart http://miraeasset.co.id/js/dwsComplex/complex.htm?StockCode=DEWA&periodBit=I
// get data http://miraeasset.co.id/tr/cpstChartAjaxTR.do?StockCode=DEWA&periodBit=I

function get_all_financials() {
	$files = [
		'start-finan_2010.out','start-finan_2011.out','start-finan_2012.out',
		'start-finan_2013.out','start-finan_2014.out','start-finan_2015.out',
		'start-finan_2016.out','start-finan_2017.out',
	];
	$result = [];
	foreach ($files as $input) {
		$chunk = include $input;
		$result = array_merge_recursive($result, $chunk);
	}

	return $result;
}

function extract_to_normalized($stock, $data) {
    $database = [];
	foreach ($data as $period => $financials) {
		preg_match('#(\d).*(\d{4})#', $period, $m);
		$quarter = $m[1];
		$year = $m[2];
		$row = [
			'code' => $stock,
			'year' => $year,
			'quarter' => $quarter,
		];
		foreach ($financials as $label => $amount) {
			if (Common::has_label($label)) {
				$column = Common::label2key($label);
				$value = floatval(str_replace(',', '', $amount));
				$row[$column] = $value;
			}
		}
		$database[] = $row;
	}
	return $database;
}

// $input = include 'finan_2016.out';
// print_r(extract_to_normalized('AALI', $input['AALI']));

function save_standard_financials() {
	$stocks = get_all_codes();
	$finances = get_all_financials();
	$result = [];
	foreach ($stocks as $code => $profile) {
		$result = array_merge($result, extract_to_normalized($code, $finances[$code]));
	}
	exporte('start-all_standardized.out', $result);
}
save_standard_financials();