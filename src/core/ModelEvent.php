<?php
namespace ci_ext\core;
class ModelEvent extends \ci_ext\events\Event {
	
	const BEFORE_VALIDATE = 'beforeValidate';
	const AFTER_VALIDATE = 'afterValidate';
	const UNSAFE_ATTRIBUTE = 'unsafeAttribute';
	
	public $isValid = true;
}

?>