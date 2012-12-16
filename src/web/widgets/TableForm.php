<?php
namespace ci_ext\web\widgets;
use ci_ext\utils\VarDumper;

use ci_ext\web\helpers\JSON;
use ci_ext\web\helpers\JavaScriptExpression;
use ci_ext\db\Table;
use ci_ext\web\helpers\JavaScript;
use ci_ext\web\helpers\Html;

class TableForm extends \ci_ext\web\Widget {
	public $action = '';
	public $method = 'post';
	public $stateful = false;
	public $errorMessageCssClass = 'errorMessage';
	public $htmlOptions = array ();
	public $clientOptions = array ();
	public $enableAjaxValidation = false;
	public $enableClientValidation = false;
	public $focus;
	protected $attributes = array ();
	protected $summaryID;

	public function init() {
		if (! isset ( $this->htmlOptions ['id'] ))
			$this->htmlOptions ['id'] = $this->id;
		else
			$this->id = $this->htmlOptions ['id'];
		
		if ($this->stateful)
			echo Html::statefulForm ( $this->action, $this->method, $this->htmlOptions );
		else
			echo Html::beginForm ( $this->action, $this->method, $this->htmlOptions );
	}
	
	public function run() {
		if (is_array ( $this->focus ))
			$this->focus = "#" . Html::activeId ( $this->focus [0], $this->focus [1] );
	
		echo Html::endForm ();
		if (! $this->enableAjaxValidation && ! $this->enableClientValidation || empty ( $this->attributes )) {
			if ($this->focus !== null) {
				get_instance()->registerScript ( 'CActiveForm#focus', "
					if(!window.location.hash)
						$('" . $this->focus . "').focus();
				" );
			}
			return;
		}
		
		
		$options = $this->clientOptions;
		if (isset ( $this->clientOptions ['validationUrl'] ) && is_array ( $this->clientOptions ['validationUrl'] ))
			$options ['validationUrl'] = Html::normalizeUrl ( $this->clientOptions ['validationUrl'] );
		
		$options ['attributes'] = array_values ( $this->attributes );
		
		if ($this->summaryID !== null)
			$options ['summaryID'] = $this->summaryID;
		
		if ($this->focus !== null)
			$options ['focus'] = $this->focus;
		
		$options = JavaScript::encode ( $options );
		$url = get_instance()->publish(dirname(__FILE__).'/assets');
		
		get_instance()->registerJsFile ($url.'/jquery.yiiactiveform.js');
		$id = $this->id;
		get_instance()->registerScript ( __CLASS__ . '#' . $id, "\$('#$id').yiiactiveform($options);" );
	}
	
	public function error($model, $attribute, $htmlOptions = array(), $enableAjaxValidation = true, $enableClientValidation = true) {
		if (! $this->enableAjaxValidation)
			$enableAjaxValidation = false;
		if (! $this->enableClientValidation)
			$enableClientValidation = false;
		
		if (! isset ( $htmlOptions ['class'] ))
			$htmlOptions ['class'] = $this->errorMessageCssClass;
		
		if (! $enableAjaxValidation && ! $enableClientValidation)
			return Html::error ( $model, $attribute, $htmlOptions );
		
		$id = Html::activeId ( $model, $attribute );
		$inputID = isset ( $htmlOptions ['inputID'] ) ? $htmlOptions ['inputID'] : $id;
		unset ( $htmlOptions ['inputID'] );
		if (! isset ( $htmlOptions ['id'] ))
			$htmlOptions ['id'] = $inputID . '_em_';
		
		$option = array ('id' => $id, 'inputID' => $inputID, 'errorID' => $htmlOptions ['id'], 'model' => get_class ( $model ), 'name' => $attribute, 'enableAjaxValidation' => $enableAjaxValidation );
		
		$optionNames = array ('validationDelay', 'validateOnChange', 'validateOnType', 'hideErrorMessage', 'inputContainer', 'errorCssClass', 'successCssClass', 'validatingCssClass', 'beforeValidateAttribute', 'afterValidateAttribute' );
		foreach ( $optionNames as $name ) {
			if (isset ( $htmlOptions [$name] )) {
				$option [$name] = $htmlOptions [$name];
				unset ( $htmlOptions [$name] );
			}
		}
		if ($model instanceof Table && ! $model->isNewRecord)
			$option ['status'] = 1;
		
		if ($enableClientValidation) {
			$validators = isset ( $htmlOptions ['clientValidation'] ) ? array ($htmlOptions ['clientValidation'] ) : array ();
			
			$attributeName = $attribute;
			if (($pos = strrpos ( $attribute, ']' )) !== false && $pos !== strlen ( $attribute ) - 1) // e.g. [a]name
{
				$attributeName = substr ( $attribute, $pos + 1 );
			}
			
			foreach ( $model->getValidators ( $attributeName ) as $validator ) {
				if ($validator->enableClientValidation) {
					if (($js = $validator->clientValidateAttribute ( $model, $attributeName )) != '')
						$validators [] = $js;
				}
			}
			if ($validators !== array ())
				$option ['clientValidation'] = new JavaScriptExpression ( "function(value, messages, attribute) {\n" . implode ( "\n", $validators ) . "\n}" );
		}
		
		$html = Html::error ( $model, $attribute, $htmlOptions );
		if ($html === '') {
			if (isset ( $htmlOptions ['style'] ))
				$htmlOptions ['style'] = rtrim ( $htmlOptions ['style'], ';' ) . ';display:none';
			else
				$htmlOptions ['style'] = 'display:none';
			$html = Html::tag ( 'div', $htmlOptions, '' );
		}
		
		$this->attributes [$inputID] = $option;
		return $html;
	}
	
	public function errorSummary($models, $header = null, $footer = null, $htmlOptions = array()) {
		if (! $this->enableAjaxValidation && ! $this->enableClientValidation)
			return Html::errorSummary ( $models, $header, $footer, $htmlOptions );
		
		if (! isset ( $htmlOptions ['id'] ))
			$htmlOptions ['id'] = $this->id . '_es_';
		$html = Html::errorSummary ( $models, $header, $footer, $htmlOptions );
		if ($html === '') {
			if ($header === null)
				$header = '<p>' . \CI_Ext::t('core', 'Please fix the following input errors:' ) . '</p>';
			if (! isset ( $htmlOptions ['class'] ))
				$htmlOptions ['class'] = Html::$errorSummaryCss;
			$htmlOptions ['style'] = isset ( $htmlOptions ['style'] ) ? rtrim ( $htmlOptions ['style'], ';' ) . ';display:none' : 'display:none';
			$html = Html::tag ( 'div', $htmlOptions, $header . "\n<ul><li>dummy</li></ul>" . $footer );
		}
		
		$this->summaryID = $htmlOptions ['id'];
		return $html;
	}
	
	public function label($model, $attribute, $htmlOptions = array()) {
		return Html::activeLabel ( $model, $attribute, $htmlOptions );
	}
	
	public function labelEx($model, $attribute, $htmlOptions = array()) {
		return Html::activeLabelEx ( $model, $attribute, $htmlOptions );
	}
	
	public function urlField($model, $attribute, $htmlOptions = array()) {
		return Html::activeUrlField ( $model, $attribute, $htmlOptions );
	}
	
	public function emailField($model, $attribute, $htmlOptions = array()) {
		return Html::activeEmailField ( $model, $attribute, $htmlOptions );
	}
	
	public function numberField($model, $attribute, $htmlOptions = array()) {
		return Html::activeNumberField ( $model, $attribute, $htmlOptions );
	}
	
	public function rangeField($model, $attribute, $htmlOptions = array()) {
		return Html::activeRangeField ( $model, $attribute, $htmlOptions );
	}
	
	public function dateField($model, $attribute, $htmlOptions = array()) {
		return Html::activeDateField ( $model, $attribute, $htmlOptions );
	}
	
	public function textField($model, $attribute, $htmlOptions = array()) {
		return Html::activeTextField ( $model, $attribute, $htmlOptions );
	}
	
	public function hiddenField($model, $attribute, $htmlOptions = array()) {
		return Html::activeHiddenField ( $model, $attribute, $htmlOptions );
	}
	
	public function passwordField($model, $attribute, $htmlOptions = array()) {
		return Html::activePasswordField ( $model, $attribute, $htmlOptions );
	}
	
	public function textArea($model, $attribute, $htmlOptions = array()) {
		return Html::activeTextArea ( $model, $attribute, $htmlOptions );
	}
	
	public function fileField($model, $attribute, $htmlOptions = array()) {
		return Html::activeFileField ( $model, $attribute, $htmlOptions );
	}
	
	public function radioButton($model, $attribute, $htmlOptions = array()) {
		return Html::activeRadioButton ( $model, $attribute, $htmlOptions );
	}
	
	public function checkBox($model, $attribute, $htmlOptions = array()) {
		return Html::activeCheckBox ( $model, $attribute, $htmlOptions );
	}
	
	public function dropDownList($model, $attribute, $data, $htmlOptions = array()) {
		return Html::activeDropDownList ( $model, $attribute, $data, $htmlOptions );
	}
	
	public function listBox($model, $attribute, $data, $htmlOptions = array()) {
		return Html::activeListBox ( $model, $attribute, $data, $htmlOptions );
	}
	
	public function checkBoxList($model, $attribute, $data, $htmlOptions = array()) {
		return Html::activeCheckBoxList ( $model, $attribute, $data, $htmlOptions );
	}
	
	public function radioButtonList($model, $attribute, $data, $htmlOptions = array()) {
		return Html::activeRadioButtonList ( $model, $attribute, $data, $htmlOptions );
	}
	
	public static function validate($models, $attributes = null, $loadInput = true) {
		$result = array ();
		if (! is_array ( $models ))
			$models = array ($models );
		foreach ( $models as $model ) {
			if ($loadInput && isset ( $_POST [get_class ( $model )] ))
				$model->attributes = $_POST [get_class ( $model )];
			$model->validate ( $attributes );
			foreach ( $model->getErrors () as $attribute => $errors )
				$result [Html::activeId ( $model, $attribute )] = $errors;
		}
		return function_exists ( 'json_encode' ) ? json_encode ( $result ) : JSON::encode ( $result );
	}
	
	public static function validateTabular($models, $attributes = null, $loadInput = true) {
		$result = array ();
		if (! is_array ( $models ))
			$models = array ($models );
		foreach ( $models as $i => $model ) {
			if ($loadInput && isset ( $_POST [get_class ( $model )] [$i] ))
				$model->attributes = $_POST [get_class ( $model )] [$i];
			$model->validate ( $attributes );
			foreach ( $model->getErrors () as $attribute => $errors )
				$result [Html::activeId ( $model, '[' . $i . ']' . $attribute )] = $errors;
		}
		return function_exists ( 'json_encode' ) ? json_encode ( $result ) : JSON::encode ( $result );
	}
}