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
		$filename = $zip->getNameIndex(0);
		$tmpDir = sys_get_temp_dir() . '/FinZip';
		if(!is_dir($tmpDir)) {
			mkdir($tmpDir);
		}
		$this->tmpFile = $tmpDir . '/' . $filename;
		if(!$zip->extractTo($tmpDir, $filename)) {
			throw new \Exception("Could not extract source");
		}
		$this->handle = fopen($this->tmpFile,'r');
		if(!$this->handle) {
			throw new \Exception("Could not read source");
		}
	}
	
	public function __destruct() {
		if($this->handle) {
			fclose($this->handle);
		}
		if($this->tmpFile) {
			unlink($this->tmpFile);
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
