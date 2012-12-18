<?php
namespace ci_ext\core;
interface IBehavior {
	/**
	 * 装载behavior
	 * @param EventDispatcher $target
	 * @return void
	 */
	public function attach($target);
	/**
	 * 卸载behavior
	 * @param EventDispatcher $target
	 * @return void
	 */
	public function detach($target);
	/**
	 * 获取该behavior的激活状态
	 * @return boolean
	 */
	public function getEnabled();
	/**
	 * 设置该behavior的激活状态
	 * @param boolean $value
	 * @return void
	 */
	public function setEnabled($value);
}

?>