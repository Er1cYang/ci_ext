<?php
namespace ci_ext\web\widgets\pagers;
use ci_ext\web\Pagination;

abstract class BasePager extends \ci_ext\web\Widget {
	private $_pages;
	
	public function getPages() {
		if ($this->_pages === null)
			$this->_pages = $this->createPages ();
		return $this->_pages;
	}
	
	public function setPages($pages) {
		$this->_pages = $pages;
	}
	
	protected function createPages() {
		return new Pagination ();
	}
	
	public function getPageSize() {
		return $this->getPages ()->getPageSize ();
	}
	
	public function setPageSize($value) {
		$this->getPages ()->setPageSize ( $value );
	}
	
	public function getItemCount() {
		return $this->getPages ()->getItemCount ();
	}
	
	public function setItemCount($value) {
		$this->getPages ()->setItemCount ( $value );
	}
	
	public function getPageCount() {
		return $this->getPages ()->getPageCount ();
	}
	
	public function getCurrentPage($recalculate = true) {
		return $this->getPages ()->getCurrentPage ( $recalculate );
	}
	
	public function setCurrentPage($value) {
		$this->getPages ()->setCurrentPage ( $value );
	}
	
	protected function createPageUrl($page) {
		return $this->getPages ()->createPageUrl ( get_instance(), $page );
	}
}
