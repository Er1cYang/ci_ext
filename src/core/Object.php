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
	
	public function __get($name) {
	    $getter='get'.$name;
	    if(method_exists($this,$getter)) {
	    	return $this->$getter();
	    }
	    $class = get_class($this);
	    throw new Exception('Property "'.$class.'.'.$name.'" is not defined');
	}
	
	public function __set($name,$value) {
    	$setter='set'.$name;
	    if(method_exists($this,$setter)) {
	        return $this->$setter($value);
	    }
	    $class = get_class($this);
	    if(method_exists($this,'get'.$name)) {
	    	throw new Exception("Property \"{$class}.{$name}\" is read only.");
	    } else {
	       throw new Exception('Property "'.$class.'.'.$name.'" is not defined');
	    }
	} 
	
	public function __isset($name) {
	    $getter='get'.$name;
	    if(method_exists($this,$getter))
	        return $this->$getter()!==null;
	    return false;
	} 
	
}

?>