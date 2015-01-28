#!/usr/bin/php
<?php
/**
 * Update streetname database using data provided by Posti.fi
 * 
 * @author Tuomas Angervuori <tuomas.angervuori@gmail.com>
 */

require_once __DIR__ . '/../vendor/autoload.php'; //Include the Composer autoloader

$cfg = parse_ini_file(__DIR__ . '/../settings.ini');
mb_internal_encoding('UTF-8');


echo "* Initializing db connection... ";
$pdo = new PDO($cfg['dsn'], $cfg['username'], $cfg['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_NAMED);
echo "Done!\n";


echo "* Loading streetnames... ";
$finzip = new FinZip();
$streetnames = $finzip->getStreetnames();
echo "Done!\n";


echo "* Update data table... ";
$pdo->beginTransaction();
try {
	$sql = "DELETE FROM data WHERE 1=1"; //Transaction-friendly truncate
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	
	$sql = "INSERT INTO data SET
				zip = :zip,
				locality = :locality,
				locality_short = :locality_short,
				street = :street,
				streetnumber_type = :streetnumber_type,
				streetnumber_min = :streetnumber_min,
				streetnumber_max = :streetnumber_max,
				municipality_code = :municipality_code,
				municipality_name = :municipality_name";
	$stmt = $pdo->prepare($sql);
	
	while($row = $streetnames->fetch()) {
		if($row['streetnumber_type'] == 1) {
			$streetnumberType = 'odd';
		}
		else if($row['streetnumber_type'] == 2) {
			$streetnumberType = 'even';
		}
		else {
			$streetnumberType = null;
		}
		$stmt->execute(array(
			':zip' => $row['zip'],
			':locality' => $row['locality'],
			':locality_short' => $row['locality_short'],
			':street' => $row['street'],
			':streetnumber_type' => $streetnumberType,
			':streetnumber_min' => $row['streetnumber_min'],
			':streetnumber_max' => $row['streetnumber_max'],
			':municipality_code' => $row['municipality_code'],
			':municipality_name' => $row['municipality_name']
		));
	}
	
	$pdo->commit();
}
catch(Exception $e) {
	$pdo->rollBack();
	throw new Exception("Database update error",0,$e);
}
echo "Done!\n";


echo "* Update municipalities... ";
$pdo->beginTransaction();
try {
	$sql = "UPDATE municipalities SET active = 0";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	
	$sql = "INSERT INTO municipalities SET
				name = :name,
				code = :code,
				created = NOW(),
				active = 1
			ON DUPLICATE KEY UPDATE
				name = :name_new,
				code = :code_new,
				active = 1";
	$updtStmt = $pdo->prepare($sql);
	
	$sql = "SELECT DISTINCT municipality_code, municipality_name FROM data";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	while($row = $stmt->fetch()) {
		$updtStmt->execute(array(
			':name' => $row['municipality_name'],
			':code' => $row['municipality_code'],
			':name_new' => $row['municipality_name'],
			':code_new' => $row['municipality_code']
		));
	}
	
	$sql = "DELETE FROM municipalities WHERE active = 0";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	
	$pdo->commit();
}
catch(Exception $e) {
	$pdo->rollBack();
	throw new Exception("Municipality update error",0,$e);
}
echo "Done!\n";


echo "* Update localities... ";
$pdo->beginTransaction();
try {
	$sql = "UPDATE localities SET active = 0";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	
	$sql = "SELECT id, code FROM municipalities";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	$municipalities = array();
	while($row = $stmt->fetch()) {
		$municipalities[$row['code']] = $row['id'];
	}
	
	$sql = "INSERT INTO localities SET
				name = :name,
				short = :short,
				municipality = :municipality,
				created = NOW(),
				active = 1
			ON DUPLICATE KEY UPDATE
				name = :name_new,
				short = :short_new,
				municipality = :municipality_new,
				active = 1";
	$updtStmt = $pdo->prepare($sql);
	
	$sql = "SELECT DISTINCT locality, locality_short, municipality_code FROM data";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	while($row = $stmt->fetch()) {
		$updtStmt->execute(array(
			':name' => $row['locality'],
			':short' => $row['locality_short'],
			':municipality' => $municipalities[$row['municipality_code']],
			':name_new' => $row['locality'],
			':short_new' => $row['locality_short'],
			':municipality_new' => $municipalities[$row['municipality_code']]
		));
	}
	
	$sql = "DELETE FROM localities WHERE active = 0";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	
	$pdo->commit();
}
catch(Exception $e) {
	$pdo->rollBack();
	throw new Exception("Locality update error",0,$e);
}
echo "Done!\n";


echo "* Update streetnames... ";
$pdo->beginTransaction();
try {
	$sql = "UPDATE streetnames SET active = 0";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	
	$sql = "SELECT l.id, l.name, m.code
			FROM localities l
			JOIN municipalities m ON l.municipality=m.id";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	$localities = array();
	while($row = $stmt->fetch()) {
		if(!isset($localities[$row['code']])) {
			$localities[$row['code']] = array();
		}
		$localities[$row['code']][$row['name']] = $row['id'];
	}
	
	$sql = "INSERT INTO streetnames SET
				name = :name,
				locality = :locality,
				created = NOW(),
				active = 1
			ON DUPLICATE KEY UPDATE
				name = :name_new,
				locality = :locality_new,
				active = 1";
	$updtStmt = $pdo->prepare($sql);
	
	$sql = "SELECT DISTINCT street, locality, municipality_code FROM data WHERE street IS NOT NULL";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	while($row = $stmt->fetch()) {
		$updtStmt->execute(array(
			':name' => $row['street'],
			':locality' => $localities[$row['municipality_code']][$row['locality']],
			':name_new' => $row['street'],
			':locality_new' => $localities[$row['municipality_code']][$row['locality']]
		));
	}
	
	$sql = "DELETE FROM streetnames WHERE active = 0";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	
	$pdo->commit();
}
catch(Exception $e) {
	$pdo->rollBack();
	throw new Exception("Streetname update error",0,$e);
}
echo "Done!\n";


echo "* Update streetnumbers... ";
$pdo->beginTransaction();
try {
	$sql = "DELETE FROM streetnumbers WHERE 1=1"; //Transaction-friendly truncate
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	
	$sql = "INSERT INTO streetnumbers (street, type, min, max, zip)
			SELECT s.id, d.streetnumber_type, d.streetnumber_min, d.streetnumber_max, d.zip
			FROM data d
			JOIN municipalities m ON d.municipality_code=m.code
			JOIN localities l ON m.id=l.municipality AND l.name=d.locality
			JOIN streetnames s ON s.locality=l.id AND s.name=d.street
			WHERE d.street IS NOT NULL
			AND (d.streetnumber_min IS NOT NULL OR d.streetnumber_max IS NOT NULL)";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	
	$pdo->commit();
}
catch(Exception $e) {
	$pdo->rollBack();
	throw new Exception("Streetnumber update error",0,$e);
}
echo "Done!\n";
