<?php
namespace ci_ext\web;

use ci_ext\utils\ArrayList;
use ci_ext\utils\FileHelper;
/**
 * CI_E_Controller
 * ==============================================
 * File encoding: UTF-8 
 * ----------------------------------------------
 * CI_E_Controller.php
 * ==============================================
 * @author YangDongqi <yangdongqi@gmail.com>
 * @copyright Copyright &copy; 2006-2012 Hayzone IT LTD.
 * @version $id$
 */
class WebController extends \CI_Controller {
	
	private $_js;
	private $_css;
	private $_assetRendered = false;		// 是否已经注册了asset文件
	
	/**
	 * 初始化js和css容器
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->_js = new ArrayList();
		$this->_css = new ArrayList();
		$this->load->helper('url');
		$this->init();
	}
	
	/**
	 * 这里完成一些初始化的工作
	 */
	protected function init() {
	}
	
	/**
	 * 获取网站根节点
	 * @return string
	 */
	public function getBaseUrl() {
		return base_url();
	}
	
	/**
	 * 注册css文件
	 * @param string $file
	 * @return void
	 */
	public function registerCssFile($file) {
		if(!$this->_css->contain($file)) {
			$this->_css->push($file);
		}
	}
	
	/**
	 * 注册js文件
	 * @param string $file
	 * @return void
	 */
	public function registerJsFile($file) {
		if(!$this->_js->contain($file)) {
			$this->_js->push($file);
		}
	}
	
	/**
	 * 发布到web可以访问的目录
	 * <pre>
	 * 返回web可以访问的目录
	 * </pre>
	 * @param string $path
	 * @param boolean $forceCopy 是否覆盖原有
	 * @return string
	 */
	public function publish($path, $forceCopy=false) {
		$dir = '/ui/assets/'.sprintf('%x', crc32($path));
		$destPath = WEB_ROOT.$dir;
		if(!file_exists($destPath) || $forceCopy) {
			@mkdir($destPath, 0777, true);
			FileHelper::copyDirectory($path,$destPath,array(
				'exclude'=>array('.svn', '.gitignore'),
			));
		}
		return $this->getBaseUrl().$dir;
	}
	
	/**
	 * 渲染页面
	 * <pre>
	 * 调用了CI_Controller->load->view()方法
	 * 用词方法，可以在视图上使用$this变量，它
	 * 指向当前controller的实例
	 * </pre>
	 * @param string $view
	 * @param array $data
	 */
	public function render($view, $data=array()) {
		$data['this'] = $this;
		ob_start();
		$this->load->view($view, $data);
		$output = ob_get_clean();
		if(!$this->_assetRendered) {
			$count=0;
			$output=preg_replace('/(<title\b[^>]*>|<\\/head\s*>)/is','<###head###>$1',$output,1,$count);
			if($count) {
				$assetHtml = '';
				foreach($this->_css as $one) {
					$assetHtml .= "<link href='{$one}' type='text/css' rel='stylesheet' />\n";
				}
				foreach($this->_js as $one) {
					$assetHtml .= "<script type='text/javascript' src='{$one}'></script>\n";
				}
				$output=str_replace('<###head###>', $assetHtml, $output);
				$this->_assetRendered = true;
			}
		}
		echo $output;
	}

}

?>