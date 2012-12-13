<?php
namespace ci_ext\validators;
class ValidatorAdapter extends Validator {
	public function validateAttribute($object, $attribute) {
		//\ci_ext\utils\VarDumper::dump($this, 1, true);
		return true;
	}
}

?>