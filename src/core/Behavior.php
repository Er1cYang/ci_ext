<?php
namespace ci_ext\core;

class Behavior extends \ci_ext\core\Object implements IBehavior {
	
	/**
	 * 当前激活状态
	 * @var boolean
	 */
	private $_enabled;
	/**
	 * 所有者
	 * @var EventDispatcher
	 */
	private $_owner;
	
	/**
	 * behavior拥有的事件定义
	 * <pre>
	 * 在behavior你可以定义诸多事件，例如Event::AFTER_CONSTRUCT
	 * 在behavior被装载或被激活时，这些事件也被添加到侦听列表中。
	 * 不过事件的侦听器必须是本类中的某非静态方法，这个方法结构通常
	 * 像下面这样定义：
	 * </pre>
	 * <code>
	 * public function events() {
	 *     return array(
	 *         'eventName1' => 'listenerName1',
	 *         'eventName2' => 'listenerName2',
	 *         'eventName3' => 'listenerName3',
	 *     );
	 * }
	 * </code>
	 * @return array
	 */
	public function events() {
		return array();
	}
	
	/**
	 * @see ci_ext\core.IBehavior::attach()
	 */
	public function attach($owner) {
		$this->_owner=$owner;
		foreach($this->events() as $event=>$handler)
			$owner->addEventListener($event,array($this,$handler));
	}
	
	/**
	 * @see ci_ext\core.IBehavior::detach()
	 */
	public function detach($owner) {
		foreach($this->events() as $event=>$handler)
			$owner->removeEventListener($event,array($this,$handler));
		$this->_owner=null;
	}
	
	/**
	 * 获取所有者
	 * <pre>
	 * 这个behavior被装载到那个实例上
	 * </pre>
	 * @return EventDispatcher
	 */
	public function getOwner() {
		return $this->_owner;
	}
	
	/**
	 * @see ci_ext\core.IBehavior::getEnabled()
	 */
	public function getEnabled() {
		return $this->_enabled;
	}
	
	/**
	 * @see ci_ext\core.IBehavior::setEnabled()
	 */
	public function setEnabled($value) {
		if($this->_enabled!=$value && $this->_owner) {
			if($value) {
				foreach($this->events() as $event=>$handler) {
					$this->_owner->addEventListener($event,array($this,$handler));
				}
			} else {
				foreach($this->events() as $event=>$handler) {
					$this->_owner->removeEventListener($event,array($this,$handler));
				}
			}
		}
		$this->_enabled=$value;
	}
}

?>