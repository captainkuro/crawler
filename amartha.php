<?php
include '_header.php';
?>
<style>
	#thetable {
    border-collapse: collapse;
    border: 1px solid;
}

#thetable td,#thetable th {
    border: 1px solid;
    padding: 2px;
}
</style>
<div class='container'>
	<form class='form-horizontal' method="post">
		Amartha Marketplace URL:
		<input type="text" name="market_url" value="<?=@$_POST['market_url'];?>">
		<br>
		<input type="submit" name="">
	</form>
<?php
if ($_POST) {
	$url = $_POST['market_url'];
	$json = file_get_contents($url);
	$parsed = json_decode($json, true);
	$people = $parsed['data']['marketplace'];

	$As = [];
	foreach ($people as $person) {
		if ($person['creditScoreGrade'] === 'A') {
			$As[] = $person;
		}
	}

	?>
	<table id="thetable">
		<tr>
			<th>Nama</th>
			<th>Score</th>
			<th>Province</th>
			<th>Area</th>
			<th>Sector</th>
			<th>Plafond</th>
			<th>Purpose</th>
		</tr>
		<?php foreach ($As as $A) : ?>
			<tr>
				<td><?=$A['borrowerName'];?></td>
				<td><?=$A['creditScoreGrade'];?></td>
				<td><?=$A['provinceName'];?></td>
				<td><?=$A['areaName'];?></td>
				<td><?=$A['sectorName'];?></td>
				<td><?=number_format($A['plafond']);?></td>
				<td><?=$A['purpose'];?></td>
			</tr>
		<?php endforeach; ?>
	</table>
</div>
<?php
}

include '_footer.php';
?>