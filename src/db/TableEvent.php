<?php
namespace ci_ext\db;
/**
 * TableEvent
 * ==============================================
 * File encoding: UTF-8 
 * ----------------------------------------------
 * TableEvent.php
 * ==============================================
 * @author YangDongqi <yangdongqi@gmail.com>
 * @copyright Copyright &copy; 2006-2012 Hayzone IT LTD.
 * @version $id$
 */
class TableEvent extends \ci_ext\events\Event {
	
	const BEFORE_FIND = 'beforeFind';
	const AFTER_FIND = 'afterFind';
	const BEFORE_SAVE = 'beforeSave';
	const AFTER_SAVE = 'afterSave';
	const BEFORE_VALIDATE = 'beforeValidate';
	const AFTER_VALIDATE = 'afterValidate';
	const BEFORE_DELETE = 'beforeDelete';
	const AFTER_DELETE = 'afterDelete';
	
	public $isValid = true;
	
}

?>