<?php
namespace ci_ext\db;
class TableEvent extends \ci_ext\core\ModelEvent {
	
	const BEFORE_FIND = 'beforeFind';
	const AFTER_FIND = 'afterFind';
	const BEFORE_SAVE = 'beforeSave';
	const AFTER_SAVE = 'afterSave';
	const BEFORE_DELETE = 'beforeDelete';
	const AFTER_DELETE = 'afterDelete';
	
}

?>