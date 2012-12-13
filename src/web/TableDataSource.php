<?php
namespace ci_ext\web;

use ci_ext\db\DbCriteria;

class TableDataSource extends DataSource {
	
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
		$page = $this->page;
		$criteria->limit = $page->pageSize;
		$criteria->offset = ($page->getCurrentPage()-1)*$page->pageSize;
		$data = $this->_table->findAll($criteria);
		return $data;
	}
	
	
}

?>