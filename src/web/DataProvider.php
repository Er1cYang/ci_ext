<?php
namespace ci_ext\web;

abstract class DataProvider extends \ci_ext\core\Object {
	
	private $_id;
	private $_data;
	private $_keys;
	private $_totalItemCount;
	private $_sort;
	private $_pagination;
	
	abstract protected function fetchData();
	abstract protected function fetchKeys();
	abstract protected function calculateTotalItemCount();
	
	public function getId() {
		return $this->_id;
	}
	public function setId($value) {
		$this->_id = $value;
	}
	
	public function getPagination() {
		if ($this->_pagination === null) {
			$this->_pagination = new Pagination ();
			if (($id = $this->getId ()) != '')
				$this->_pagination->pageVar = $id . '_page';
		}
		return $this->_pagination;
	}
	
	public function setPagination($value) {
		if (is_array ( $value )) {
			$pagination = $this->getPagination ();
			foreach ( $value as $k => $v )
				$pagination->$k = $v;
		} else
			$this->_pagination = $value;
	}
	
	public function getSort() {
		if ($this->_sort === null) {
			$this->_sort = new Sort ();
			if (($id = $this->getId ()) != '')
				$this->_sort->sortVar = $id . '_sort';
		}
		return $this->_sort;
	}
	
	public function setSort($value) {
		if (is_array ( $value )) {
			$sort = $this->getSort ();
			foreach ( $value as $k => $v )
				$sort->$k = $v;
		} else
			$this->_sort = $value;
	}
	
	public function getData($refresh = false) {
		if ($this->_data === null || $refresh)
			$this->_data = $this->fetchData ();
		return $this->_data;
	}
	
	public function setData($value) {
		$this->_data = $value;
	}
	
	public function getKeys($refresh = false) {
		if ($this->_keys === null || $refresh)
			$this->_keys = $this->fetchKeys ();
		return $this->_keys;
	}
	
	public function setKeys($value) {
		$this->_keys = $value;
	}
	
	public function getItemCount($refresh = false) {
		return count ( $this->getData ( $refresh ) );
	}
	
	public function getTotalItemCount($refresh = false) {
		if ($this->_totalItemCount === null || $refresh)
			$this->_totalItemCount = $this->calculateTotalItemCount ();
		return $this->_totalItemCount;
	}

	public function setTotalItemCount($value) {
		$this->_totalItemCount = $value;
	}

}

?>