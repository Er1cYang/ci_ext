<?php
namespace ci_ext\core;

use ci_ext\core\Exception;
use ci_ext\events\Event;
use ci_ext\utils\ArrayList;
use ci_ext\validators\Validator;
/**
 * Model
 * ==============================================
 * File encoding: UTF-8 
 * ----------------------------------------------
 * Model.php
 * ==============================================
 * @author YangDongqi <yangdongqi@gmail.com>
 * @copyright Copyright &copy; 2006-2012 Hayzone IT LTD.
 * @version $id$
 */
abstract class Model extends \ci_ext\events\EventDispatcher implements \IteratorAggregate, \ArrayAccess {
	
	private $_errors = array ();
	private $_validators;
	private $_scenario = '';
	
	/**
	 * 验证规则
	 * @return array
	 */
	public function rules() {
		return array ();
	}
	
	/**
	 * 预装载的behaviors
	 * @return array
	 */
	public function behaviors() {
		return array ();
	}
	
	/**
	 * 模型静态属性名
	 * @return array
	 */
	abstract public function attributeNames();
	
	/**
	 * 验证
	 * @param array $attributes
	 * @param boolean $clearErrors
	 * @return boolean
	 */
	public function validate($attributes = null, $clearErrors = true) {
		if ($clearErrors) {
			$this->clearErrors ();
		}
		if ($this->beforeValidate ()) {
			foreach ( $this->getValidators () as $validator ) {
				$validator->validate ( $this, $attributes );
			}
			$this->afterValidate ();
			return ! $this->hasErrors ();
		} else {
			return false;
		}
	}
	
	/**
	 * 触发事件afterConstruct
	 * @return void
	 */
	protected function afterConstruct() {
		$this->dispatchEvent ( Event::AFTER_CONSTRUCT, new Event ( $this ) );
	}
	
	/**
	 * 触发事件beforeValidate
	 * @return boolean
	 */
	protected function beforeValidate() {
		if ($this->hasEventListener ( ModelEvent::BEFORE_VALIDATE )) {
			$event = new ModelEvent ( $this );
			$this->dispatchEvent ( ModelEvent::BEFORE_VALIDATE, $event );
			return $event->isValid;
		} else {
			return true;
		}
	}
	
	/**
	 * 触发事件afterValidate
	 * @return void
	 */
	protected function afterValidate() {
		$this->dispatchEvent ( ModelEvent::AFTER_VALIDATE, new Event ( $this ) );
	}

	/**
	 * 获取验证器集合
	 * @return array
	 */
	public function getValidatorList() {
		if ($this->_validators === null)
			$this->_validators = $this->createValidators ();
		return $this->_validators;
	}
	
	/**
	 * 清除错误
	 * @param mixed $attribute
	 * @return void
	 */
	public function clearErrors($attribute = null) {
		if ($attribute === null) {
			$this->_errors = array ();
		} else {
			unset ( $this->_errors [$attribute] );
		}
	}
	
	/**
	 * 是否包含某字段的错误
	 * @param string $attribute
	 * @return boolean
	 */
	public function hasErrors($attribute = null) {
		if ($attribute === null)
			return $this->_errors !== array ();
		else
			return isset ( $this->_errors [$attribute] );
	}
	
	/**
	 * 获取错误信息
	 * @param array $attribute
	 * @return array
	 */
	public function getErrors($attribute = null) {
		if ($attribute === null)
			return $this->_errors;
		else
			return isset ( $this->_errors [$attribute] ) ? $this->_errors [$attribute] : array ();
	}
	
	/**
	 * 获取错误信息
	 * @param array $attribute
	 * @return array
	 */
	public function getError($attribute) {
		return isset ( $this->_errors [$attribute] ) ? reset ( $this->_errors [$attribute] ) : null;
	}
	
	/**
	 * 添加某属性的错误
	 * @param string $attribute
	 * @param string $error
	 * @return void
	 */
	public function addError($attribute, $error) {
		$this->_errors [$attribute] [] = $error;
	}
	
	/**
	 * 添加某属性的错误
	 * @param array $errors
	 * @return void
	 */
	public function addErrors($errors) {
		foreach ( $errors as $attribute => $error ) {
			if (is_array ( $error )) {
				foreach ( $error as $e ) {
					$this->addError ( $attribute, $e );
				}
			} else {
				$this->addError ( $attribute, $error );
			}
		}
	}
	
	/**
	 * 获取某属性的标签
	 * @param string $attribute
	 * @return array
	 */
	public function getAttributeLabel($attribute) {
		$labels = $this->attributeLabels ();
		if (isset ( $labels [$attribute] )) {
			return $labels [$attribute];
		} else {
			return $this->generateAttributeLabel ( $attribute );
		}
	}
	
	/**
	 * 创建人性化的标签
	 * @param string $name
	 * @return string
	 */
	public function generateAttributeLabel($name) {
		return ucwords ( trim ( strtolower ( str_replace ( array ('-', '_', '.' ), ' ', preg_replace ( '/(?<![A-Z])[A-Z]/', ' \0', $name ) ) ) ) );
	}
	
	/**
	 * 创建验证器
	 * @throws CException
	 * @return ArrayList
	 */
	public function createValidators() {
		$validators = new ArrayList ();
		foreach ( $this->rules () as $rule ) {
			if (isset ( $rule [0], $rule [1] )) {
				$validators->add ( Validator::createValidator ( $rule [1], $this, $rule [0], array_slice ( $rule, 2 ) ) );
			} else {
				$class = get_class ( $this );
				throw new Exception ( "{$class} has an invalid validation rule. The rule must specify attributes to be validated and the validator name." );
			}
		}
		return $validators;
	}
	
	/**
	 * 获取验证器
	 * @param unknown_type $attribute
	 */
	public function getValidators($attribute = null) {
		if ($this->_validators === null) {
			$this->_validators = $this->createValidators ();
		}
		$validators = array ();
		$scenario = $this->getScenario ();
		foreach ( $this->_validators as $validator ) {
			if ($validator->applyTo ( $scenario )) {
				if ($attribute === null || in_array ( $attribute, $validator->attributes, true )) {
					$validators [] = $validator;
				}
			}
		}
		return $validators;
	}
	
	/**
	 * 检查属性是否必填
	 * @param string $attribute
	 * @return boolean
	 */
	public function isAttributeRequired($attribute) {
		foreach ( $this->getValidators ( $attribute ) as $validator ) {
			if ($validator instanceof \ci_ext\validators\RequiredValidator)
				return true;
		}
		return false;
	}
	
	/**
	 * 检查属性是否安全
	 * @param string $attribute
	 * @return boolean
	 */
	public function isAttributeSafe($attribute) {
		$attributes = $this->getSafeAttributeNames ();
		return in_array ( $attribute, $attributes );
	}
	
	/**
	 * 获取属性
	 * @param array $names
	 * @return array
	 */
	public function getAttributes($names = null) {
		$values = array ();
		foreach ( $this->attributeNames () as $name )
			$values [$name] = $this->$name;
		
		if (is_array ( $names )) {
			$values2 = array ();
			foreach ( $names as $name )
				$values2 [$name] = isset ( $values [$name] ) ? $values [$name] : null;
			return $values2;
		} else
			return $values;
	}
	
	/**
	 * 设置属性
	 * @param array $values
	 * @param boolean $safeOnly
	 */
	public function setAttributes($values, $safeOnly = true) {
		if (! is_array ( $values ))
			return;
		$attributes = array_flip ( $safeOnly ? $this->getSafeAttributeNames () : $this->attributeNames () );
		foreach ( $values as $name => $value ) {
			if (isset ( $attributes [$name] ))
				$this->$name = $value;
			else if ($safeOnly) {
				$this->dispatchEvent(ModelEvent::UNSAFE_ATTRIBUTE, new Event($this, array('attribute'=>$$name, 'value'=>$value)));
				$this->onUnsafeAttribute ( $name, $value );
			}
		}
	}
	
	/**
	 * 将某些属性设为NULL
	 * @param array $names
	 * @return void
	 */
	public function unsetAttributes($names = null) {
		if ($names === null)
			$names = $this->attributeNames ();
		foreach ( $names as $name )
			$this->$name = null;
	}
	
	/**
	 * 获取安全的属性名
	 * @return array
	 */
	public function getSafeAttributeNames() {
		$attributes = array ();
		$unsafe = array ();
		foreach ( $this->getValidators () as $validator ) {
			if (! $validator->safe) {
				foreach ( $validator->attributes as $name )
					$unsafe [] = $name;
			} else {
				foreach ( $validator->attributes as $name )
					$attributes [$name] = true;
			}
		}
		
		foreach ( $unsafe as $name )
			unset ( $attributes [$name] );
		return array_keys ( $attributes );
	}
	
	/**
	 * 获取迭代器
	 * @return \ArrayObject
	 */
	public function getIterator() {
		$attributes = $this->getAttributes ();
		return new \ArrayObject ( $attributes );
	}
	
	/**
	 * 设置当前场景
	 * @param string $scenario
	 * @return void
	 */
	public function setScenario($scenario) {
		$this->_scenario = $scenario;
	}
	
	/**
	 * 获取当前场景
	 * @return string
	 */
	public function getScenario() {
		return $this->_scenario;
	}
	
	/**
	 * 属性对应标签
	 * @return array
	 */
	public function attributeLabels() {
		return array ();
	}
	
	public function offsetExists($offset) {
		return property_exists ( $this, $offset );
	}
	
	public function offsetGet($offset) {
		return $this->$offset;
	}
	
	public function offsetSet($offset, $item) {
		$this->$offset = $item;
	}
	
	public function offsetUnset($offset) {
		unset ( $this->$offset );
	}
	

}

?>