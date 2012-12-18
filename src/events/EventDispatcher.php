<?php

namespace ci_ext\events;
use ci_ext\utils\VarDumper;

use ci_ext\utils\ArrayList;
use ci_ext\core\IBehavior;
use ci_ext\core\Exception;
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
	 * @var array
	 */
	private $_m = array();
	
	
	/**
	 * 绑定多个IBehavior实例
	 * @see attachBehavior
	 * @param array $behaviors
	 * @return void
	 */
	public function attachBehaviors($behaviors) {
		foreach($behaviors as $name=>$behavior)
			$this->attachBehavior($name,$behavior);
	}
	
	/**
	 * 解绑多个IBehavior实例
	 * @return void
	 */
	public function detachBehaviors() {
		if($this->_m!==null) {
			foreach($this->_m as $name=>$behavior) {
				$this->detachBehavior($name);
			}
			$this->_m=null;
		}
	}
	
	/**
	 * 绑定一个IBehavior实例
	 * @param string $name
	 * @param IBehavior $behavior
	 * @return IBehavior
	 */
	public function attachBehavior($name,$behavior) {
		if(!($behavior instanceof IBehavior)) {
			$className = $behavior['class'];
			$config = $behavior;
			unset($config['class']);
			$behavior = new $className();
			foreach($config as $k=>$v) {
				$behavior->$k = $v;
			}
		}
		$behavior->setEnabled(true);
		$behavior->attach($this);
		return $this->_m[$name]=$behavior;
	}
	
	/**
	 * 解绑一个IBehavior实例
	 * <pre>
	 * 根据指定的名字
	 * 返回被解绑的IBehavior实例
	 * </pre>
	 * @param string $name
	 * @return IBehavior
	 */
	public function detachBehavior($name) {
		if(isset($this->_m[$name])) {
			$this->_m[$name]->detach($this);
			$behavior=$this->_m[$name];
			unset($this->_m[$name]);
			return $behavior;
		}
	}
	
	/**
	 * 激活所有Behavior
	 * @return void
	 */
	public function enableBehaviors() {
		if($this->_m!==null) {
			foreach($this->_m as $behavior) {
				$behavior->setEnabled(true);
			}
		}
	}
	
	/**
	 * 禁用所有Behavior
	 * @return void
	 */
	public function disableBehaviors() {
		if($this->_m!==null) {
			foreach($this->_m as $behavior) {
				$behavior->setEnabled(false);
			}
		}
	}
	
	/**
	 * 激活某个behavior
	 * @param string $name
	 * @return void
	 */
	public function enableBehavior($name) {
		if(isset($this->_m[$name])) {
			$this->_m[$name]->setEnabled(true);
		}
	}
	
	/**
	 * 禁用某个behavior
	 * @param string $name
	 * @return void
	 */
	public function disableBehavior($name) {
		if(isset($this->_m[$name])) {
			$this->_m[$name]->setEnabled(false);
		}
	}
	
	/**
	 * 按名称返回某个behavior
	 * <pre>
	 * 如果该behavior不存在，则返回null
	 * </pre>
	 * @param string $behavior
	 * @return mixed
	 */
	public function asa($behavior) {
		return isset($this->_m[$behavior]) ? $this->_m[$behavior] : null;
	}
	
	/**
	 * 增加事件监听器
	 * @param string $type
	 * @param function $listener
	 * @return void
	 */
	public function addEventListener($type, $listener, $priority=0) {
		if(!isset($this->_e[$type])) {
			$this->_e[$type] = new ArrayList();
		}
		
		if(!$this->isEventListenerExists($type, $listener)) {
			$this->_e[$type]->unshift(array($priority, $listener));
			$this->sortEventListeners($this->_e[$type]);
		}
		
	}
	
	/**
	 * 检测某个侦听器是否存在与某个事件下
	 * @param string $type
	 * @param mixed $listener
	 * @return boolean
	 */
	public function isEventListenerExists($type, $listener) {
		if(!isset($this->_e[$type])) {
			return false;
		}
		foreach($this->_e[$type] as $listenerStruct) {
			list($priority, $exists) = $listenerStruct;
			if($listener == $exists)
				return true;
		}
		return false;
	}
	
	/**
	 * 时间侦听器按优先级排序
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
	 * 是否包含某种事件的侦听器
	 * @param string $type
	 * @return boolean
	 */
	public function hasEventListener($type) {
		return isset($this->_e[$type]) && $this->_e[$type]->getLength() != 0;
	}
	
	/**
	 * 删除某个事件侦听器
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
	 * 自动调用behavior的方法
	 * <pre>
	 * 如果当前类的某个方法不存在，则遍历它所装载的behaviors，如果某个behavior拥有
	 * 该方法，则调用该behavior的该方法，调用后立即返回。
	 * 如果behaviors中没有符合的behavior则检测当前实例对应名称是否是一个闭包（匿名）函数
	 * 如果是，则调用它。
	 * 如果以上条件都不满足，则抛出异常
	 * </pre>
	 * @param unknown_type $name
	 * @param unknown_type $parameters
	 * @throws Exception
	 */
	public function __call($name,$parameters) {
		if($this->_m!==null) {
			foreach($this->_m as $object) {
				if($object->getEnabled() && method_exists($object,$name)) {
					return call_user_func_array(array($object,$name),$parameters);
				}
			}
		}
		if(class_exists('\Closure', false) && $this->canGetProperty($name) && $this->$name instanceof \Closure) {
			return call_user_func_array($this->$name, $parameters);
		}
		$class = get_class($this);
		throw new Exception("{$class} and its behaviors do not have a method or closure named \"{$name}\".");
	}
	
	/**
	 * 派发事件
	 * @param String $type
	 * @param Event $event
	 * @return boolean
	 */
	public function dispatchEvent($type, Event $event) {
		if($type == 'afterSave') {
			//echo '走';
			//VarDumper::dump($this->_e[$type], 5, true);
		}
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