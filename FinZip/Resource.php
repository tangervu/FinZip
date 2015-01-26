<?php

namespace FinZip;

class Resource implements \SeekableIterator {
	
	protected $tmpFile;
	protected $handle;
	protected $key = 0;
	protected $line;
	
	public function __construct($zipFilename) {
		$zip = new \ZipArchive();
		$zip->open($zipFilename);
		if($zip->numFiles != 1) {
			throw new \Exception("Unknown source file structure");
		}
		$this->handle = $zip->getStream($zip->getNameIndex(0));
		if(!$this->handle) {
			throw new \Exception("Could not read source");
		}
	}
	
	public function __destruct() {
		if($this->handle) {
			fclose($this->handle);
		}
	}
	
	public function seek($position) {
		if(fseek($this->handle, $position) !== 0) {
			throw new \OutOfBoundsException("Position $position out of bounds");
		}
		$this->key = $position;
		$this->_readLine();
	}
	
	public function current() {
		return $this->line;
	}
	
	public function key() {
		return $this->key;
	}
	
	public function next() {
		$this->key++;
		$this->_readLine();
	}
	
	public function rewind() {
		rewind($this->handle);
		$this->key = 0;
		$this->_readLine();
	}
	
	public function valid() {
		return !feof($this->handle);
	}
	
	protected function _readLine() {
		$this->line = utf8_encode(fread($this->handle,1024));
	}
}
