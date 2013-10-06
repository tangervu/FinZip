<?php
/**
 * Fetch zip code information from Itella
 * 
 * @link http://www.itella.fi/liitteet/palvelutjatuotteet/yhteystietopalvelut/uusi_postinumeropalvelut_palvelukuvaus_ja_kayttoehdot.pdf
 * @author Tuomas Angervuori <tuomas.angervuori@gmail.com>
 */

class FinZip {
	
	public $host = 'ftp2.itella.com';
	public $user = 'postcode';
	public $password = 'postcode';
	
	protected $tmpFiles = array();
	
	public function __destruct() {
		foreach($this->tmpFiles as $file) {
			unlink($file);
		}
	}
	
	public function getLocalities() {
		$tmpFile = $this->fetchFile('PCF');
		$data = $this->extractZip($tmpFile);
		
		mb_internal_encoding('UTF-8');
		
		$rows = explode("\n", $data);
		$result = array();
		foreach($rows as $row) {
			$item = array(
				'struct' => mb_substr($row,0,5),
				'updated' => mb_substr($row,5,8),
				'zip' => mb_substr($row,13,5),
				'locality' => mb_substr($row,18,30),
				'locality_swe' => mb_substr($row,48,30),
				'locality_short' => mb_substr($row,78,12),
				'locality_short_swe' => mb_substr($row,90,12),
				'created' => mb_substr($row,102,8),
				'type' => mb_substr($row,110,1), //1,2 = normal, 3 = postilokero, 4 = corporate zip, 7=koontipostinumero, 8=erikoispostinumero
				'govt_area_code' => mb_substr($row,111,5),
				'govt_area_name' => mb_substr($row,116,30),
				'govt_area_name_swe' => mb_substr($row,146,30),
				'municipality_code' => mb_substr($row,176,3),
				'municipality_name' => mb_substr($row,179,20),
				'municipality_name_swe' => mb_substr($row,199,20),
				'municipality_language' => mb_substr($row,219,1) //1 = Finnish, 2, 3 = Bilangual, 4 = Swedish
			);
			
			foreach($item as $key => $val) {
				$val = trim($val);
				if($val == '') {
					$val = null;
				}
				$item[$key] = $val;
			}
			foreach(array('updated','created') as $i) {
				if($item[$i]) {
					$str = $item[$i];
					$item[$i] = mb_substr($str,0,4) . '-' . mb_substr($str,4,2) . '-' . mb_substr($str,6,2);
				}
			}
			$result[] = $item;
		}
		
		return $result;
	}
	
	/**
	 * Fetch data file from Itella FTP server
	 * @param $type Data file type (PCF = localities, BAF = street addresses, POM = zip code changes)
	 * @returns Temp file name
	 */
	public function fetchFile($type) {
		//Connect to FTP server
		$ftp = ftp_connect($this->host);
		if($ftp === false) {
			throw new FinZipException("Could not connect to '{$this->host}'");
		}
		if(!ftp_login($ftp, $this->user, $this->password)) {
			throw new FinZipException("Login to '{$this->host}' as '{$this->user}' failed");
		}
		
		//Find filename to download
		$list = ftp_nlist($ftp,'.');
		$file = null;
		foreach($list as $item) {
			$parts = explode('_',$item);
			if(isset($parts[1]) && strtoupper($parts[1]) == strtoupper($type)) {
				$file = $item;
			}
		}
		if($file == null) {
			throw new FinZipException("'$type' file not found");
		}
		
		//Download requested data file
		$tmpFile = tempnam(sys_get_temp_dir(),'postcode_' . $type . '_') . '.zip';
		$this->tmpFiles[] = $tmpFile;
		$tmp = fopen($tmpFile,'w');
		ftp_pasv($ftp, true);
		ftp_fget($ftp, $tmp, $file, FTP_BINARY);
		ftp_close($ftp);
		fclose($tmp);
		
		//Return the filename of the temporary file
		return $tmpFile;
	}
	
	public function extractZip($filename) {
		$zip = new ZipArchive();
		$zip->open($filename);
		if($zip->numFiles != 1) {
			throw new FinZipException("Unknown zip structure");
		}
		return utf8_encode(trim($zip->getFromIndex(0)));
	}
}

class FinZipException extends Exception { }