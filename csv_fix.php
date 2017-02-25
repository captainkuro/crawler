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
	$out = fopen($file.'-out', 'w');
	while ($data = fgetcsv($in)) {
		foreach ($data as $key => $value) {
			if (preg_match('#^\d\.\d+E\d$#', $value)) {
				$data[$key] = something((float)$value);
			}
		}
		fputcsv($out, $data);
	}
	fclose($in);
	fclose($out);
}
setlocale(LC_ALL, 'id');
fixit('C:/MINE/mutasi-jan-2017.CSV');
// print_r(localeconv());