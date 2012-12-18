<?php
namespace ci_ext\core;
class ModelBehavior extends Behavior {
	
	public function events() {
		return array(
			ModelEvent::BEFORE_VALIDATE => 'beforeValidate',
			ModelEvent::AFTER_VALIDATE 	=> 'afterValidate',
		);
	}
	
	/**
	 * @see Model::beforeValidate
	 * @param ModelEvent $e
	 * @return void
	 */
	public function beforeValidate(TableEvent $e) {
	}
	
	/**
	 * @see Model::afterValidate
	 * @param Event $e
	 * @return void
	 */
	public function afterValidate(Event $e) {
	}
}

?>