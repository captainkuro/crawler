<?php
require 'vendor/autoload.php';
/*
Sample row:
  array (
    'code' => 'AALI',
    'year' => '2010',
    'quarter' => '1',
    'total_sales' => 1633078000000.0,
    'cost_of_good_sold' => -1071707000000.0,
    'gross_profit' => 561371000000.0,
    'operation_expenses' => -124701000000.0,
    'ebit' => 436670000000.0,
    'other_income_expenses' => -33852000000.0,
    'earning_before_tax' => 402818000000.0,
    'net_income_after_tax' => 284209000000.0,
    'minor_interest' => 1.0,
    'net_income' => 271978000000.0,
    'eps' => 172.0,
    'bv' => 4126.0,
    'close_price' => 24600.0,
    'per' => 35.600000000000001,
    'pbv' => 6.0,
    'cash_eq' => 1047806000000.0,
    'acc_rec' => 40958000000.0,
    'inventories' => 715717000000.0,
    'other_curr_asset' => 259409000000.0,
    'total_curr_asset' => 2063890000000.0,
    'fixed_asset' => 2480007000000.0,
    'other_noncurr_asset' => 3678887000000.0,
    'total_noncurr_asset' => 6158894000000.0,
    'total_asset' => 8222784000000.0,
    'curr_liab' => 1296895000000.0,
    'long_liab' => 215064000000.0,
    'total_liab' => 1511959000000.0,
    'total_equity' => 6498343000000.0,
    'total_liab_equity' => 8222784000000.0,
    'operating_act' => 574548000000.0,
    'investing_act' => -291013000000.0,
    'financing_act' => -3855000000.0,
    'net_cash_flow_act' => 279680000000.0,
    'cash_eq_end' => 1047806000000.0,
    'per2' => 35.600000000000001,
    'pbv2' => 6.0,
    'der' => 0.20000000000000001,
    'roa' => 13.199999999999999,
    'roe' => 16.699999999999999,
    'op_margin' => 26.699999999999999,
  ),
*/
$stocks = include 'start-all_codes.out';
$dividens = include 'all_dividens.out';
$financials = include 'start-all_standardized.out';

// Configs sort of
$included_years = [2016, 2015, 2014];
$quarter = '4';
list($ynow, $yprev, $yprev2) = $included_years;

// Group by code - years
$grouped = [];
foreach ($financials as $row) {
	if (in_array($row['year'], $included_years)
		&& $row['quarter'] == $quarter) {
		$grouped[$row['code']][$row['year']] = $row;
	}
}

// Convert to important values only
$compiled = [];
foreach ($grouped as $code => $yearly_info) {
	$infonow = @$yearly_info[$ynow];
	$infoprev = @$yearly_info[$yprev];
	$infoprev2 = @$yearly_info[$yprev2];

	if (!$infonow || !$infoprev || !$infoprev2) continue;

	$compiled[$code] = $stocks[$code];
	$compiled[$code]['Close Price'] = [
		$ynow => $infonow['close_price'],
		$yprev => $infoprev['close_price'],
		$yprev2 => $infoprev2['close_price'],
	];

	$compiled[$code]['Total Aset'] = [
		$ynow => $infonow['total_asset'],
		$yprev => $infoprev['total_asset'],
		$yprev2 => $infoprev2['total_asset'],
	];
	$compiled[$code]['Modal'] = [
		$ynow => $infonow['total_equity'],
		$yprev => $infoprev['total_equity'],
		$yprev2 => $infoprev2['total_equity'],
	];
	$compiled[$code]['EPS'] = [
		$ynow => $infonow['eps'],
		$yprev => $infoprev['eps'],
		$yprev2 => $infoprev2['eps'],
	];
	$compiled[$code]['Laba Usaha'] = [
		$ynow => $infonow['ebit'],
		$yprev => $infoprev['ebit'],
		$yprev2 => $infoprev2['ebit'],
	];
	$compiled[$code]['Penjualan'] = [
		$ynow => $infonow['total_sales'],
		$yprev => $infoprev['total_sales'],
		$yprev2 => $infoprev2['total_sales'],
	];
	$compiled[$code]['Dividen'] = '';
	// quick fix
	foreach ($included_years as $y) {
		if (isset($dividens[$code]['dividen'][$y])) {
			$compiled[$code]['Dividen'][$y] = implode(', ', $dividens[$code]['dividen'][$y]);
		}
	}

	$compiled[$code]['ROE'] = [
		$ynow => $infonow['roe'],
		$yprev => $infoprev['roe'],
		$yprev2 => $infoprev2['roe'],
	];

	$curr_liab = $infonow['curr_liab'] == 0 ? 1 : $infonow['curr_liab'];
	$compiled[$code][$ynow] = [
		'Arus Kas' => $infonow['net_cash_flow_act'],
		'Arus Kas Operasi' => $infonow['operating_act'],
		'Arus Kas Investasi' => $infonow['investing_act'],
		'Arus Kas Pendanaan' => $infonow['financing_act'],
		'Current Ratio' => sprintf('%.2f', $infonow['total_curr_asset'] / $curr_liab),
		'DAR' => sprintf('%.2f', $infonow['total_asset'] / $infonow['total_liab']),
		'DER' => $infonow['der'],
	];
}

function is_3_tahun($stockdata, $label, $arah) {
	global $ynow, $yprev, $yprev2;

	if ($arah == 'turun') {
		return $stockdata[$label][$ynow] > $stockdata[$label][$yprev]
			&& $stockdata[$label][$yprev] > $stockdata[$label][$yprev2];
	} else {
		return $stockdata[$label][$ynow] > $stockdata[$label][$yprev]
			&& $stockdata[$label][$yprev] > $stockdata[$label][$yprev2];
	}
}

// Give scores
foreach ($compiled as $code => $stockdata) {
	$score = 0;
	$detail = [];
	// Aset meningkat 3 tahun terakhir
	if (is_3_tahun($stockdata, 'Total Aset', 'naik')) {
		$score++;
		$detail['Aset meningkat 3 tahun terakhir'] = 1;
	} else {
		$detail['Aset meningkat 3 tahun terakhir'] = 0;
	}
	// Modal meningkat 3 tahun terakhir
	if (is_3_tahun($stockdata, 'Modal', 'naik')) {
		$score++;
		$detail['Modal meningkat 3 tahun terakhir'] = 1;
	} else {
		$detail['Modal meningkat 3 tahun terakhir'] = 0;
	}
	// Aset lancar > Hutang lancar (Current Ratio > 1)
	if ($stockdata[$ynow]['Current Ratio'] > 1) {
		$score++;
		$detail['Aset lancar > Hutang lancar (Current Ratio > 1)'] = 1;
	} else {
		$detail['Aset lancar > Hutang lancar (Current Ratio > 1)'] = 0;
	}
	// Total Hutang < Total Aset (DAR < 1)
	if ($stockdata[$ynow]['DAR'] < 1) {
		$score++;
		$detail['Total Hutang < Total Aset (DAR < 1)'] = 1;
	} else {
		$detail['Total Hutang < Total Aset (DAR < 1)'] = 0;
	}
	// Total Hutang < Total Modal > Hutang (DER < 1)
	if ($stockdata[$ynow]['DER'] < 1) {
		$score++;
		$detail['Total Hutang < Total Modal > Hutang (DER < 1)'] = 1;
	} else {
		$detail['Total Hutang < Total Modal > Hutang (DER < 1)'] = 0;
	}

	// EPS meningkat 3 tahun terakhir
	if (is_3_tahun($stockdata, 'EPS', 'naik')) {
		$score++;
		$detail['EPS meningkat 3 tahun terakhir'] = 1;
	} else {
		$detail['EPS meningkat 3 tahun terakhir'] = 0;
	}
	// Laba usaha meningkat 3 tahun terakhir
	if (is_3_tahun($stockdata, 'Laba Usaha', 'naik')) {
		$score++;
		$detail['Laba usaha meningkat 3 tahun terakhir'] = 1;
	} else {
		$detail['Laba usaha meningkat 3 tahun terakhir'] = 0;
	}
	// Penjualan meningkat 3 tahun terakhir
	if (is_3_tahun($stockdata, 'Penjualan', 'naik')) {
		$score++;
		$detail['Penjualan meningkat 3 tahun terakhir'] = 1;
	} else {
		$detail['Penjualan meningkat 3 tahun terakhir'] = 0;
	}

	// Arus kas bersih positif
	if ($stockdata[$ynow]['Arus Kas'] > 0) {
		$score++;
		$detail['Arus kas bersih positif'] = 1;
	} else {
		$detail['Arus kas bersih positif'] = 0;
	}
	// Arus kas dari operasi > lainnya
	if ($stockdata[$ynow]['Arus Kas Operasi'] > ($stockdata[$ynow]['Arus Kas Investasi'] + $stockdata[$ynow]['Arus Kas Pendanaan'])) {
		$score++;
		$detail['Arus kas dari operasi > lainnya'] = 1;
	} else {
		$detail['Arus kas dari operasi > lainnya'] = 0;
	}

	$compiled[$code]['Score'] = $score;
	$compiled[$code]['Score Detail'] = $detail;
	// Not counted:
	// Rutin membagikan dividen
	// Khusus bank, CAR > 8%
	// Khusus bank, NPL menurun 3 tahun terakhir
}

usort($compiled, function ($a, $b) use ($ynow) {
	if ($a['Score'] > $b['Score']) return -1;
	else if ($a['Score'] < $b['Score']) return 1;
	else if ($a['ROE'][$ynow] > $b['ROE'][$ynow]) return -1;
	else if ($a['ROE'][$ynow] < $b['ROE'][$ynow]) return 1;
	else return 0;
});


file_put_contents('search-best-buku.out', '<?php return '.var_export($compiled, true).';');