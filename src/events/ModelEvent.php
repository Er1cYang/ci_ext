<?php
namespace ci_ext\events;
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
	
	const AFTER_FIND = 'afterFind';
	const BEFORE_FIND = 'beforeFind';
	const BEFORE_SAVE = 'beforeSave';
	const AFTER_SAVE = 'afterSave';
	
	public $isValid = true;
	
}

?>