<?php
namespace ci_ext\validators;
abstract class Validator extends \ci_ext\core\Object {
	
	public $attributes;
	public $message;
	public $skipOnError = false;
	public $on;
	public $except;
	public $safe = true;
	public $enableClientValidation = true;
	
	abstract protected function validateAttribute($object, $attribute);
	
	public static function createValidator($name, $object, $attributes, $params = array()) {
		if (is_string ( $attributes ))
			$attributes = preg_split ( '/[\s,]+/', $attributes, - 1, PREG_SPLIT_NO_EMPTY );
		
		if (isset ( $params ['on'] )) {
			if (is_array ( $params ['on'] ))
				$on = $params ['on'];
			else
				$on = preg_split ( '/[\s,]+/', $params ['on'], - 1, PREG_SPLIT_NO_EMPTY );
		} else
			$on = array ();
		
		if (isset ( $params ['except'] )) {
			if (is_array ( $params ['except'] ))
				$except = $params ['except'];
			else
				$except = preg_split ( '/[\s,]+/', $params ['except'], - 1, PREG_SPLIT_NO_EMPTY );
		} else
			$except = array ();
		
		if (method_exists ( $object, $name )) {
			$validator = new InlineValidator ();
			$validator->attributes = $attributes;
			$validator->method = $name;
			if (isset ( $params ['clientValidate'] )) {
				$validator->clientValidate = $params ['clientValidate'];
				unset ( $params ['clientValidate'] );
			}
			$validator->params = $params;
			if (isset ( $params ['skipOnError'] ))
				$validator->skipOnError = $params ['skipOnError'];
		} else {
			$params ['attributes'] = $attributes;
			$validator = new ValidatorAdapter();
			foreach ( $params as $name => $value )
				$validator->$name = $value;
		}
		
		$validator->on = empty ( $on ) ? array () : array_combine ( $on, $on );
		$validator->except = empty ( $except ) ? array () : array_combine ( $except, $except );
		
		return $validator;
	}
	
	public function validate($object, $attributes = null) {
		if (is_array ( $attributes ))
			$attributes = array_intersect ( $this->attributes, $attributes );
		else
			$attributes = $this->attributes;
		foreach ( $attributes as $attribute ) {
			if (! $this->skipOnError || ! $object->hasErrors ( $attribute ))
				$this->validateAttribute ( $object, $attribute );
		}
	}
	
	public function clientValidateAttribute($object, $attribute) {
	}
	
	public function applyTo($scenario) {
		if (isset ( $this->except [$scenario] ))
			return false;
		return empty ( $this->on ) || isset ( $this->on [$scenario] );
	}
	
	protected function addError($object, $attribute, $message, $params = array()) {
		$params ['{attribute}'] = $object->getAttributeLabel ( $attribute );
		$object->addError ( $attribute, strtr ( $message, $params ) );
	}
	
	protected function isEmpty($value, $trim = false) {
		return $value === null || $value === array () || $value === '' || $trim && is_scalar ( $value ) && trim ( $value ) === '';
	}

}

?>