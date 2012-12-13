<?php
namespace ci_ext\web;

class DataSource extends \ci_ext\core\Object {
	
	private $_data;
	private $_page;
	private $_keys;

	public function __construct($config=array()) {
		foreach($config as $k=>$v) {
			$this->$k=$v;
		}
	}
	
	public function getData() {
		if(!$this->_data) {
			$this->_data = $this->fetchData();
		}
		return $this->_data;
	}
	
	protected function fetchData() {
	}
	
	public function setData($data) {
		$this->_data = $data;
	}
	
	public function getPage() {
		if(!$this->_page) {
			$this->_page = new Page();
		}
		return $this->_page;
	}
	
	public function setPage($page) {
		$this->_page = $page;
	}
	
	protected function fetchKeys() {
	}
	
	public function getKeys() {
		if(!$this->_keys) {
			$this->_keys=$this->fetchKeys();
		}
		return $this->_keys;
	}
	
}

?>