<?php
namespace ci_ext\web;

use ci_ext\db\Table;
use ci_ext\db\DbCriteria;

class TableDataProvider extends DataProvider {
	
	public $modelClass;
	public $model;
	public $keyAttribute;
	
	private $_criteria;
	
	public function __construct($modelClass, $config = array()) {
		if (is_string ( $modelClass )) {
			$this->modelClass = $modelClass;
			$this->model = Table::model ( $this->modelClass );
		} else if ($modelClass instanceof Table) {
			$this->modelClass = get_class ( $modelClass );
			$this->model = $modelClass;
		}
		$this->setId ( $this->modelClass );
		foreach ( $config as $key => $value )
			$this->$key = $value;
	}
	
	public function getCriteria() {
		if ($this->_criteria === null)
			$this->_criteria = new DbCriteria ();
		return $this->_criteria;
	}
	
	public function setCriteria($value) {
		$this->_criteria = $value instanceof DbCriteria ? $value : new DbCriteria ( $value );
	}
	
	public function getSort() {
		if (($sort = parent::getSort ()) !== false)
			$sort->modelClass = $this->modelClass;
		return $sort;
	}
	
	protected function fetchData() {
		$criteria = clone $this->getCriteria ();
		
		if (($pagination = $this->getPagination ()) !== false) {
			$pagination->setItemCount ( $this->getTotalItemCount () );
			$pagination->applyLimit ( $criteria );
		}
		
		$baseCriteria = $this->model->getDbCriteria ( false );
		
		if (($sort = $this->getSort ()) !== false) {
			if ($baseCriteria !== null) {
				$c = clone $baseCriteria;
				$c->mergeWith ( $criteria );
				$this->model->setDbCriteria ( $c );
			} else
				$this->model->setDbCriteria ( $criteria );
			$sort->applyOrder ( $criteria );
		}
		
		$this->model->setDbCriteria ( $baseCriteria !== null ? clone $baseCriteria : null );
		$data = $this->model->findAll ( $criteria );
		$this->model->setDbCriteria ( $baseCriteria ); // restore original criteria
		return $data;
	}
	
	protected function fetchKeys() {
		$keys = array ();
		foreach ( $this->getData () as $i => $data ) {
			$key = $this->keyAttribute === null ? $data->getPrimaryKey () : $data->{$this->keyAttribute};
			$keys [$i] = is_array ( $key ) ? implode ( ',', $key ) : $key;
		}
		return $keys;
	}
	
	protected function calculateTotalItemCount() {
		$baseCriteria = $this->model->getDbCriteria ( false );
		if ($baseCriteria !== null)
			$baseCriteria = clone $baseCriteria;
		$count = $this->model->count ( $this->getCriteria () );
		$this->model->setDbCriteria ( $baseCriteria );
		return $count;
	}

}

?>