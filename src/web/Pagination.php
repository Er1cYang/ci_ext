<?php
namespace ci_ext\web;

class Pagination extends \ci_ext\core\Object {
	const DEFAULT_PAGE_VAR = 'page';
	const DEFAULT_PAGE_SIZE = 1;
	public $pageSize = self::DEFAULT_PAGE_SIZE;
	public $pageVar = self::DEFAULT_PAGE_VAR;
	
	public $totalItemCount;
	
	public function __construct($totalItemCount=0) {
		$this->totalItemCount = $totalItemCount;
	}
	
	public function getCurrentPage() {
		return isset($_GET[$this->pageVar]) ? $_GET[$this->pageVar] : 1; 
	}
	
}

?>