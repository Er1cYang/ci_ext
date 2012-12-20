<?php
namespace ci_ext\web\widgets\gridview;

use ci_ext\utils\VarDumper;

use ci_ext\web\helpers\Html;

class GridView extends \ci_ext\web\Widget {
	
	private $_ci;
	
	public $id;
	public $dataProvider;
	public $columns = array();
	public $cssFile=null;
	public $pager=array('class'=>'ci_ext\web\widgets\pagers\LinkPager');
	public $hasFooter=false;
	public $hasHeader=true;
	public $blankDisplay;
	public $nullDisplay;
	public $enableSorting=true;
	public $baseScriptUrl;
	public $sort;
	public $selectableRows=1;
	public $rowCssClass=array('odd', 'even');
	
	public function __construct() {
		$this->_ci =& \get_instance();
		$assetBaseUrl = $this->_ci->publish(dirname(__FILE__).'/assets', true);
		$this->_ci->registerJsFile($assetBaseUrl.'/jquery.gridview.js');
		if($this->cssFile) {
			$this->_ci->registerCssFile($this->cssFile);
		}
	}
	
	public function init() {
	}
		
	public function run() {
		
		$this->initColumns();
		$this->dataProvider->getData();
		$header = '';
		$footer = '';
		
		if($this->hasHeader)		
			$header = Html::tag('thead', array(), $this->renderHeader(), true);
		if($this->hasFooter)
			$footer = Html::tag('tfoot', array(), $this->renderFooter(), true);
		$body = $this->renderBody();
		
		$pager = $this->createPager();
		
		$template = <<<TEMPLATE
<div class='cie-grid-view' id='{$this->id}'>
	<table class='items class'>\n{$header}\n<tbody>{$body}</tbody>\n{$footer}\n</table>
	<div class='pager'>{$pager}</div>
</div>
TEMPLATE;
		
		$url = get_instance()->getCurrentUrl();
		$this->_ci->registerScript('cie-gridview-'.$this->id, "$('#{$this->id}').cieGridview({url: '{$url}'});");
		
		echo $template;
		
	}
	
	protected function createPager() {
		$this->pager['pages'] = $this->dataProvider->pagination;
		$route = get_instance()->getRoute();;
		$this->dataProvider->pagination->route = $route;
		$this->dataProvider->sort->route = $route;
		$page = \CI_Ext::createObject($this->pager);
		ob_start();
		$page->init();
		$page->run();
		return ob_get_clean();
	}
	
	protected function initColumns() {
		$newColumns = array();
		foreach($this->columns as $column) {
			$config = array();
			if(is_string($column)) {
				$name = $column;
				$column = new DataColumn($this);
				$column->name = $name;
			} else if(is_array($column)) {
				$config = $column;
				if(isset($column['class'])) {
					$column = \CI_Ext::createObject($config, array($this));
				} else {
					$column = new DataColumn($this);
					foreach($config as $k=>$v) {
						$column->$k=$v;
					}
				}
			}
			$column->init();
			$newColumns[] = $column;
		}
		$this->columns = $newColumns;
	}
	
	protected function renderHeader() {
		ob_start();
		echo "<tr>\n";
		foreach($this->columns as $column) {
			$column->renderHeaderCell()."\n";
		}
		echo "</tr>\n";
		return ob_get_clean();
	}
	
	protected function renderBody() {
		ob_start();
		$rowClasses = $this->rowCssClass;
		$rowClassCount = count($rowClasses);
		foreach($this->dataProvider->getData() as $row=>$data) {
			$rowClass = $rowClasses[$row%$rowClassCount];
			echo "<tr class='{$rowClass}'>\n";
			foreach($this->columns as $column) {
				$column->renderDataCell($row)."\n";		
			}
			echo "</tr>\n";
		}
		return ob_get_clean();
	}
	
	protected function renderFooter() {
		ob_start();
		echo "<tr>\n";
		foreach($this->columns as $column) {
			$column->renderFooterCell()."\n";
		}
		echo "</tr>\n";
		return ob_get_clean();
	}
	
	

}

?>