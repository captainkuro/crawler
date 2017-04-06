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
	// url
	// data-pair-id="101278"
// 5. open https://id.investing.com/equities/bank-pembangun-dividends
// 6. grab dividen and year, group by year
// 7. compile into list
// deprecated:
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
		$url = 'https://id.investing.com' . $span->prev_sibling()->getAttribute('href');
		$result[$id] = [$name, $url];
	}
	return $result;
}
// exporte('all_pairs.out', all_pairs());

function all_dividens() {
	$pairs = include 'all_pairs.out';
	$result = [];
	foreach ($pairs as $id => list($name, $url)) {
		echo "$id $name\n";
		$dividen_url = $url . '-dividends';
		try {
			$p = new Page($dividen_url, array('become_firefox'=>true));
		} catch (Exception $e) {
			echo "Failed to get dividen: $name $dividen_url\n";
			continue;
		}

		$h = new simple_html_dom();
		$h->load($p->content());

		$header = $h->find('.instrumentHeader h2', 0);
		preg_match('#Dividen (\w+)#', $header->innertext(), $m);
		$code = $m[1];

		if (strpos($p->content(), 'earningNoData')) {
			echo "No Dividen: $code $name\n";
			continue;
		}

		$compile = array();
		$table = $h->find('.dividendTbl', 0);
		foreach ($table->find('td.first') as $td) {
			preg_match('#(\d{4}),#', $td->innertext(), $m);
			$year = $m[1];
			$dividen = $td->next_sibling()->innertext();
			$compile[$year][] = $dividen;
		}

		$data = [
			'id' => $id,
			'name' => $name,
			'code' => $code,
			'dividen' => $compile,
		];
		$result[$name] = $data;
	}
	return $result;
}
// exporte('all_dividens.out', all_dividens());
// exit;

function standardize_dividen() {
	$dividens = include 'all_dividens.out';
	$financials = include 'finan_2016.out';
	$result = [];
	foreach ($dividens as $name => $data) {
		$finan = $financials[$data['code']];
		if (isset($data['dividen'][2016]) && isset($finan['4th Quarter 2016'])) {
			$dividen = array_sum($data['dividen'][2016]);
			$item = [
				'Name' => $name,
				'Code' => $data['code'],
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