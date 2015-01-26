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
$municipalities = array();
foreach($finzip->getLocalities() as $row) {
	$municipalities[$row['municipality_code']] = $row['municipality_name'];
}
echo "Found " . count($municipalities) . " municipalities!\n";


echo "* Load current municipalities from db... ";
$sql = "SELECT id, code FROM $table";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$currentMunicipalities = array();
while($row = $stmt->fetch()) {
	$currentMunicipalities[$row['code']] = $row['id'];
}
echo count($currentMunicipalities) . " municipalities currently in db!\n";


$sql = "INSERT INTO $table SET name = :name, code = :code, created = NOW(), active = 1";
$insertStmt = $pdo->prepare($sql);
$sql = "UPDATE $table SET name = :name, code = :code, active = 1 WHERE id = :id";
$updateStmt = $pdo->prepare($sql);


try {
	$pdo->beginTransaction();
	
	$sql = "UPDATE $table SET active = 0";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	
	$new = 0;
	$updated = 0;
	
	
	echo "* Updating municipality db... ";
	foreach($municipalities as $code => $name) {
		if(isset($currentMunicipalities[$code])) {
			$updateStmt->execute(array(
				':name' => $name,
				':code' => $code,
				':id' => $currentMunicipalities[$code]
			));
			$updated++;
		}
		else {
			$insertStmt->execute(array(
				':name' => $name,
				':code' => $code
			));
			$new++;
		}
	}
	$sql = "DELETE FROM $table WHERE active = 0";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	$deleted = $stmt->rowCount();
	echo "$new added, $updated updated, $deleted deleted!\n";
	
	
	$pdo->commit();
}
catch(Exception $e) {
	$pdo->rollBack();
	throw new Exception("DB update failed",0,$e);
}
