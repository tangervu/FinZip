#!/usr/bin/php
<?php
/**
 * Update municipality database using data provided by Itella
 * 
 * @author Tuomas Angervuori <tuomas.angervuori@gmail.com>
 */

require_once dirname(__FILE__) . '/../FinZip.php';

$cfg = parse_ini_file(dirname(__FILE__) . '/../settings.ini');
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


echo "* Loading municipalities from db... ";
$stmt = $pdo->prepare("SELECT id, code FROM municipalities");
$stmt->execute();
$municipalities = array(); //array(municipalityCode => municipalityId)
while($row = $stmt->fetch()) {
	$municipalities[$row['code']] = $row['id'];
}
echo "Found " . count($municipalities) . " rows!\n";


echo "* Loading current localities... ";
$stmt = $pdo->prepare("SELECT id, name, municipality FROM localities");
$stmt->execute();
$currentLocalities = array(); //array(municipalityId => array(localityName => localityId))
while($row = $stmt->fetch()) {
	$currentLocalities[$row['municipality']][mb_strtolower($row['name'])] = $row['id'];
}
echo "Done!\n";


echo "* Sorting localities... ";
$localities = array(); //array(municipalityCode => array(localityName => localityData))
foreach($streetnames as $row) {
	$municipalityCode = $row['municipality_code'];
	
	//Group localities by municipalities
	if(!isset($localities[$municipalityCode])) {
		$localities[$municipalityCode] = array();
	}
	
	$localityName = mb_strtolower($row['locality']);
	if(!isset($localities[$municipalityCode][$localityName])) { //Only add unique localities
		$localities[$municipalityCode][$localityName] = array(
			'name' => $row['locality'],
			'short' => $row['locality_short']
		);
		if($row['streetnumber_type'] == 0) {
			$localities[$municipalityCode][$localityName]['type'] = 0;
		}
		else {
			$localities[$municipalityCode][$localityName]['type'] = 1;
		}
	}
}
echo "Done!\n";


echo "* Updating localities... ";
$new = 0;
$updated = 0;
$removed = 0;
$insertStmt = $pdo->prepare("INSERT INTO localities SET name = :name, short = :short, type = :type, municipality = :municipality, created = NOW()");
$updateStmt = $pdo->prepare("UPDATE localities SET name = :name, short = :short, type = :type WHERE id = :id");
$localityIds = array(); //array(municipalityId => array(localityName => localityId))
foreach($localities as $municipalityCode => $localityData) {
	
	if(isset($municipalities[$municipalityCode])) {
		$municipalityId = $municipalities[$municipalityCode];
		
		//Add & update locality info
		$localitiesFound = array();
		foreach($localityData as $localityName => $data) {
			if(isset($currentLocalities[$municipalityId][$localityName])) {
				$id = $currentLocalities[$municipalityId][$localityName];
				$updateStmt->execute(array(
					':name' => $data['name'],
					':short' => $data['short'],
					':type' => $data['type'],
					':id' => $id
				));
				$updated++;
				$localitiesFound[] = $id;
				
			}
			else {
				$insertStmt->execute(array(
					':name' => $data['name'],
					':short' => $data['short'],
					':type' => $data['type'],
					':municipality' => $municipalityId
				));
				$new++;
				$id = $pdo->lastInsertId();
				$localitiesFound[] = $id;
			}
			
			//Update locality ids
			if(!isset($localityIds[$municipalityId])) {
				$localityIds[$municipalityId] = array();
			}
			$localityIds[$municipalityId][$localityName] = $id;
		}
		
		//Remove old localities
		$foundIds = '';
		foreach($localitiesFound as $id) {
			if($foundIds != '') {
				$foundIds .= ',';
			}
			$foundIds .= $pdo->quote($id);
		}
		$stmt = $pdo->prepare("DELETE FROM localities WHERE municipality = :municipality AND id NOT IN ($foundIds)");
		$stmt->execute(array(':municipality' => $municipalityId));
		$removed += $stmt->rowCount();
	}
	else {
		trigger_error("Unknown municipality code $municipalityCode",E_USER_WARNING);
	}
}
echo "$new localities new, $updated updated, $removed removed!\n";


echo "* Loading streetnames from db... ";
$stmt = $pdo->prepare("SELECT id, name, locality FROM streetnames");
$stmt->execute();
$dbStreetnames = array(); //array(localityId => array(streetName => id))
while($row = $stmt->fetch()) {
	if(!isset($dbStreetnames[$row['locality']])) {
		$dbStreetnames[$row['locality']] = array();
	}
	$dbStreetnames[$row['locality']][mb_strtolower($row['name'])] = $row['id'];
}
echo "Done!\n";


echo "* Updating streetnames...  ";
$new = 0;
$removed = 0;
$insertStmt = $pdo->prepare("INSERT INTO streetnames SET name = :name, locality = :locality, created = NOW()");
$foundStreetIds = array(); //array(localityId => array(streetIds))
$streetNumbers = array(); //array(streetId => array(data))
foreach($streetnames as $row) {
	if($row['street'] && $row['streetnumber_type'] > 0) {
		//print_r($row);
		if(isset($municipalities[$row['municipality_code']])) {
			
			$municipalityId = $municipalities[$row['municipality_code']];
			$localityName = mb_strtolower($row['locality']);
			
			if(isset($localityIds[$municipalityId][$localityName])) {
				$localityId = $localityIds[$municipalityId][$localityName];
				$streetName = mb_strtolower($row['street']);
				
				//Street already in database
				if(isset($dbStreetnames[$localityId]) && isset($dbStreetnames[$localityId][$streetName])) {
					$id = $dbStreetnames[$localityId][$streetName];
				}
				else {
					$insertStmt->execute(array(
						':name' => $row['street'],
						':locality' => $localityId
					));
					$new++;
					$id = $pdo->lastInsertId();
					$dbStreetnames[$localityId][$streetName] = $id;
				}
				
				$foundStreetIds[] = $id;
				
				//Collect street number info
				if(!isset($streetNumbers[$id])) {
					$streetNumbers[$id] = array();
				}
				$streetNumbers[$id][] = array(
					'street' => $id,
					'type' => $row['streetnumber_type'],
					'min' => $row['streetnumber_min'],
					'max' => $row['streetnumber_max'],
					'zip' => $row['zip']
				);
			}
			else {
				trigger_error("Unknown locality: $locality",E_USER_WARNING);
			}
		}
		else {
			trigger_error("Unknown municipality code: $row[municipality_code]",E_USER_WARNING);
		}
	}
}

//Clean up removed localities
if($foundStreetIds) {
	//Removed localities
	$streetIds = '';
	foreach($foundStreetIds as $id) {
		if($streetIds != '') {
			$streetIds .= ',';
		}
		$streetIds .= $pdo->quote($id);
	}
	$stmt = $pdo->prepare("DELETE FROM streetnames WHERE id NOT IN($streetIds)");
	$stmt->execute();
	$removed = $stmt->rowCount();
}
echo "$new streetnames new, $removed removed!\n";


echo "* Updating street number info... ";
$deleteStmt = $pdo->prepare("DELETE FROM streetnumbers WHERE street = :street");
$insertStmt = $pdo->prepare("INSERT INTO streetnumbers SET street = :street, type = :type, min = :min, max = :max, zip = :zip");
foreach($streetNumbers as $streetId => $rows) {
	$pdo->beginTransaction();
	try {
		$deleteStmt->execute(array(':street' => $streetId));
		foreach($rows as $row) {
			$insertStmt->execute(array(
				':street' => $streetId,
				':type' => $row['type'],
				':min' => $row['min'],
				':max' => $row['max'],
				':zip' => $row['zip']
			));
		}
	}
	catch(Exception $e) {
		$pdo->rollBack();
		trigger_error("Street number update failed",E_USER_WARNING);
	}
	$pdo->commit();
}
echo "Done!\n";
