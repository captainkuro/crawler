<?php
require '../class/simple_html_dom.php';
require '../class/text.php';
require '../class/page.php';

function exporte($file, $value) {
	file_put_contents($file, '<?php return '.var_export($value, true).';');
}

function get_all_codes() {
	if (is_file('all_codes.out')) {
		return include 'all_codes.out';
	}
	$raw = file_get_contents('Daftar Emiten Dan Kode Saham Di Bursa Efek Indonesia.html');
	$h = new simple_html_dom();
	$h->load($raw);

	$table = $h->find('.font_general', 0);
	$trs = $table->find('tr[bgcolor="#F4F4F4"]');
	$result = [];
	foreach ($trs as $tr) {
		$code = trim($tr->find('td', 1)->text());
		$name = trim($tr->find('td', 2)->text());

		$sector = $tr->find('td', 3)->text();
		$parts = explode('|', $sector);
		$sector = trim(array_shift($parts));
		$index = implode(',', array_map('trim', $parts));
		
		$result[$code] = [
			'code' => $code, 'name' => $name, 
			'sector' => $sector, 'index' => $index
		];
	}
	
	exporte('all_codes.out', $result);
	return $result;
}

// $x = get_all_codes();
// print_r($x);
// file_put_contents('all_codes.out', var_export($x, true));

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

	$row = 2;
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
}
// $text = file_get_contents('view-source_dwsec-id.com_hmpg_quote_quoteMain-finan.do.html');
// $x = extract_fin($text);
// print_r($x);

function fetch_html($code, $year) {
	$url = 'http://dwsec-id.com/hmpg/quote/quoteMain-finan.do';
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

// $result = fin_in_year(2014);
// exporte('finan_2014.out', $result);

// show chart http://dwsec-id.com/js/dwsComplex/complex.htm?StockCode=DEWA&periodBit=I
// get data http://dwsec-id.com/tr/cpstChartAjaxTR.do?StockCode=DEWA&periodBit=I

function get_all_financials() {
	$files = ['finan_2014.out','finan_2015.out','finan_2016.out',];
	$result = [];
	foreach ($files as $input) {
		$chunk = include $input;
		$result = array_merge_recursive($result, $chunk);
	}

	return $result;
}

function extract_to_normalized($stock, $data) {
	$map = array(
      'Total Sales' => 'total_sales',
      'Cost of Good Sold' => 'cost_of_good_sold',
      'Gross Profit' => 'gross_profit',
      'Operation Expenses' => 'operation_expenses',
      'EBIT' => 'ebit',
      'Other Income/Expenses' => 'other_income_expenses',
      'Earning Before Tax' => 'earning_before_tax',
      'Net Income After Tax' => 'net_income_after_tax',
      'Minority Interest' => 'minority_interest',
      'Net Income(NI)' => 'net_income',
      'Earning Per Share(EPS)' => 'eps',
      'Book Value Per Share(BV)' => 'bv',
      'Close Price' => 'close_price',
      'PER(Colse Price/EPS*)' => 'per',
      'PBV(Close Price/BV)' => 'pbv',
    );
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
			if (isset($map[$label])) {
				$column = $map[$label];
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
	exporte('all_standardized.out', $result);
}
save_standard_financials();