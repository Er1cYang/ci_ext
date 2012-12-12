<?php
namespace ci_ext\core;
use ci_ext\core\Exception;
/**
 * Object
 * ==============================================
 * File encoding: UTF-8 
 * ----------------------------------------------
 * Object.php
 * ==============================================
 * @author YangDongqi <yangdongqi@gmail.com>
 * @copyright Copyright &copy; 2006-2012 Hayzone IT LTD.
 * @version $id$
 */
class Object {
	
	/**
	 * Getter 方法实现
	 * @param string $name
	 * @throws Exception
	 * @return mixed
	 */
	public function __get($name) {
	    $getter='get'.$name;
	    if(method_exists($this,$getter)) {
	    	return $this->$getter();
	    }
	    $class = get_class($this);
	    throw new Exception('Property "'.$class.'.'.$name.'" is not defined');
	}
	
	/**
	 * Setter 方法实现
	 * @param string $name
	 * @param mixed $value
	 * @throws Exception
	 * @return void
	 */
	public function __set($name,$value) {
    	$setter='set'.$name;
	    if(method_exists($this,$setter)) {
	        return $this->$setter($value);
	    }
	    $class = get_class($this);
	    if(method_exists($this,'get'.$name)) {
	    	throw new Exception("Property \"{$class}.{$name}\" is read only.");
	    } else {
	       throw new Exception("Property \"{$class}.{$name}\" is not defined.");
	    }
	} 
	
	/**
	 * 卸载某个属性
	 * <pre>
	 * 将某个属性设置为null，如果该属性只读，则抛出异常
	 * </pre>
	 * @param string $name
	 * @throws Exception
	 * @return void
	 */
	public function __unset($name) {
		$setter='set'.$name;
		if(method_exists($this,$setter)) {
			$this->$setter(null);
		} else if(method_exists($this,'get'.$name)) {
			$class = get_class($this);
			throw new Exception("Property \"{$class}.{$name}\" is read only.");
		}
	}
	
	/**
	 * 是否存在某属性
	 * @param string $name
	 * @return boolean
	 */
	public function __isset($name) {
	    $getter='get'.$name;
	    if(method_exists($this,$getter))
	        return $this->$getter()!==null;
	    return false;
	}
	
	/**
	 * 是否存在某属性
	 * @param string $name
	 * @return boolean
	 */
	public function hasProperty($name) {
		return method_exists($this,'get'.$name) || method_exists($this,'set'.$name);
	}
	
	/**
	 * 属性是否可读
	 * @param string $name
	 * @return boolean
	 */
	public function canGetProperty($name) {
		return method_exists($this,'get'.$name);
	}
	
	/**
	 * 属性是否可写
	 * @param string $name
	 * @return boolean
	 */
	public function canSetProperty($name) {
		return method_exists($this,'set'.$name);
	}
}

?>