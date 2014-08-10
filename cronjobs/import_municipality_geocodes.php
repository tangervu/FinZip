#!/usr/bin/php
<?php
/**
 * Update municipality database geocodes using Google Geocoding API
 * 
 * @author Tuomas Angervuori <tuomas.angervuori@gmail.com>
 * @requires php-google-maps/php-google-maps
 */

require_once __DIR__ . '/../vendor/autoload.php'; //Include the Composer autoloader

$cfg = parse_ini_file(__DIR__ . '/../settings.ini');
$table = 'municipalities';
$dateLimit = new DateTime('-1 year'); //When to refresh loaded geodata


echo "* Initializing db connection... ";
$pdo = new PDO($cfg['dsn'], $cfg['username'], $cfg['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_NAMED);
echo "Done!\n";


echo "* Loading updated municipalities from db... ";
$sql = "SELECT id, name FROM $table WHERE locationUpdated IS NULL OR updated > locationUpdated OR locationUpdated < :dateLimit";
$stmt = $pdo->prepare($sql);
$stmt->execute(array(':dateLimit' => $dateLimit->format('Y-m-d')));
$municipalities = array();
while($row = $stmt->fetch()) {
	$municipalities[$row['id']] = $row['name'];
}
echo "Found " . count($municipalities) . " municipalities!\n";


echo "* Fetching geocode information:\n";
$geocoder = new PHPGoogleMaps\Service\Geocoder();
$updateStmt = $pdo->prepare("UPDATE $table SET location = GEOMFROMTEXT(:point, 0), locationUpdated = NOW() WHERE id = :id");
foreach($municipalities as $id => $municipality) {
	echo " - $municipality: ";
	$locationString = $municipality . ', Finland';
	$response = $geocoder->geocode($locationString);
	if($response instanceof PHPGoogleMaps\Service\GeocodeResult) {
		$result = $response->response->results;
		$num = count($result);
		echo $num . " locations\n";
		if($num > 0) {
			$lat = (float)$result[0]->geometry->location->lat;
			$lng = (float)$result[0]->geometry->location->lng;
			$updateStmt->execute(array(
				':point' => "POINT($lng $lat)",
				':id' => $id
			));
		}
		sleep(1); //Let's not overload Google servers :)
	}
	else {
		trigger_error("Geocoding '$municipality' failed",E_USER_WARNING);
	}
}
