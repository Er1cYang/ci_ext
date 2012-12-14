<?php
namespace ci_ext\web\widgets\gridview;

/**
 * GridView
 * ==============================================
 * File encoding: UTF-8 
 * ----------------------------------------------
 * GridView.php
 * ==============================================
 * @author YangDongqi <yangdongqi@gmail.com>
 * @copyright Copyright &copy; 2006-2012 Hayzone IT LTD.
 * @version $id$
 */
use ci_ext\utils\VarDumper;

use ci_ext\web\helpers\Html;

class GridView extends \ci_ext\core\Object {
	
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
	public $enableSorting=false;
	public $baseScriptUrl;
	
	public function __construct() {
		$this->_ci =& \get_instance();
		$assetBaseUrl = $this->_ci->publish(dirname(__FILE__).'/assets', true);
		$this->_ci->registerJsFile($assetBaseUrl.'/jquery.gridview.js');
		if($this->cssFile) {
			$this->_ci->registerCssFile($this->cssFile);
		}
	}
		
	public function render() {
		
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
	<table class='items'>\n{$header}\n<tbody>{$body}</tbody>\n{$footer}\n</table>
	<div class='pager'>{$pager}</div>
</div>
TEMPLATE;
		
		$this->_ci->registerScript('cie-gridview-'.$this->id, "$('#{$this->id}').cieGridview();");
		
		echo $template;
		
	}
	
	protected function createPager() {
		$this->pager['pages'] = $this->dataProvider->pagination;
		$this->dataProvider->pagination->route = uri_string();
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
		foreach($this->dataProvider->getData() as $row=>$data) {
			echo "<tr>\n";
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