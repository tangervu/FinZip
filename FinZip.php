<?php
/**
 * Fetch zip code information from Posti
 * 
 * @link http://www.posti.fi/liitteet-yrityksille/ehdot/postinumeropalvelut-palvelukuvaus-ja-kayttoehdot.pdf
 * @author Tuomas Angervuori <tuomas.angervuori@gmail.com>
 * @license http://opensource.org/licenses/LGPL-3.0 LGPL v3
 */

class FinZip {
	
	public $url = 'http://www.posti.fi/webpcode/unzip/';
	
	public function getLocalities() {
		$resource = new FinZip\Localities($this->_getDataUrl('PCF'));
		return $resource;
	}
	
	public function getLocalityUpdates() {
		$resource = new FinZip\LocalityUpdates($this->_getDataUrl('POM'));
		return $resource;
	}
	
	public function getStreetnames() {
		$resource = new FinZip\Streetnames($this->_getDataUrl('BAF'));
		return $resource;
	}
	
	/**
	 * Fetch data file from Itella FTP server
	 * @param $type Data file type (PCF = localities, BAF = street addresses, POM = zip code changes)
	 * @returns handle
	 */
	public function fetchFile($type) {
		
		$handle = fopen($fileList[$type],'rb');
		if(!$handle) {
			throw new Exception("Could not open url '$url'");
		}
		
		return $handle;
	}
	
	/**
	 * Get directory list
	 * @param $url Base directory
	 * @return string url to requested data file
	 **/
	private function _getDataUrl($type) {
		$html = file_get_contents($this->url);
		if($html === false) {
			throw new Exception("Could not open url '{$this->url}'");
		}
		$dom = new DOMDocument();
		$dom->loadHTML($html);
		$links = array();
		foreach($dom->getElementsByTagName('a') as $item) {
			$link = $item->getAttribute('href');
			$data = pathinfo($link);
			$parts = explode('_',$data['basename']);
			if(in_array($parts[0], array('PCF','POM','BAF'))) {
				$links[$parts[0]] = $link;
			}
		}
		
		if(isset($links[$type])) {
			return $links[$type];
		}
		else {
			throw new Exception("Could not locate file type '$type'");
		}
	}
}
