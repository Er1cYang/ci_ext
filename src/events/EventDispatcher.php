<?php

namespace ci_ext\events;
use ci_ext\utils\ArrayList;
/**
 * EventDispatcher
 * ==============================================
 * File encoding: UTF-8 
 * ----------------------------------------------
 * EventDispatcher.php
 * ==============================================
 * @author YangDongqi <yangdongqi@gmail.com>
 * @copyright Copyright &copy; 2006-2012 Hayzone IT LTD.
 * @version $id$
 */
class EventDispatcher extends \ci_ext\core\Object {
	
	/**
	 * @var array
	 */
	private $_e = array();	
	
	/**
	 * @param string $type
	 * @param function $listener
	 * @return void
	 */
	public function addEventListener($type, $listener, $priority=0) {
		if(!isset($this->_e[$type])) {
			$this->_e[$type] = new ArrayList();
		}
		$this->_e[$type]->unshift(array($priority, $listener));
		$this->sortEventListeners($this->_e[$type]);
	}
	
	/**
	 * 
	 * @param ArrayList $eventListeners
	 * @return void
	 */
	private function sortEventListeners(ArrayList $eventListeners) {
		$eventListeners->usort(function($v1, $v2) {
			if($v1[0]==$v2[0]) {
				return 0;
			} else {
				return ($v1[0] < $v2[0]) ? 1 : -1;
			}
		});
	}
	
	/**
	 * @param string $type
	 * @return boolean
	 */
	public function hasEventListener($type) {
		return isset($this->_e[$type]) && $this->_e[$type]->getLength() != 0;
	}
	
	/**
	 * @param string $type
	 * @param function $listener
	 * @return void
	 */
	public function removeEventListener($type, $listener) {
		if(isset($this->_e[$type])) {
			foreach($this->_e[$type] as $k=>$v) {
				if($v[1] == $listener) {
					unset($this->_e[$type][$k]);
				}
			}
		}
	}
	
	/**
	 * @param string $type
	 * @return boolean
	 */
	public function willTrigger($type) {
		return true;
	}
	
	/**
	 * @param String $type
	 * @param Event $event
	 * @return boolean
	 */
	public function dispatchEvent($type, Event $event) {
		$event->target = $this;
		if(!isset($this->_e[$type])) {
			return;
		}
		foreach($this->_e[$type] as $v) {
			list($priority, $listener) = $v;
			call_user_func($listener, $event);
		}
	}

}

?>