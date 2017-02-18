<?php
require '../class/simple_html_dom.php';
require '../class/text.php';
require '../class/page.php';

// https://id.investing.com/equities/indonesia
// 1. open id.investing
// 2. get all stock links
// 3. for each stock links
// 4. open page, grab info ...
	// title
	// data-pair-id="101278"
// 5. open https://id.investing.com/instruments/Financials/changereporttypeajax?action=change_report_type&pair_ID=101278&report_type=INC&period_type=Annual
// 6. grab periode (year), grab dividen (per year)
// 7. compile into list

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
		$result[$id] = $name;
	}
	return $result;
}
// exporte('all_pairs.out', all_pairs());

function all_dividens() {
	$pairs = include 'all_pairs.out';
	$result = [];
	foreach ($pairs as $id => $name) {
		echo "$id $name\n";
		$table_url = "https://id.investing.com/instruments/Financials/changereporttypeajax?action=change_report_type&pair_ID={$id}&report_type=INC&period_type=Annual";
		try {
			$p = new Page($table_url, array('become_firefox'=>true));
		} catch (Exception $e) {
			echo "Failed to get financial: $name\n";
			continue;
		}
		$h = new simple_html_dom();
		$h->load($p->content());

		$header = $h->find('#header_row', 0);
		$periods = [];
		foreach ($header->find('.bold') as $span) {
			$periods[] = trim($span->innertext());
		}

		$summary_url = "https://id.investing.com/instruments/Financials/changesummaryreporttypeajax?action=change_report_type&pid={$id}&financial_id={$id}&ratios_id={$id}&period_type=Annual";
		$p = new Page($summary_url, ['become_firefox' => true]);
		preg_match('#Laporan Laba Rugi (\w+)#', $p->content(), $m);
		$data = [
			'id' => $id,
			'name' => $name,
			'code' => $m[1],
		];

		foreach ($h->find('tr') as $tr) {
			if (strpos($tr->innertext(), 'Dividen per Saham') !== false) {
				foreach ($periods as $i => $year) {
					$dividen = $tr->find('td', $i+1)->innertext();
					$data[$year] = trim($dividen);
				}
			}
		}

		$result[$name] = $data;
	}
	return $result;
}
// exporte('all_dividens.out', all_dividens());

function standardize_dividen() {
	$dividens = include 'all_dividens.out';
	$financials = include 'finan_2016.out';
	$result = [];
	foreach ($dividens as $name => $data) {
		$finan = $financials[$data['code']];
		if (isset($data[2015]) && $data[2015] != '-' && isset($finan['3rd Quarter 2016'])) {
			$item = [
				'Name' => $name,
				'Code' => $data['code'],
				'Dividen 2015' => $data[2015],
			];
			$item = $item + $finan['3rd Quarter 2016'];
			$item['Rasio Dividen'] = sprintf('%.2f', 
				floatval(str_replace(',', '', $item['Close Price'])) 
				/ 
				floatval(str_replace(',', '', $data[2015]))
			);
			$result[] = $item;
		}
	}
	return $result;
}

function search_dividen() {
	$dividens = standardize_dividen();
	usort($dividens, function ($a, $b) {
		return (floatval($a['Rasio Dividen']) < floatval($b['Rasio Dividen'])) ? -1 : 1;
	});
	return $dividens;
}
exporte('search_dividen.out', search_dividen());