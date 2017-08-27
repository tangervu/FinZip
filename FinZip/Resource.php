<?php


namespace FinZip;

abstract class Resource {
	
	protected $handle;
	protected $row = 0;
	
	public function __construct($url) {
		mb_internal_encoding('UTF-8');
		$this->handle = fopen($url,'r');
		if(!$this->handle) {
			throw new Exception("Could not open url '$url'");
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
			return $this->_getData();
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
	
	abstract protected function _getData();
	
	protected function _readLine() {
		
		$row = fgets($this->handle);
		
		if($row !== false && trim($row) != '') {
			return utf8_encode($row);
		}
		else {
			return false;
		}
	}
	
	protected function _trimArray($item) {
		foreach($item as $key => $val) {
			$val = trim($val);
			if($val == '') {
				$val = null;
			}
			$item[$key] = $val;
		}
		return $item;
	}
	
	protected function _convertDate($str) {
		return mb_substr($str,0,4) . '-' . mb_substr($str,4,2) . '-' . mb_substr($str,6,2);
	}
}
