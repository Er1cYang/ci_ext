<?php
namespace ci_ext\web;

use ci_ext\db\DbCriteria;

class TableDataProvider extends DataProvider {
	
	private $_tableClass;
	private $_table;
	
	public function __construct($table, $config = array()) {
		if(is_string($table)) {
			$this->_tableClass = $table;
			$this->_table = new $table();
		} else {
			$this->_table = $table;
			$this->_tableClass = get_class($table);
		}
		parent::__construct($config);
	}
	
	protected function fetchKeys() {
		$keys = array_keys($this->_table->attributes);
	}
	
	protected function fetchData() {
		$criteria = new DbCriteria();
		$page = $this->pagination;
		$page->itemCount = $this->_table->count();
		$page->applyLimit($criteria);
		$data = $this->_table->findAll($criteria);
		return $data;
	}
	
	public function getModel() {
		return $this->_table;
	}
	
	
}

?>