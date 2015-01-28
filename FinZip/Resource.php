<?php


namespace FinZip;

abstract class Resource {
	
	protected $handle;
	
	public function __construct($zipFilename) {
		$zip = new \ZipArchive();
		$zip->open($zipFilename);
		if($zip->numFiles != 1) {
			throw new \Exception("Unknown source file structure");
		}
		$filename = $zip->getNameIndex(0);
		$this->handle = $zip->getStream($filename);
		if(!$this->handle) {
			throw new \Exception("Could not read source");
		}
	}
	
	public function __destruct() {
		if($this->handle) {
			fclose($this->handle);
		}
	}
	
	public function __toString() {
		return stream_get_contents($this->handle);
	}
	
	public function fetch() {
		if($this->valid()) {
			return $this->_readLine();
		}
		else {
			return false;
		}
	}
	
	public function fetchAll() {
		$result = array();
		while($row = $this->fetch()) {
			$result[] = $row;
		}
		return $result;
	}
	
	public function valid() {
		return !feof($this->handle);
	}
	
	abstract protected function _readLine();
}
