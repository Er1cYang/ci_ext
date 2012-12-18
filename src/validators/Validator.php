<?php
namespace ci_ext\validators;
use ci_ext\db\DbCriteria;
use ci_ext\db\Table;
use ci_ext\web\helpers\JSON;
use ci_ext\core\Exception;
abstract class Validator extends \ci_ext\core\Object {
	
	public static $builtInValidators = array (
		'required' => '\ci_ext\validators\RequiredValidator', 			// 必填
		'filter' => '\ci_ext\validators\FilterValidator', 				// 过滤器
		'match' => '\ci_ext\validators\RegularExpressionValidator', 	// 匹配正则
		'email' => '\ci_ext\validators\EmailValidator', 				// 邮箱
		'url' => '\ci_ext\validators\UrlValidator', 					// URL链接
		'unique' => '\ci_ext\validators\UniqueValidator', 				// 是否唯一
		'compare' => '\ci_ext\validators\CompareValidator', 			// 两个属性比较
		'length' => '\ci_ext\validators\StringValidator', 				// 字符串
		'in' => '\ci_ext\validators\RangeValidator',					// 区间
	 	'numerical' => '\ci_ext\validators\NumberValidator', 			// 数值
	 	'default' => '\ci_ext\validators\DefaultValueValidator', 		// 是否为默认数据
	 	'boolean' => '\ci_ext\validators\BooleanValidator', 			// 是否为布尔值
	 	'safe' => '\ci_ext\validators\SafeValidator', 					// 安全 
	 	'unsafe' => '\ci_ext\validators\UnsafeValidator', 				// 非安全
	 	//'date' => '\ci_ext\validators\DateValidator' 					// unsupport 日期
	 	//'captcha' => '\ci_ext\validators\CaptchaValidator', 			// unsupport 验证码
	 	//'type' => '\ci_ext\validators\TypeValidator', 				// unsupport 数据类型
	 	//'file' => '\ci_ext\validators\FileValidator', 				// unsupport 文件上传
	 	//'exist' => '\ci_ext\validators\ExistValidator', 				// unsupport 数据是否存在
	);
	
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
			if (isset ( self::$builtInValidators [$name] ))
				$className = self::$builtInValidators [$name];
			else
				$className = $name;
			$validator = new $className ();
			foreach ( $params as $name => $value )
				$validator->$name = $value;
		}
		
		$validator->on = empty ( $on ) ? array () : array_combine ( $on, $on );
		$validator->except = empty ( $except ) ? array () : array_combine ( $except, $except );
		
		return $validator;
	}
	
	public function validate($object, $attributes = null) {
		if (is_array ( $attributes )) {
			$attributes = array_intersect ( $this->attributes, $attributes );
		} else {
			$attributes = $this->attributes;
		}
		
		foreach ( $attributes as $attribute ) {
			if (! $this->skipOnError || ! $object->hasErrors ( $attribute )) {
				$this->validateAttribute ( $object, $attribute );
			}
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

class BooleanValidator extends Validator {
	public $trueValue = '1';
	public $falseValue = '0';
	public $strict = false;
	public $allowEmpty = true;
	
	protected function validateAttribute($object, $attribute) {
		$value = $object->$attribute;
		if ($this->allowEmpty && $this->isEmpty ( $value ))
			return;
		if (! $this->strict && $value != $this->trueValue && $value != $this->falseValue || $this->strict && $value !== $this->trueValue && $value !== $this->falseValue) {
			$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} must be either {true} or {false}.' );
			$this->addError ( $object, $attribute, $message, array ('{true}' => $this->trueValue, '{false}' => $this->falseValue ) );
		}
	}
	
	public function clientValidateAttribute($object, $attribute) {
		$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} must be either {true} or {false}.' );
		$message = strtr ( $message, array ('{attribute}' => $object->getAttributeLabel ( $attribute ), '{true}' => $this->trueValue, '{false}' => $this->falseValue ) );
		return "
if(" . ($this->allowEmpty ? "$.trim(value)!='' && " : '') . "value!=" . JSON::encode ( $this->trueValue ) . " && value!=" . JSON::encode ( $this->falseValue ) . ") {
	messages.push(" . JSON::encode ( $message ) . ");
}
";
	}
}

class CompareValidator extends Validator {
	public $compareAttribute;
	public $compareValue;
	public $strict = false;
	public $allowEmpty = false;
	public $operator = '=';
	
	protected function validateAttribute($object, $attribute) {
		$value = $object->$attribute;
		if ($this->allowEmpty && $this->isEmpty ( $value ))
			return;
		if ($this->compareValue !== null)
			$compareTo = $compareValue = $this->compareValue;
		else {
			$compareAttribute = $this->compareAttribute === null ? $attribute . '_repeat' : $this->compareAttribute;
			$compareValue = $object->$compareAttribute;
			$compareTo = $object->getAttributeLabel ( $compareAttribute );
		}
		
		switch ($this->operator) {
			case '=' :
			case '==' :
				if (($this->strict && $value !== $compareValue) || (! $this->strict && $value != $compareValue)) {
					$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} must be repeated exactly.' );
					$this->addError ( $object, $attribute, $message, array ('{compareAttribute}' => $compareTo ) );
				}
				break;
			case '!=' :
				if (($this->strict && $value === $compareValue) || (! $this->strict && $value == $compareValue)) {
					$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} must not be equal to "{compareValue}".' );
					$this->addError ( $object, $attribute, $message, array ('{compareAttribute}' => $compareTo, '{compareValue}' => $compareValue ) );
				}
				break;
			case '>' :
				if ($value <= $compareValue) {
					$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} must be greater than "{compareValue}".' );
					$this->addError ( $object, $attribute, $message, array ('{compareAttribute}' => $compareTo, '{compareValue}' => $compareValue ) );
				}
				break;
			case '>=' :
				if ($value < $compareValue) {
					$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} must be greater than or equal to "{compareValue}".' );
					$this->addError ( $object, $attribute, $message, array ('{compareAttribute}' => $compareTo, '{compareValue}' => $compareValue ) );
				}
				break;
			case '<' :
				if ($value >= $compareValue) {
					$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} must be less than "{compareValue}".' );
					$this->addError ( $object, $attribute, $message, array ('{compareAttribute}' => $compareTo, '{compareValue}' => $compareValue ) );
				}
				break;
			case '<=' :
				if ($value > $compareValue) {
					$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} must be less than or equal to "{compareValue}".' );
					$this->addError ( $object, $attribute, $message, array ('{compareAttribute}' => $compareTo, '{compareValue}' => $compareValue ) );
				}
				break;
			default :
				throw new Exception ( \CI_Ext::t ( 'core', 'Invalid operator "{operator}".', array ('{operator}' => $this->operator ) ) );
		}
	}
}

class DefaultValueValidator extends Validator {
	public $value;
	public $setOnEmpty = true;
	
	protected function validateAttribute($object, $attribute) {
		if (! $this->setOnEmpty)
			$object->$attribute = $this->value;
		else {
			$value = $object->$attribute;
			if ($value === null || $value === '')
				$object->$attribute = $this->value;
		}
	}
}

class FileValidator extends Validator {
	public $allowEmpty = false;
	public $types;
	public $mimeTypes;
	public $minSize;
	public $maxSize;
	public $tooLarge;
	public $tooSmall;
	public $wrongType;
	public $wrongMimeType;
	public $maxFiles = 1;
	public $tooMany;
	public $safe = false;
	
	protected function validateAttribute($object, $attribute) {
		return true;
	}
}
class FilterValidator extends Validator {
	public $filter;
	
	protected function validateAttribute($object, $attribute) {
		if ($this->filter === null || ! is_callable ( $this->filter ))
			throw new Exception ( \CI_Ext::t ( 'core', 'The "filter" property must be specified with a valid callback.' ) );
		$object->$attribute = call_user_func_array ( $this->filter, array ($object->$attribute ) );
	}
}
class InlineValidator extends Validator {
	public $method;
	public $params;
	public $clientValidate;
	
	protected function validateAttribute($object, $attribute) {
		$method = $this->method;
		$object->$method ( $attribute, $this->params );
	}
	
	public function clientValidateAttribute($object, $attribute) {
		if ($this->clientValidate !== null) {
			$method = $this->clientValidate;
			return $object->$method ( $attribute );
		}
	}
}
class NumberValidator extends Validator {
	public $integerOnly = false;
	public $allowEmpty = true;
	public $max;
	public $min;
	public $tooBig;
	public $tooSmall;
	public $integerPattern = '/^\s*[+-]?\d+\s*$/';
	public $numberPattern = '/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/';
	
	protected function validateAttribute($object, $attribute) {
		$value = $object->$attribute;
		if ($this->allowEmpty && $this->isEmpty ( $value ))
			return;
		if ($this->integerOnly) {
			if (! preg_match ( $this->integerPattern, "$value" )) {
				$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} must be an integer.' );
				$this->addError ( $object, $attribute, $message );
			}
		} else {
			if (! preg_match ( $this->numberPattern, "$value" )) {
				$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} must be a number.' );
				$this->addError ( $object, $attribute, $message );
			}
		}
		if ($this->min !== null && $value < $this->min) {
			$message = $this->tooSmall !== null ? $this->tooSmall : \CI_Ext::t ( 'core', '{attribute} is too small (minimum is {min}).' );
			$this->addError ( $object, $attribute, $message, array ('{min}' => $this->min ) );
		}
		if ($this->max !== null && $value > $this->max) {
			$message = $this->tooBig !== null ? $this->tooBig : \CI_Ext::t ( 'core', '{attribute} is too big (maximum is {max}).' );
			$this->addError ( $object, $attribute, $message, array ('{max}' => $this->max ) );
		}
	}
	
	public function clientValidateAttribute($object, $attribute) {
		$label = $object->getAttributeLabel ( $attribute );
		
		if (($message = $this->message) === null)
			$message = $this->integerOnly ? \CI_Ext::t ( 'core', '{attribute} must be an integer.' ) : \CI_Ext::t ( 'core', '{attribute} must be a number.' );
		$message = strtr ( $message, array ('{attribute}' => $label ) );
		
		if (($tooBig = $this->tooBig) === null)
			$tooBig = \CI_Ext::t ( 'core', '{attribute} is too big (maximum is {max}).' );
		$tooBig = strtr ( $tooBig, array ('{attribute}' => $label, '{max}' => $this->max ) );
		
		if (($tooSmall = $this->tooSmall) === null)
			$tooSmall = \CI_Ext::t ( 'core', '{attribute} is too small (minimum is {min}).' );
		$tooSmall = strtr ( $tooSmall, array ('{attribute}' => $label, '{min}' => $this->min ) );
		
		$pattern = $this->integerOnly ? $this->integerPattern : $this->numberPattern;
		$js = "
if(!value.match($pattern)) {
	messages.push(" . JSON::encode ( $message ) . ");
}
";
		if ($this->min !== null) {
			$js .= "
if(value<{$this->min}) {
	messages.push(" . JSON::encode ( $tooSmall ) . ");
}
";
		}
		if ($this->max !== null) {
			$js .= "
if(value>{$this->max}) {
	messages.push(" . JSON::encode ( $tooBig ) . ");
}
";
		}
		
		if ($this->allowEmpty) {
			$js = "
if($.trim(value)!='') {
	$js
}
";
		}
		
		return $js;
	}
}
class RangeValidator extends Validator {
	public $range;
	public $strict = false;
	public $allowEmpty = true;
	public $not = false;
	
	protected function validateAttribute($object, $attribute) {
		$value = $object->$attribute;
		if ($this->allowEmpty && $this->isEmpty ( $value ))
			return;
		if (! is_array ( $this->range ))
			throw new Exception ( \CI_Ext::t ( 'core', 'The "range" property must be specified with a list of values.' ) );
		if (! $this->not && ! in_array ( $value, $this->range, $this->strict )) {
			$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} is not in the list.' );
			$this->addError ( $object, $attribute, $message );
		} else if ($this->not && in_array ( $value, $this->range, $this->strict )) {
			$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} is in the list.' );
			$this->addError ( $object, $attribute, $message );
		}
	}
	public function clientValidateAttribute($object, $attribute) {
		if (! is_array ( $this->range ))
			throw new Exception ( \CI_Ext::t ( 'core', 'The "range" property must be specified with a list of values.' ) );
		
		if (($message = $this->message) === null)
			$message = $this->not ? \CI_Ext::t ( 'core', '{attribute} is in the list.' ) : \CI_Ext::t ( 'core', '{attribute} is not in the list.' );
		$message = strtr ( $message, array ('{attribute}' => $object->getAttributeLabel ( $attribute ) ) );
		
		$range = array ();
		foreach ( $this->range as $value )
			$range [] = ( string ) $value;
		$range = JSON::encode ( $range );
		
		return "
if(" . ($this->allowEmpty ? "$.trim(value)!='' && " : '') . ($this->not ? "$.inArray(value, $range)>=0" : "$.inArray(value, $range)<0") . ") {
	messages.push(" . JSON::encode ( $message ) . ");
}
";
	}
}
class RegularExpressionValidator extends Validator {
	public $pattern;
	public $allowEmpty = true;
	public $not = false;
	protected function validateAttribute($object, $attribute) {
		$value = $object->$attribute;
		if ($this->allowEmpty && $this->isEmpty ( $value ))
			return;
		if ($this->pattern === null)
			throw new Exception ( \CI_Ext::t ( 'core', 'The "pattern" property must be specified with a valid regular expression.' ) );
		if ((! $this->not && ! preg_match ( $this->pattern, $value )) || ($this->not && preg_match ( $this->pattern, $value ))) {
			$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} is invalid.' );
			$this->addError ( $object, $attribute, $message );
		}
	}
	
	public function clientValidateAttribute($object, $attribute) {
		if ($this->pattern === null)
			throw new Exception ( \CI_Ext::t ( 'core', 'The "pattern" property must be specified with a valid regular expression.' ) );
		
		$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} is invalid.' );
		$message = strtr ( $message, array ('{attribute}' => $object->getAttributeLabel ( $attribute ) ) );
		
		$pattern = $this->pattern;
		$pattern = preg_replace ( '/\\\\x\{?([0-9a-fA-F]+)\}?/', '\u$1', $pattern );
		$delim = substr ( $pattern, 0, 1 );
		$endpos = strrpos ( $pattern, $delim, 1 );
		$flag = substr ( $pattern, $endpos + 1 );
		if ($delim !== '/')
			$pattern = '/' . str_replace ( '/', '\\/', substr ( $pattern, 1, $endpos - 1 ) ) . '/';
		else
			$pattern = substr ( $pattern, 0, $endpos + 1 );
		if (! empty ( $flag ))
			$pattern .= preg_replace ( '/[^igm]/', '', $flag );
		
		return "
if(" . ($this->allowEmpty ? "$.trim(value)!='' && " : '') . ($this->not ? '' : '!') . "value.match($pattern)) {
	messages.push(" . JSON::encode ( $message ) . ");
}
";
	}
}

class RequiredValidator extends Validator {
	public $requiredValue;
	public $strict = false;
	protected function validateAttribute($object, $attribute) {
		$value = $object->$attribute;
		if ($this->requiredValue !== null) {
			if (! $this->strict && $value != $this->requiredValue || $this->strict && $value !== $this->requiredValue) {
				$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} must be {value}.', array ('{value}' => $this->requiredValue ) );
				$this->addError ( $object, $attribute, $message );
			}
		} else if ($this->isEmpty ( $value, true )) {
			$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} cannot be blank.' );
			$this->addError ( $object, $attribute, $message );
		}
	}
	
	public function clientValidateAttribute($object, $attribute) {
		$message = $this->message;
		if ($this->requiredValue !== null) {
			if ($message === null)
				$message = \CI_Ext::t ( 'core', '{attribute} must be {value}.' );
			$message = strtr ( $message, array ('{value}' => $this->requiredValue, '{attribute}' => $object->getAttributeLabel ( $attribute ) ) );
			return "
if(value!=" . JSON::encode ( $this->requiredValue ) . ") {
	messages.push(" . JSON::encode ( $message ) . ");
}
";
		} else {
			if ($message === null)
				$message = \CI_Ext::t ( 'core', '{attribute} cannot be blank.' );
			$message = strtr ( $message, array ('{attribute}' => $object->getAttributeLabel ( $attribute ) ) );
			return "
if($.trim(value)=='') {
	messages.push(" . JSON::encode ( $message ) . ");
}
";
		}
	}
}
class SafeValidator extends Validator {
	protected function validateAttribute($object, $attribute) {
	}
}
class StringValidator extends Validator {
	public $max;
	public $min;
	public $is;
	public $tooShort;
	public $tooLong;
	public $allowEmpty = true;
	public $encoding;
	protected function validateAttribute($object, $attribute) {
		$value = $object->$attribute;
		if ($this->allowEmpty && $this->isEmpty ( $value ))
			return;
		
		if (function_exists ( 'mb_strlen' ) && $this->encoding !== false)
			$length = mb_strlen ( $value, $this->encoding ? $this->encoding : \CI_Ext::charset () );
		else
			$length = strlen ( $value );
		
		if ($this->min !== null && $length < $this->min) {
			$message = $this->tooShort !== null ? $this->tooShort : \CI_Ext::t ( 'core', '{attribute} is too short (minimum is {min} characters).' );
			$this->addError ( $object, $attribute, $message, array ('{min}' => $this->min ) );
		}
		if ($this->max !== null && $length > $this->max) {
			$message = $this->tooLong !== null ? $this->tooLong : \CI_Ext::t ( 'core', '{attribute} is too long (maximum is {max} characters).' );
			$this->addError ( $object, $attribute, $message, array ('{max}' => $this->max ) );
		}
		if ($this->is !== null && $length !== $this->is) {
			$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} is of the wrong length (should be {length} characters).' );
			$this->addError ( $object, $attribute, $message, array ('{length}' => $this->is ) );
		}
	}
	
	public function clientValidateAttribute($object, $attribute) {
		$label = $object->getAttributeLabel ( $attribute );
		
		if (($message = $this->message) === null)
			$message = \CI_Ext::t ( 'core', '{attribute} is of the wrong length (should be {length} characters).' );
		$message = strtr ( $message, array ('{attribute}' => $label, '{length}' => $this->is ) );
		
		if (($tooShort = $this->tooShort) === null)
			$tooShort = \CI_Ext::t ( 'core', '{attribute} is too short (minimum is {min} characters).' );
		$tooShort = strtr ( $tooShort, array ('{attribute}' => $label, '{min}' => $this->min ) );
		
		if (($tooLong = $this->tooLong) === null)
			$tooLong = \CI_Ext::t ( 'core', '{attribute} is too long (maximum is {max} characters).' );
		$tooLong = strtr ( $tooLong, array ('{attribute}' => $label, '{max}' => $this->max ) );
		
		$js = '';
		if ($this->min !== null) {
			$js .= "
if(value.length<{$this->min}) {
	messages.push(" . JSON::encode ( $tooShort ) . ");
}
";
		}
		if ($this->max !== null) {
			$js .= "
if(value.length>{$this->max}) {
	messages.push(" . JSON::encode ( $tooLong ) . ");
}
";
		}
		if ($this->is !== null) {
			$js .= "
if(value.length!={$this->is}) {
	messages.push(" . JSON::encode ( $message ) . ");
}
";
		}
		
		if ($this->allowEmpty) {
			$js = "
if($.trim(value)!='') {
	$js
}
";
		}
		
		return $js;
	}
}

class UniqueValidator extends Validator {
	public $caseSensitive = true;
	public $allowEmpty = true;
	public $className;
	public $attributeName;
	public $criteria = array ();
	public $message;
	public $skipOnError = true;
	
	protected function validateAttribute($object, $attribute) {
		$value = $object->$attribute;
		if ($this->allowEmpty && $this->isEmpty ( $value ))
			return;
		
		$className = $this->className === null ? get_class ( $object ) : $this->className;
		$attributeName = $this->attributeName === null ? $attribute : $this->attributeName;
		$finder = Table::model ( $className );
		$table = $finder->getTableSchema ();
		if (($column = $table->getColumn ( $attributeName )) === null)
			throw new Exception ( \CI_Ext::t ( 'core', 'Table "{table}" does not have a column named "{column}".', array ('{column}' => $attributeName, '{table}' => $table->name ) ) );
		
		$columnName = $column->rawName;
		$criteria = new DbCriteria ();
		if ($this->criteria !== array ())
			$criteria->mergeWith ( $this->criteria );
		$tableAlias = empty ( $criteria->alias ) ? $finder->getTableAlias ( true ) : $criteria->alias;
		$valueParamName = DbCriteria::PARAM_PREFIX . DbCriteria::$paramCount ++;
		$criteria->addCondition ( $this->caseSensitive ? "{$tableAlias}.{$columnName}={$valueParamName}" : "LOWER({$tableAlias}.{$columnName})=LOWER({$valueParamName})" );
		$criteria->params [$valueParamName] = $value;
		
		if (! $object instanceof Table || $object->isNewRecord || $object->tableName () !== $finder->tableName ())
			$exists = $finder->exists ( $criteria );
		else {
			$criteria->limit = 2;
			$objects = $finder->findAll ( $criteria );
			$n = count ( $objects );
			if ($n === 1) {
				if ($column->isPrimaryKey) // primary key is modified and not unique
					$exists = $object->getOldPrimaryKey () != $object->getPrimaryKey ();
				else {
					// non-primary key, need to exclude the current record based on PK
					$exists = array_shift ( $objects )->getPrimaryKey () != $object->getOldPrimaryKey ();
				}
			} else
				$exists = $n > 1;
		}
		
		if ($exists) {
			$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} "{value}" has already been taken.' );
			$this->addError ( $object, $attribute, $message, array ('{value}' => $value ) );
		}
	}
}

class UnsafeValidator extends Validator {
	public $safe = false;
	protected function validateAttribute($object, $attribute) {
	}
}
class UrlValidator extends Validator {
	public $pattern = '/^{schemes}:\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)/i';
	public $validSchemes = array ('http', 'https' );
	public $defaultScheme;
	public $allowEmpty = true;
	
	protected function validateAttribute($object, $attribute) {
		$value = $object->$attribute;
		if ($this->allowEmpty && $this->isEmpty ( $value ))
			return;
		if (($value = $this->validateValue ( $value )) !== false)
			$object->$attribute = $value;
		else {
			$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} is not a valid URL.' );
			$this->addError ( $object, $attribute, $message );
		}
	}
	
	public function validateValue($value) {
		if (is_string ( $value ) && strlen ( $value ) < 2000) // make sure the length is limited to avoid DOS attacks
{
			if ($this->defaultScheme !== null && strpos ( $value, '://' ) === false)
				$value = $this->defaultScheme . '://' . $value;
			
			if (strpos ( $this->pattern, '{schemes}' ) !== false)
				$pattern = str_replace ( '{schemes}', '(' . implode ( '|', $this->validSchemes ) . ')', $this->pattern );
			else
				$pattern = $this->pattern;
			
			if (preg_match ( $pattern, $value ))
				return $value;
		}
		return false;
	}
	
	public function clientValidateAttribute($object, $attribute) {
		$message = $this->message !== null ? $this->message : \CI_Ext::t ( 'core', '{attribute} is not a valid URL.' );
		$message = strtr ( $message, array ('{attribute}' => $object->getAttributeLabel ( $attribute ) ) );
		
		if (strpos ( $this->pattern, '{schemes}' ) !== false)
			$pattern = str_replace ( '{schemes}', '(' . implode ( '|', $this->validSchemes ) . ')', $this->pattern );
		else
			$pattern = $this->pattern;
		
		$js = "
if(!value.match($pattern)) {
	messages.push(" . JSON::encode ( $message ) . ");
}
";
		if ($this->defaultScheme !== null) {
			$js = "
if(!value.match(/:\\/\\//)) {
	value=" . JSON::encode ( $this->defaultScheme ) . "+'://'+value;
}
$js
";
		}
		
		if ($this->allowEmpty) {
			$js = "
if($.trim(value)!='') {
	$js
}
";
		}
		
		return $js;
	}
}

?>