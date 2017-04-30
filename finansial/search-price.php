<?php
require 'vendor/autoload.php';

$bests = include 'search-best-buku.out';
$financials = include 'start-all_standardized.out';

// Config?
$start = 2010;
$end = 2016;
$price_url = "https://chart.finance.yahoo.com/table.csv?s=%s.JK&a=0&b=1&c={$start}&d=11&e=31&f={$end}g=m&ignore=.csv";
$quarter = '4';

// Ambil top stocks
$ambil = 30;
$collected = [];

$i = 0;
while ($i < $ambil) {
	$data = $bests[$i];
	$code = $data['code'];
	echo "Processing $code\n";
	$stock_price_url = sprintf($price_url, $code);
	$p = new Page($stock_price_url);
	$csv = array_map('str_getcsv', explode("\n", $p->content()));
	array_shift($csv);

	foreach ($csv as $row) {
		$date = $row[0];
		$close = floatval($row[4]);
		if (!$close) continue;

		preg_match('#(\d+)-\d+-\d+#', $date, $m);
		$year = $m[1];
		if ($start > $year || $year > $end) continue;

		// Ambil data Harga tertinggi 5 tahun
		if (!isset($collected[$code][$year]['highest'])) {
			$collected[$code][$year]['highest'] = $close;
		} else if ($collected[$code][$year]['highest'] < $close) {
			$collected[$code][$year]['highest'] = $close;
		}

		// Ambil data Harga terendah 5 tahun
		if (!isset($collected[$code][$year]['lowest'])) {
			$collected[$code][$year]['lowest'] = $close;
		} else if ($collected[$code][$year]['lowest'] > $close) {
			$collected[$code][$year]['lowest'] = $close;
		}
	}

	$i++;
}

// Ambil data EPS 5 tahun
foreach ($financials as $row) {
	if (isset($collected[$row['code']])
		&& $start <= $row['year'] 
		&& $row['year'] <= $end
		&& $row['quarter'] == $quarter) {

		$collected[$row['code']][$row['year']]['eps'] = floatval($row['eps']);
	}
}


// Hitung PER terendah + tertinggi tiap tahun
foreach ($collected as $code => $yearly) {
	foreach ($yearly as $year => $stats) {
		if (!isset($stats['eps'])) continue;
		if (!$stats['eps']) continue;

		$per_lowest = $stats['lowest'] / $stats['eps'];
		$per_highest = $stats['highest'] / $stats['eps'];
		$collected[$code][$year]['per_lowest'] = $per_lowest;
		$collected[$code][$year]['per_highest'] = $per_highest;
	}
}

foreach ($collected as $code => $yearly) {
	// Hitung EPS growth per tahun
	// Hitung rata2 EPS growth
	$acc_growth = 0;
	$range = range($start+1, $end);
	$count = 0;
	foreach ($range as $year) {
		if (!isset($yearly[$year-1]['eps'])) continue;
		if (!$yearly[$year-1]['eps']) continue;

		$prev_eps = $yearly[$year-1]['eps'];
		$collected[$code][$year]["eps_growth"] = ($yearly[$year]['eps'] - $prev_eps) * 100 / $prev_eps;
		$acc_growth += $collected[$code][$year]["eps_growth"];
		$count++;
	}
	$collected[$code]['median']['eps_growth'] = $acc_growth / $count;

	// Hitung rata2 PER terendah, PER tertinggi
	$acc_per_lowest = 0;
	$acc_per_highest = 0;
	$range = range($start, $end);
	$count = 0;
	foreach ($range as $year) {
		if (!isset($collected[$code][$year]["per_lowest"])) continue;

		$acc_per_lowest += $collected[$code][$year]["per_lowest"];
		$acc_per_highest += $collected[$code][$year]["per_highest"];
		$count++;
	}

	$collected[$code]['median']['per_lowest'] = $acc_per_lowest / $count;
	$collected[$code]['median']['per_highest'] = $acc_per_highest / $count;
	
}

// Keluarkan perkiraan Harga tahun depan
foreach ($collected as $code => $yearly) {
	// Adjust PER rata2 terendah, tertinggi
	$adj_per_lowest = $yearly['median']['per_lowest'] * 0.9;
	$adj_per_highest = $yearly['median']['per_highest'] * 1.1;
	$per_middle = ($adj_per_lowest + $adj_per_highest) / 2;
	$future_eps = $yearly[$end]['eps'] * (100 + $yearly['median']['eps_growth']) / 100;
	$future_lowest = $future_eps * $adj_per_lowest;
	$future_middle = $future_eps * $per_middle;
	$future_highest = $future_eps * $adj_per_highest;

	$collected[$code]['prediction'] = [
		'year' => $end + 1,
		'Asumsi EPS' => round($future_eps),
		'PER terendah' => sprintf('%.2f', $adj_per_lowest),
		'Harga terendah' => round($future_lowest),
		'PER tengah' => sprintf('%.2f', $per_middle),
		'Harga tengah' => round($future_middle),
		'PER tertinggi' => sprintf('%.2f', $adj_per_highest),
		'Harga tertinggi' => round($future_highest),
	];
}

file_put_contents('search-price-PER-valuation.out', '<?php return '.var_export($collected, true).';');