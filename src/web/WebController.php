<?php
namespace ci_ext\web;

use ci_ext\core\Exception;

use ci_ext\utils\HashMap;

use ci_ext\utils\ArrayList;
use ci_ext\utils\FileHelper;
/**
 * WebController
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
	
	private $_jsFiles;
	private $_cssFiles;
	private $_js;
	private $_assetHeaderRendered = false;		// 是否已经注册了头部asset文件
	private $_assetFooterRendered = false;
	private $_widgetStack = array();
	
	/**
	 * 初始化js和css容器
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->_jsFiles = new ArrayList();
		$this->_cssFiles = new ArrayList();
		$this->_js = new HashMap();
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
	public function getBaseUrl($url='') {
		return base_url($url);
	}
	
	/**
	 * @see site_url()
	 * @return string
	 */
	public function getSiteUrl($uri='') {
		return site_url($uri);
	}
	
	/**
	 * 跳转
	 * @param string $url
	 * @param boolean $terminate
	 * @param integer $statusCode
	 * @return void
	 */
	public function redirect($url,$terminate=true,$statusCode=302) {
	    header('Location: '.$url, true, $statusCode);
	    if($terminate) {
	    	die();
	    }
	}
	
	/**
	 * 创建链接
	 * @param string $route
	 * @param params $params
	 */
	public function createUrl($route, $params=array()) {
		$url = $this->getSiteUrl();
		if(substr($route, 0, 1)=='/') {
			$url.=$route;
		} else {
			$currentRouteInfo = $this->getRouteInfo();
			switch(substr_count($route, '/')) {
				case 2: // d/c/a
					$url.='/'.$route;
					break;
				case 0: // a
					$url.=isset($currentRouteInfo['d'])?'/'.$currentRouteInfo['d']:'';
					$url.='/'.$currentRouteInfo['c'].'/'.$route;
					break;
				case 1: // c/a
					$url.=isset($currentRouteInfo['d'])?'/'.$currentRouteInfo['d']:'';
					$url.='/'.$route;
					break;
			}
		}
		
		if($params) {
			$url .= '?'.http_build_query($params);
		}
		
		return $url;
		
	}
	
	/**
	 * 获取当前路由
	 * @return string
	 */
	public function getRoute() {
		$info = $this->getRouteInfo();
		return '/'.join('/', $info);
	}
	
	/**
	 * 获取当前uri的route信息
	 * @return array
	 */
	public function getRouteInfo($uri='') {
		if(!$uri) $uri = uri_string();
		$routeInfo = array();
		$path = WEB_ROOT.'/'.APPPATH.'controllers';
		$routeArray = explode('/', $uri);
		$count = count($routeArray);
		for($i=0; $i<3&&$i<$count; ++$i) {
			$name = $routeArray[$i];
			$path .= '/'.$name;
			$file = $path.'.php';
			if(file_exists($path)) {
				$routeInfo['d'] = $name;
			} else if(file_exists($file)) {
				$routeInfo['c'] = $name;
			} else {
				$routeInfo['a'] = $name;
			}
		}
		return $routeInfo;
	}
	
	/**
	 * 注册css文件
	 * @param string $file
	 * @return void
	 */
	public function registerCssFile($file) {
		if(!$this->_cssFiles->contain($file)) {
			$this->_cssFiles->push($file);
		}
	}
	
	/**
	 * 注册js文件
	 * @param string $file
	 * @return void
	 */
	public function registerJsFile($file) {
		if(!$this->_jsFiles->contain($file)) {
			$this->_jsFiles->push($file);
		}
	}
	
	/**
	 * 在指定位置注册一段JS代码
	 * @param string $id
	 * @param string $script
	 * @param string $position
	 * @return void
	 */
	public function registerScript($id, $script, $position=null) {
		if(!$this->_js->contains($id)) {
			$this->_js->add($id, $script);
		}
	}
	
	/**
	 * 获取当前链接地址
	 * @return string
	 */
	public function getCurrentUrl() {
		$url = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
		$url .= '://'. $_SERVER['HTTP_HOST'];
		$url .= $_SERVER['REQUEST_URI'];
		return $url;
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
		$data['controller'] = get_instance();
		ob_start();
		$this->load->view($view, $data);
		$output = ob_get_clean();
		if(!$this->_assetHeaderRendered) {
			$this->renderHeaderAssets($output);
		}
		if(!$this->_assetFooterRendered) {
			$this->renderFooterAssets($output);
		}
		echo $output;
	}
	
	/**
	 * 渲染头部js、css文件
	 * @param string $output
	 * @return void
	 */
	protected function renderHeaderAssets(&$output) {
		$count=0;
		$output=preg_replace('/(<title\b[^>]*>|<\\/head\s*>)/is','<###head###>$1',$output,1,$count);
		if($count) {
			$assetHtml = '';
			foreach($this->_cssFiles as $one) {
				$assetHtml .= "<link href='{$one}' type='text/css' rel='stylesheet' />\n";
			}
			foreach($this->_jsFiles as $one) {
				$assetHtml .= "<script type='text/javascript' src='{$one}'></script>\n";
			}
			$output=str_replace('<###head###>', $assetHtml, $output);
			$this->_assetHeaderRendered = true;
		}
	}
	
	/**
	 * 渲染底部js代码
	 * @param string $output
	 * @return void
	 */
	protected function renderFooterAssets(&$output) {
		$output=preg_replace('/(<\\/body\s*>)/is','<###end###>$1',$output,1,$fullPage);
		if($fullPage) {
			$assetHtml = '<script type="text/javascript">'."\n";
			foreach($this->_js as $block) {
				$assetHtml .= $block."\n";
			}
			$assetHtml .= '</script>'."\n";
			$output=str_replace('<###end###>', $assetHtml, $output);
			$this->_assetFooterRendered = true;
		}
	
	}
	
	/**
	 * 创建widget
	 * @param string $className
	 * @param array $properties
	 * @return Widget
	 */
	protected function createWidget($className, $properties = array()) {
		$properties['class'] = $className;
		$widget = \CI_Ext::createObject($properties);
		$widget->init();
		return $widget;
	}
	
	/**
	 * 直接渲染一个widget
	 * @param string $className
	 * @param array $properties
	 * @param boolean $captureOutput
	 * @return Widget
	 */
	public function widget($className, $properties = array(), $captureOutput=false) {
		if ($captureOutput) {
			ob_start ();
			ob_implicit_flush ( false );
			$widget = $this->createWidget ( $className, $properties );
			$widget->run ();
			return ob_get_clean ();
		} else {
			$widget = $this->createWidget ( $className, $properties);
			$widget->run();
			return $widget;
		}
	}
	
	/**
	 * 打开一个widget
	 * @param string $className
	 * @param array $properties
	 * @return Widget
	 */
	public function beginWidget($className,$properties=array()) {
		$widget=$this->createWidget($className,$properties);
		$this->_widgetStack[]=$widget;
		return $widget;
	}
	
	/**
	 * 闭合一个widget
	 * @param string $id
	 * @throws Exception
	 * @return void
	 */
	public function endWidget($id='') {
		if(($widget=array_pop($this->_widgetStack))!==null)
		{
			$widget->run();
			return $widget;
		}
		else
			throw new Exception(\CI_Ext::t('core','{controller} has an extra endWidget({id}) call in its view.',
				array('{controller}'=>get_class($this),'{id}'=>$id)));
	}

}

?>