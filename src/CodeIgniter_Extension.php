<?php
/**
 * CodeIgniter_Extension
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
class CodeIgniter_Extension {
	
	/**
	 * 初始化扩展
	 * @return void
	 */
	public static function setup() {
		spl_autoload_register(array('CodeIgniter_Extension', 'autoload'));
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
	
}

?>