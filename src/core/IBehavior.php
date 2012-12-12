<?php
namespace ci_ext\core;
/**
 * IBehavior
 * ==============================================
 * File encoding: UTF-8 
 * ----------------------------------------------
 * IBehavior.php
 * ==============================================
 * @author YangDongqi <yangdongqi@gmail.com>
 * @copyright Copyright &copy; 2006-2012 Hayzone IT LTD.
 * @version $id$
 */
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