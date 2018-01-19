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
    $locale = localeconv();

	while ($data = fgetcsv($in)) {
		foreach ($data as $key => $value) {
			if (preg_match('#^\d\.\d+E\d$#', $value)) {
				$data[$key] = something((float)$value);
			} else if (preg_match('#^\d{4,}\.\d\d$#', $value)) {
				$data[$key] = str_replace('.', $locale['decimal_point'], $value);
			}
		}
		fputcsv($out, $data);
	}
	fclose($in);
	fclose($out);
}
setlocale(LC_ALL, 'id');
fixit('C:\\MINE\\Download\\KHANDARW0705_1732514504.CSV');
// print_r(localeconv());