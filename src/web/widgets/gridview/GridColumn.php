<?php
namespace ci_ext\web\widgets\gridview;
class GridColumn extends \ci_ext\core\Object {
	
	private $_name;
	private $_grid;
	
	public function __construct($name, $grid) {
		$this->_name = $name;
		$this->_grid = $grid;
	}
	
	public function getName() {
		return $this->_name;
	}
	
	public function getGrid() {
		return $this->_grid;
	}
	
	public function renderHeader() {
		return $this->_name;
	}
	public function renderBody($row) {
	}
	public function renderFooter() {
	}
	
}

?>