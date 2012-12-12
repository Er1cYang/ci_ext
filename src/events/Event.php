<?php
namespace ci_ext\events;
/**
 * Event
 * ==============================================
 * File encoding: UTF-8 
 * ----------------------------------------------
 * Event.php
 * ==============================================
 * @author YangDongqi <yangdongqi@gmail.com>
 * @copyright Copyright &copy; 2006-2012 Hayzone IT LTD.
 * @version $id$
 */
class Event extends \ci_ext\core\Object {

	const AFTER_CONSTRUCT = 'afterConstruct';
	
	public $target;
	public $params = array();
	
	public function __construct($target, $params = array()) {
		$this->target = $target;
		$this->params = $params;
	}
}

?>