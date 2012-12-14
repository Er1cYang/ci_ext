<?php
namespace ci_ext\web;

class DataProvider extends \ci_ext\core\Object {
	
	private $_data;
	private $_pagination;
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
	
	public function getPagination() {
		if(!$this->_pagination) {
			$this->_pagination = new Pagination();
		}
		return $this->_pagination;
	}
	
	public function setPagination($pagination) {
		$this->_pagination = $pagination;
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