#!/usr/bin/php
<?php
/**
 * Update municipality database using data provided by Itella
 * 
 * @author Tuomas Angervuori <tuomas.angervuori@gmail.com>
 */

require_once __DIR__ . '/../vendor/autoload.php'; //Include the Composer autoloader

$cfg = parse_ini_file(__DIR__ . '/../settings.ini');
$table = 'municipalities';


echo "* Initializing db connection... ";
$pdo = new PDO($cfg['dsn'], $cfg['username'], $cfg['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_NAMED);
echo "Done!\n";


echo "* Loading municipality data... ";
$finzip = new FinZip();
$zipCodes = $finzip->getLocalities();
echo "Done!\n";


echo "* Filtering data... ";
$municipalities = array();
foreach($zipCodes as $row) {
	$code = $row['municipality_code'];
	$name = $row['municipality_name'];
	if(!isset($municipalities[$code])) {
		$municipalities[$code] = array(
			'code' => $code,
			'name' => $name
		);
	}
}
echo "Found " . count($municipalities) . " municipalities!\n";


echo "* Loading current municipalities from db... ";
$sql = "SELECT id, name, code FROM $table";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$currentMunicipalities = array();
while($row = $stmt->fetch()) {
	$currentMunicipalities[$row['code']] = array(
		'id' => $row['id'],
		'name' => $row['name']
	);
}
echo "Found " . count($currentMunicipalities) . " municipalities!\n";


echo "* Updating municipality db:\n";
$new = 0;
$updated = 0;
$skipped = 0;
$foundMunicipalities = array();
$insertStmt = $pdo->prepare("INSERT INTO $table SET name = :name, code = :code, created = NOW()");
$updateStmt = $pdo->prepare("UPDATE $table SET name = :name, code = :code WHERE id = :id");
foreach($municipalities as $code => $row) {
	$data = array(
		':name' => $row['name'],
		':code' => $row['code']
	);
	
	$foundMunicipalities[] = $row['code'];
	
	if(isset($currentMunicipalities[$code])) {
		if($currentMunicipalities[$code]['name'] == $row['name']) {
			//Nothing has changed
			$skipped++;
		}
		else {
			//Municipality name has changed
			$data[':id'] = $currentMunicipalities[$code]['id'];
			$updateStmt->execute($data);
			echo " - Update: {$currentMunicipalities[$code]['name']} -> {$row['name']} ({$row['code']})\n";
			$updated++;
		}
	}
	else {
		//New municipality
		$insertStmt->execute($data);
		echo " - New: {$row['name']} ({$row['code']})\n";
		$new++;
	}
}
echo "=> $new added, $updated updated, $skipped skipped\n";


echo "* Removing deprecated municipalities... ";
if($foundMunicipalities) {
	$foundIds = '';
	foreach($foundMunicipalities as $id) {
		if($foundIds != '') {
			$foundIds .= ',';
		}
		$foundIds .= $pdo->quote($id);
	}
	$stmt = $pdo->prepare("DELETE FROM $table WHERE code NOT IN ($foundIds)");
	$stmt->execute();
}
echo "Done!\n";
