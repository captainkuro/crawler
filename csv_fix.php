<?php
function something($number)
{
    $locale = localeconv();
    return number_format($number,
       	2,
        $locale['decimal_point'],
        '');
}

function fixit($file) {
	$in = fopen($file, 'r');
	$out = fopen($file.'-out.csv', 'w');
	while ($data = fgetcsv($in)) {
		foreach ($data as $key => $value) {
			if (preg_match('#^\d\.\d+E\d$#', $value)) {
				$data[$key] = something((float)$value);
			} else if (preg_match('#^\d{4,}\.\d\d$#', $value)) {
				$data[$key] = str_replace('.', ',', $value);
			}
		}
		fputcsv($out, $data);
	}
	fclose($in);
	fclose($out);
}
setlocale(LC_ALL, 'id');
fixit('C:\MINE\Download\KHANDARW0705_1016306113.CSV');
// print_r(localeconv());