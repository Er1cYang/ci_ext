<?php
namespace ci_ext\web\widgets\gridview;

class GridView {
	
	public $dataSource;
	public $columns = array();
	
	public function __construct() {
		$assetBaseUrl = \CI_Controller::get_instance()->publish(dirname(__FILE__).'/assets');
		\CI_Controller::get_instance()->registerJsFile($assetBaseUrl.'/jquery.gridview.js');
	}
		
	public function render() {
		
		$this->initColumns();
		
		$header = $this->renderHeader();
		$body = $this->renderBody();
		$footer = $this->renderFooter();
		
		$template = <<<TEMPLATE
<table id='test'>\n<thead>{$header}</thead>\n<tbody>{$body}</tbody>\n<tfoot>{$footer}</tfoot>\n</table>
TEMPLATE;
		
		echo $template;
	}
	
	protected function initColumns() {
		$newColumns = array();
		foreach($this->columns as $column) {
			if(is_string($column)) {
				$newColumns[] = new DataColumn($column, $this);
			} else {
				
			}
		}
		$this->columns = $newColumns;
	}
	
	protected function renderHeader() {
		$result = "<tr>\n";
		foreach($this->columns as $column) {
			$result .= '<th>'.$column->renderHeader()."</th>\n";
		}
		$result .= "</tr>\n";
		return $result;
	}
	
	protected function renderBody() {
		$result = '';
		foreach($this->dataSource->getData() as $row) {
			$result .= "<tr>\n";
			foreach($this->columns as $column) {
				$result .= '<td>'.$column->renderBody($row)."</td>\n";		
			}
			$result .= "</tr>\n";
		}
		return $result;
	}
	
	protected function renderFooter() {
		$result = "<tr>\n";
		foreach($this->columns as $column) {
			$result .= '<td>'.$column->renderFooter()."</td>\n";
		}
		$result .= "</tr>\n";
		return $result;
	}
	
	

}

?>