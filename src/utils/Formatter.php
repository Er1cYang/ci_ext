<?php
namespace ci_ext\utils;
use ci_ext\web\helpers\Html;
use ci_ext\core\Exception;
class Formatter extends \ci_ext\core\Object {
	private $_htmlPurifier;
	public $dateFormat = 'Y/m/d';
	public $timeFormat = 'h:i:s A';
	public $datetimeFormat = 'Y/m/d h:i:s A';
	public $numberFormat = array ('decimals' => null, 'decimalSeparator' => null, 'thousandSeparator' => null );
	public $booleanFormat = array ('No', 'Yes' );
	
	public $sizeFormat = array ('base' => 1024, 'decimals' => 2 );
	
	public function __call($name, $parameters) {
		if (method_exists ( $this, 'format' . $name ))
			return call_user_func_array ( array ($this, 'format' . $name ), $parameters );
		else
			return parent::__call ( $name, $parameters );
	}
	
	public function format($value, $type) {
		$method = 'format' . $type;
		if (method_exists ( $this, $method ))
			return $this->$method ( $value );
		else
			throw new Exception( \CI_Ext::t( 'core', 'Unknown type "{type}".', array ('{type}' => $type ) ) );
	}
	
	public function formatRaw($value) {
		return $value;
	}
	
	public function formatText($value) {
		return Html::encode ( $value );
	}
	
	public function formatNtext($value) {
		return nl2br ( Html::encode ( $value ) );
	}
	
	public function formatHtml($value) {
		return $this->getHtmlPurifier ()->purify ( $value );
	}
	
	public function formatDate($value) {
		return date ( $this->dateFormat, $value );
	}
	
	public function formatTime($value) {
		return date ( $this->timeFormat, $value );
	}
	
	public function formatDatetime($value) {
		return date ( $this->datetimeFormat, $value );
	}
	
	public function formatBoolean($value) {
		return $value ? $this->booleanFormat [1] : $this->booleanFormat [0];
	}
	
	public function formatEmail($value) {
		return Html::mailto ( $value );
	}
	
	public function formatImage($value) {
		return Html::image ( $value );
	}
	
	public function formatUrl($value) {
		$url = $value;
		if (strpos ( $url, 'http://' ) !== 0 && strpos ( $url, 'https://' ) !== 0)
			$url = 'http://' . $url;
		return Html::link ( Html::encode ( $value ), $url );
	}
	
	public function formatNumber($value) {
		return number_format ( $value, $this->numberFormat ['decimals'], $this->numberFormat ['decimalSeparator'], $this->numberFormat ['thousandSeparator'] );
	}
	
}
