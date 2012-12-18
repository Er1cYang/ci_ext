<?php
/**
 * CI_Ext
 * <pre>
 * 基于CodeIgniter框架的扩展代码库
 * </pre>
 * ==============================================
 * File encoding: UTF-8 
 * ----------------------------------------------
 * CodeIgniter_Extension.php
 * ==============================================
 * @author YangDongqi <yangdongqi@gmail.com>
 * @copyright Copyright &copy; 2006-2012 Hayzone IT LTD.
 * @version $id$
 */
class CI_Ext {
	
	private static $_tData;
	
	/**
	 * 返回项目编码
	 * Enter description here ...
	 */
	public static function charset() {
		return 'utf-8';
	}
	
	/**
	 * 返回CI的控制器实例
	 * @return CI_Controller
	 */
	public static function ci() {
		return get_instance();
	}
	
	/**
	 * 初始化扩展
	 * @return void
	 */
	public static function setup() {
		defined('IS_DEV_MODE') or define('IS_DEV_MODE', in_array($_SERVER['SERVER_ADDR'], array('127.0.0.1', '::1')) ? 'local' : 'remote');
		spl_autoload_register(array('CI_Ext', 'autoload'));
	}
	
	/**
	 * autoload实现
	 * @param string $className
	 * @return void;
	 */
	public static function autoload($className) {
		if(substr($className, 0, 6) == 'ci_ext') {
			$filename = dirname(__FILE__)."/".str_replace('\\', '/', substr($className, 6)).'.php';
			include $filename;
		}
	}
	
	/**
	 * 创建对象
	 * @param array $config
	 * @param array $constructorArgs
	 */
	public static function createObject($config, $constructorArgs = array()) {
		$className = $config['class'];
		if(isset(self::$_coreClasses[$className])) {
			$className = self::$_coreClasses[$className];
		}
		$rc = new ReflectionClass($className);
		$object = null;
		if($rc->hasMethod('__construct')) {
			$object = $rc->newInstanceArgs($constructorArgs);
		} else {
			$object = $rc->newInstance();
		}
		unset($config['class']);
		foreach($config as $k=>$v) {
			$object->$k = $v;
		}
		return $object;
	}
	
	/**
	 * 转换字符串占位符
	 * @category string
	 * @param string $message
	 * @param array $params
	 * @return string
	 */
	public static function t($category, $message, $params=array()) {
		if(!self::$_tData) {
			self::$_tData = include dirname(__FILE__).'/message/'.$category.'.php';
		}
		if(isset(self::$_tData[$message])) {
			$message = self::$_tData[$message];
		}
		return str_replace(array_keys($params), array_values($params), $message);
	}
	
	
	private static $_coreClasses = array(
		'DataColumn' => 'ci_ext\web\widgets\gridview\DataColumn',
		'ButtonColumn' => 'ci_ext\web\widgets\gridview\ButtonColumn',
		'CheckBoxColumn' => 'ci_ext\web\widgets\gridview\CheckBoxColumn',
		'TableForm' => 'ci_ext\web\widgets\TableForm',
		'GridView' => 'ci_ext\web\widgets\gridview\GridView',
		'LinkPager' => 'ci_ext\web\widgets\pagers\LinkPager',
		'TableDataProvider' => 'ci_ext\web\TableDataProvider',
		'Sort' => 'ci_ext\web\Sort',
		'Pagination' => 'ci_ext\web\Pagination',
	);
	
}
CI_Ext::setup();
?>