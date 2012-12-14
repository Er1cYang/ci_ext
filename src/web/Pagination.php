<?php
namespace ci_ext\web;

class Pagination extends \ci_ext\core\Object {
	
	const DEFAULT_PAGE_SIZE = 10;
	public $pageVar = 'page';
	public $route = '';
	public $params;
	public $validateCurrentPage = true;
	
	private $_pageSize = self::DEFAULT_PAGE_SIZE;
	private $_itemCount = 0;
	private $_currentPage;

	public function __construct($itemCount = 0) {
		$this->setItemCount ( $itemCount );
	}
	
	public function getPageSize() {
		return $this->_pageSize;
	}
	
	public function setPageSize($value) {
		if (($this->_pageSize = $value) <= 0)
			$this->_pageSize = self::DEFAULT_PAGE_SIZE;
	}
	
	public function getItemCount() {
		return $this->_itemCount;
	}
	
	public function setItemCount($value) {
		if (($this->_itemCount = $value) < 0)
			$this->_itemCount = 0;
	}

	public function getPageCount() {
		return ( int ) (($this->_itemCount + $this->_pageSize - 1) / $this->_pageSize);
	}
	
	public function getCurrentPage($recalculate = true) {
		if ($this->_currentPage === null || $recalculate) {
			if (isset ( $_GET [$this->pageVar] )) {
				$this->_currentPage = ( int ) $_GET [$this->pageVar] - 1;
				if ($this->validateCurrentPage) {
					$pageCount = $this->getPageCount ();
					if ($this->_currentPage >= $pageCount)
						$this->_currentPage = $pageCount - 1;
				}
				if ($this->_currentPage < 0)
					$this->_currentPage = 0;
			} else
				$this->_currentPage = 0;
		}
		return $this->_currentPage;
	}
	
	public function setCurrentPage($value) {
		$this->_currentPage = $value;
		$_GET [$this->pageVar] = $value + 1;
	}
	
	public function createPageUrl($controller, $page) {
		$params = $this->params === null ? $_GET : $this->params;
		if ($page > 0) // page 0 is the default
			$params [$this->pageVar] = $page + 1;
		else
			unset ( $params [$this->pageVar] );
		
		return $controller->createUrl ( $this->route, $params );
	}
	
	public function applyLimit($criteria) {
		$criteria->limit = $this->getLimit ();
		$criteria->offset = $this->getOffset ();
	}
	
	public function getOffset() {
		return $this->getCurrentPage () * $this->getPageSize ();
	}
	
	public function getLimit() {
		return $this->getPageSize ();
	}
}

?>