<?php
namespace ci_ext\web\helpers;
use ci_ext\core\Exception;

class JavaScriptExpression {
	public $code;
	
	public function __construct($code) {
		if (! is_string ( $code ))
			throw new Exception ( 'Value passed to CJavaScriptExpression should be a string.' );
		if (strpos ( $code, 'js:' ) === 0)
			$code = substr ( $code, 3 );
		$this->code = $code;
	}
	public function __toString() {
		return $this->code;
	}
}