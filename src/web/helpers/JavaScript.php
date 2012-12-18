<?php
namespace ci_ext\web\helpers;
class JavaScript {
	public static function quote($js, $forUrl = false) {
		if ($forUrl)
			return strtr ( $js, array ('%' => '%25', "\t" => '\t', "\n" => '\n', "\r" => '\r', '"' => '\"', '\'' => '\\\'', '\\' => '\\\\', '</' => '<\/' ) );
		else
			return strtr ( $js, array ("\t" => '\t', "\n" => '\n', "\r" => '\r', '"' => '\"', '\'' => '\\\'', '\\' => '\\\\', '</' => '<\/' ) );
	}
	
	public static function encode($value, $safe = false) {
		if (is_string ( $value )) {
			if (strpos ( $value, 'js:' ) === 0 && $safe === false)
				return substr ( $value, 3 );
			else
				return "'" . self::quote ( $value ) . "'";
		} else if ($value === null)
			return 'null';
		else if (is_bool ( $value ))
			return $value ? 'true' : 'false';
		else if (is_integer ( $value ))
			return "$value";
		else if (is_float ( $value )) {
			if ($value === - INF)
				return 'Number.NEGATIVE_INFINITY';
			else if ($value === INF)
				return 'Number.POSITIVE_INFINITY';
			else
				return rtrim ( sprintf ( '%.16F', $value ), '0' ); // locale-independent representation
		} else if ($value instanceof JavaScriptExpression)
			return $value->__toString ();
		else if (is_object ( $value ))
			return self::encode ( get_object_vars ( $value ) );
		else if (is_array ( $value )) {
			$es = array ();
			if (($n = count ( $value )) > 0 && array_keys ( $value ) !== range ( 0, $n - 1 )) {
				foreach ( $value as $k => $v )
					$es [] = "'" . self::quote ( $k ) . "':" . self::encode ( $v );
				return '{' . implode ( ',', $es ) . '}';
			} else {
				foreach ( $value as $v )
					$es [] = self::encode ( $v );
				return '[' . implode ( ',', $es ) . ']';
			}
		} else
			return '';
	}
	
	public static function jsonEncode($data) {
		return JSON::encode ( $data );
	}
	
	public static function jsonDecode($data, $useArray = true) {
		return JSON::decode ( $data, $useArray );
	}
}
