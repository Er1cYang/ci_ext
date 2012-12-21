<?php
namespace ci_ext\web\widgets\gridview;
use ci_ext\web\TableDataProvider;
use ci_ext\web\helpers\Html;
use ci_ext\core\Exception;

class DataColumn extends GridColumn {
	
	public $name;
	public $value;
	public $type = 'text';
	public $sortable = true;
	
	public function init() {
		parent::init ();
		if ($this->name === null)
			$this->sortable = false;
		if ($this->name === null && $this->value === null)
			throw new Exception ( \CI_Ext::t ( 'core', 'Either "name" or "value" must be specified for DataColumn.' ) );
	}
	
	protected function renderHeaderCellContent() {
		if ($this->grid->enableSorting && $this->sortable && $this->name !== null) {
			echo $this->grid->dataProvider->getSort ()->link ( $this->name, $this->header, array ('class' => 'sort-link' ) );
		} else if ($this->name !== null && $this->header === null) {
			if ($this->grid->dataProvider instanceof TableDataProvider)
				echo Html::encode ( $this->grid->dataProvider->model->getAttributeLabel ( $this->name ) );
			else
				echo Html::encode ( $this->name );
		} else
			parent::renderHeaderCellContent ();
	}
	
	protected function renderDataCellContent($row, $data) {
		if($this->value instanceof \Closure) {
			echo call_user_func_array($this->value, array($this, $row, $data));
			return;
		} else if ($this->value !== null)
			$value = $this->evaluateExpression ( $this->value, array ('data' => $data, 'row' => $row ) );
		else if ($this->name !== null)
			$value = Html::value ( $data, $this->name );
		echo $value === null ? $this->grid->nullDisplay : $this->grid->getFormatter()->format($value,$this->type);
	}
}

?>