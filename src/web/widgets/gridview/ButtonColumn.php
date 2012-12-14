<?php
namespace ci_ext\web\widgets\gridview;

use ci_ext\web\helpers\Html;
use ci_ext\web\helpers\JavaScript;
use ci_ext\web\helpers\JavaScriptExpression;

class ButtonColumn extends GridColumn {
	public $htmlOptions = array ('class' => 'button-column' );
	public $headerHtmlOptions = array ('class' => 'button-column' );
	public $footerHtmlOptions = array ('class' => 'button-column' );
	public $template = '{view} {update} {delete}';
	public $viewButtonLabel;
	public $viewButtonImageUrl;
	public $viewButtonUrl = 'get_instance()->createUrl("view",array("id"=>$data->primaryKey))';
	public $viewButtonOptions = array ('class' => 'view' );
	public $updateButtonLabel;
	public $updateButtonImageUrl;
	public $updateButtonUrl = 'get_instance()->createUrl("update",array("id"=>$data->primaryKey))';
	public $updateButtonOptions = array ('class' => 'update' );
	public $deleteButtonLabel;
	public $deleteButtonImageUrl;
	public $deleteButtonUrl = 'get_instance()->createUrl("delete",array("id"=>$data->primaryKey))';
	public $deleteButtonOptions = array ('class' => 'delete' );
	public $deleteConfirmation;
	public $afterDelete;
	public $buttons = array ();
	
	public function init() {
		$this->initDefaultButtons ();
		
		foreach ( $this->buttons as $id => $button ) {
			if (strpos ( $this->template, '{' . $id . '}' ) === false)
				unset ( $this->buttons [$id] );
			else if (isset ( $button ['click'] )) {
				if (! isset ( $button ['options'] ['class'] ))
					$this->buttons [$id] ['options'] ['class'] = $id;
				if (! ($button ['click'] instanceof JavaScriptExpression))
					$this->buttons [$id] ['click'] = new JavaScriptExpression ( $button ['click'] );
			}
		}
		
		$this->registerClientScript ();
	}
	
	protected function initDefaultButtons() {
		if ($this->viewButtonLabel === null)
			$this->viewButtonLabel = \CI_Ext::t ( 'core', 'View' );
		if ($this->updateButtonLabel === null)
			$this->updateButtonLabel = \CI_Ext::t ( 'core', 'Update' );
		if ($this->deleteButtonLabel === null)
			$this->deleteButtonLabel = \CI_Ext::t ( 'core', 'Delete' );
		if ($this->deleteConfirmation === null)
			$this->deleteConfirmation = \CI_Ext::t ( 'core', 'Are you sure you want to delete this item?' );
		
		foreach ( array ('view', 'update', 'delete' ) as $id ) {
			$button = array ('label' => $this->{$id . 'ButtonLabel'}, 'url' => $this->{$id . 'ButtonUrl'}, 'imageUrl' => $this->{$id . 'ButtonImageUrl'}, 'options' => $this->{$id . 'ButtonOptions'} );
			if (isset ( $this->buttons [$id] ))
				$this->buttons [$id] = array_merge ( $button, $this->buttons [$id] );
			else
				$this->buttons [$id] = $button;
		}
		
		if (! isset ( $this->buttons ['delete'] ['click'] )) {
			if (is_string ( $this->deleteConfirmation ))
				$confirmation = "if(!confirm(" . JavaScript::encode ( $this->deleteConfirmation ) . ")) return false;";
			else
				$confirmation = '';
			
			$csrf = '';
			
			if ($this->afterDelete === null)
				$this->afterDelete = 'function(){}';
			
			$this->buttons ['delete'] ['click'] = <<<EOD
function() {
	$confirmation
	var th=this;
	var afterDelete=$this->afterDelete;
	$.cieGridview.get('{$this->grid->id}').update({
		type:'POST',
		url:$(this).attr('href'),
		success:function(data) {
			$.cieGridview.get('{$this->grid->id}').update();
		}
	});
	return false;
}
EOD;
		}
	}
	
	protected function registerClientScript() {
		$js = array ();
		foreach ( $this->buttons as $id => $button ) {
			if (isset ( $button ['click'] )) {
				$function = JavaScript::encode ( $button ['click'] );
				$class = preg_replace ( '/\s+/', '.', $button ['options'] ['class'] );
				$js [] = "$('#{$this->grid->id} a.{$class}').live('click',$function);";
			}
		}
		
		if ($js !== array ())
			get_instance()->registerScript ( __CLASS__ . '#' . $this->id, implode ( "\n", $js ) );
	}
	
	protected function renderDataCellContent($row, $data) {
		$tr = array ();
		ob_start ();
		foreach ( $this->buttons as $id => $button ) {
			$this->renderButton ( $id, $button, $row, $data );
			$tr ['{' . $id . '}'] = ob_get_contents ();
			ob_clean ();
		}
		ob_end_clean ();
		echo strtr ( $this->template, $tr );
	}
	
	protected function renderButton($id, $button, $row, $data) {
		if (isset ( $button ['visible'] ) && ! $this->evaluateExpression ( $button ['visible'], array ('row' => $row, 'data' => $data ) ))
			return;
		$label = isset ( $button ['label'] ) ? $button ['label'] : $id;
		$url = isset ( $button ['url'] ) ? $this->evaluateExpression ( $button ['url'], array ('data' => $data, 'row' => $row ) ) : '#';
		$options = isset ( $button ['options'] ) ? $button ['options'] : array ();
		if (! isset ( $options ['title'] ))
			$options ['title'] = $label;
		if (isset ( $button ['imageUrl'] ) && is_string ( $button ['imageUrl'] ))
			echo Html::link ( Html::image ( $button ['imageUrl'], $label ), $url, $options );
		else
			echo Html::link ( $label, $url, $options );
	}
}
