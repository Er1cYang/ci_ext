<?php
namespace ci_ext\web;
class Widget extends \ci_ext\core\Object {
	
	private $_id;
	private static $_counter = 0;
	
	public function init() {
	}
	
	public function run() {
	}
	
	public function getId($autoGenerate = true) {
		if ($this->_id !== null)
			return $this->_id;
		else if ($autoGenerate)
			return $this->_id = 'cw' . self::$_counter ++;
	}
	
	public function setId($value) {
		$this->_id = $value;
	}

}