<?php
namespace ci_ext\core;
/**
 * ModelEvent
 * ==============================================
 * File encoding: UTF-8 
 * ----------------------------------------------
 * ModelEvent.php
 * ==============================================
 * @author YangDongqi <yangdongqi@gmail.com>
 * @copyright Copyright &copy; 2006-2012 Hayzone IT LTD.
 * @version $id$
 */
class ModelEvent extends \ci_ext\events\Event {
	
	const BEFORE_VALIDATE = 'beforeValidate';
	const AFTER_VALIDATE = 'afterValidate';
	const UNSAFE_ATTRIBUTE = 'unsafeAttribute';
	
	public $isValid = true;
}

?>