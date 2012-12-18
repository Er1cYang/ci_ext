<?php
namespace ci_ext\utils;
class ArrayList extends \ci_ext\core\Object implements \ArrayAccess, \Countable, \IteratorAggregate {
	
	private $_d = array();
	
	public function add($element) {
		array_push($this->_d, $element);
	}
	
	public function push($element) {
		array_push($this->_d, $element);
	}
	
	public function pop() {
		return array_pop($this->_d);
	}
	
	public function unshift($element) {
		array_unshift($this->_d, $element);
	}
	
	public function shift() {
		return array_shift($this->_d);
	}
	
	public function getLength() {
		return count($this->_d);
	}
	
	public function getIterator() {
		return new \ArrayObject($this->_d);		
	}

	public function offsetExists($offset) {
		$offset = intval($offset);
		return isset($this->_d[$offset]);
	}

	public function offsetGet($offset) {
		$offset = intval($offset);
		return isset($this->_d[$offset]) ? $this->_d[$offset] : null;
	}

	public function offsetSet($offset, $value) {
		$this->_d[intval($offset)] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->_d[intval($offset)]);
	}

	public function count() {
		return count($this->_d);
	}

	public function sort() {
		sort($this->_d);
	}
	
	public function usort($cmpFunction) {
		usort($this->_d, $cmpFunction);
	}
	
	public function shuffle() {
		shuffle($this->_d);
	}
	
	public function concat($data) {
		foreach($data as $v) {
			$this->push($v);
		}
		return $this;
	}
	
	public function contain($element) {
		return in_array($element, $this->_d);
	}
	
	public function indexOf($index) {
		return $this->offsetGet($index);
	}
	
	public function lastIndexOf($index) {
		// TODO ArrayList::lastIndexOf
	}
	
	public function reverse() {
		array_reverse($this->_d);
		return $this;
	}
	
	public function slice($offset, $length = 1) {
		return array_slice($this->_d, $offset, $length);
	}
	
	public function splice($offset, $length = 1) {
		return array_splice($this->_d, $offset, $length);
	}
	
	public function toArray() {
		return $this->_d;
	}
	
}

?>