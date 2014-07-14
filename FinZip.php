<?php
/**
 * Fetch zip code information from Itella
 * 
 * @link http://www.itella.fi/liitteet/palvelutjatuotteet/yhteystietopalvelut/Postinumeropalvelut-Palvelukuvausjakayttoehdot.pdf
 * @author Tuomas Angervuori <tuomas.angervuori@gmail.com>
 * @license http://opensource.org/licenses/LGPL-3.0 LGPL v3
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
		require_once dirname(__FILE__) . '/FinZip/Localities.php';
		$resource = new FinZip\Localities($tmpFile);
		return $resource;
	}
	
	public function getLocalityUpdates() {
		$tmpFile = $this->fetchFile('POM');
		require_once dirname(__FILE__) . '/FinZip/LocalityUpdates.php';
		$resource = new FinZip\LocalityUpdates($tmpFile);
		return $resource;
	}
	
	public function getStreetnames() {
		$tmpFile = $this->fetchFile('BAF');
		require_once dirname(__FILE__) . '/FinZip/Streetnames.php';
		$resource = new FinZip\Streetnames($tmpFile);
		return $resource;
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
			throw new Exception("Could not connect to '{$this->host}'");
		}
		if(!ftp_login($ftp, $this->user, $this->password)) {
			throw new Exception("Login to '{$this->host}' as '{$this->user}' failed");
		}
		
		//Find filename to download
		ftp_pasv($ftp, true);
		$list = ftp_nlist($ftp,'.');
		$file = null;
		foreach($list as $item) {
			$parts = explode('_',$item);
			if(isset($parts[1]) && strtoupper($parts[1]) == strtoupper($type)) {
				$file = $item;
			}
		}
		if($file == null) {
			throw new Exception("'$type' file not found");
		}
		
		//Download requested data file
		$tmpFile = tempnam(sys_get_temp_dir(),'FinZip_' . $type . '_') . '.zip';
		$this->tmpFiles[] = $tmpFile;
		$tmp = fopen($tmpFile,'w');
		ftp_pasv($ftp, true);
		ftp_fget($ftp, $tmp, $file, FTP_BINARY);
		ftp_close($ftp);
		fclose($tmp);
		
		//Return the filename of the temporary file
		return $tmpFile;
	}
}
