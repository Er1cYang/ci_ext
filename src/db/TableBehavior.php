<?php
namespace ci_ext\db;
use ci_ext\events\Event;
class TableBehavior extends \ci_ext\core\ModelBehavior {
	
	/**
	 * 侦听了所有相关于Table的事件
	 * @see ci_ext\core.Behavior::events()
	 */
	public function events() {
		return array_merge(parent::events(), array(
			TableEvent::BEFORE_FIND 	=> 'beforeFind',
			TableEvent::AFTER_FIND 		=> 'afterFind',
			TableEvent::BEFORE_SAVE 	=> 'beforeSave',
			TableEvent::AFTER_SAVE 		=> 'afterSave',
			TableEvent::BEFORE_DELETE 	=> 'beforeDelete',
			TableEvent::AFTER_DELETE 	=> 'afterDelete',
		));
	}
	
	/**
	 * @see Table::beforeFind
	 * @param Event $e
	 * @return void
	 */
	public function beforeFind(Event $e) {
	}
	
	/**
	 * @see Table::afterFind
	 * @param Event $e
	 * @return void
	 */
	public function afterFind(Event $e) {
	}
	
	/**
	 * @see Table::beforeSave
	 * @param TableEvent $e
	 * @return void
	 */
	public function beforeSave(TableEvent $e) {
	}
	
	/**
	 * @see Table::afterSave
	 * @param Event $e
	 * @return void
	 */
	public function afterSave(Event $e) {
	}
	
	/**
	 * @see Table::beforeDelete
	 * @param TableEvent $e
	 * @return void
	 */
	public function beforeDelete(TableEvent $e) {
	}
	
	/**
	 * @see Table::afterDelete
	 * @param Event $e
	 * @return void
	 */
	public function afterDelete(Event $e) {
	}
	
}

?>