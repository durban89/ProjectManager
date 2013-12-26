<?php
namespace Core;
if(!defined('_CorePath_'))
	exit;
/**
 * 路由器规则类，必须由PATH_INFO提供支持
 */
class Uri{
	/**
	 * @var UriInfo URI信息类实例
	 */
	private $_uriInfo;

	/**
	 * @var bool 页面404标记
	 */
	private $_flag_404;

	/**
	 * @var array 404页面存放路由位置
	 */
	private $_list_404;

	/**
	 * 构造函数
	 */
	public function __construct(){
		// TODO: Implement __construct() method.
		$this->_uriInfo = new UriInfo();
		$this->_flag_404 = false;
	}

	/**
	 * 设置404的页面
	 * @param array $list_404
	 */
	public function setList404($list_404){
		$this->_list_404 = $list_404;
	}


	/**
	 * 加载默认页面和默认404页面
	 * @param array $home
	 * @param array $page_404 可以留空默认
	 */
	public function home($home, $page_404 = array()){
		$this->_uriInfo->process(); //处理路由信息
		$this->setList404($page_404);
		$list = $this->_uriInfo->getUrlList();
		if(count($list) == 0){
			call_user_func_array(array(
				$this,
				'load'
			), $home);
		} else{
			call_user_func_array(array(
				$this,
				'load'
			), $list);
		}
	}

	/**
	 * 获取404信息
	 * @return array
	 */
	public function getList404(){
		return $this->_list_404;
	}

	/**
	 * 加载页面
	 * @return bool
	 */
	public function load(){
		$list = func_get_args();
		$path = _PagePath_;
		foreach($list as $id => $v){
			if(is_file($path . "/" . $v . '.php')){
				require_once($path . "/" . $v . '.php');
				$class_name = "UView\\$v";
				if(class_exists($class_name)){
					$page = new $class_name;
					if(get_class($page) !== $class_name){
						break;
					}
					$methods = get_class_methods($page);
					if(isset($list[$id + 1])){
						if(substr($list[$id + 1], 0, 2) == "--" || in_array($list[$id + 1], $methods)){
							hook()->apply('Uri_load_begin', null);
							@call_user_func_array(array(
								$page,
								$list[$id + 1]
							), array_slice($list, 2));
							hook()->apply('Uri_load_end', null);
							return true;
						}
					} else{
						if(in_array('main', $methods)){
							hook()->apply('Uri_load_begin', null);
							$page->main();
							hook()->apply('Uri_load_end', null);
							return true;
						}
					}
				}
			} elseif(is_dir($path . "/" . $v)){
				$path .= "/" . $v;
			} else{
				break;
			}
		}
		if(!$this->_flag_404){
			//当再次加载404页面跳过
			$this->load_404();
		}
		return false;
	}

	/**
	 * 加载404页面
	 */
	public function load_404(){
		cfg()->set(array(
			'system',
			'is_404'
		), true);
		hook()->apply('Uri_load_404', null);
		$this->_flag_404 = true;
		if(empty($this->_list_404) || call_user_func_array(array(
			$this,
			'load'
		), $this->_list_404) === false
		){
			header("HTTP/1.1 404 Not Found");
			include(_CorePath_ . "/view/404.html");
		}
		$this->_flag_404 = false;
	}

	/**
	 * @return UriInfo
	 */
	public function &getUriInfo(){
		return $this->_uriInfo;
	}

}

/**
 * 路由信息
 */
class UriInfo{
	/**
	 * 路由路径
	 * @var string
	 */
	private $path;

	/**
	 * 分割后的路由列表
	 * @var array
	 */
	private $url_list = array();

	/**
	 * @var string HTTP_HOST信息
	 */
	private $http_host;

	/**
	 * @var int HTTP端口
	 */
	private $http_port;

	/**
	 * @var string 使用的协议名
	 */
	private $protocol_name;

	/**
	 * 路由构造方法
	 */
	function __construct(){
		// TODO: Implement __construct() method.
		$this->make_req();
		$this->init_param();
		$this->make_list();
	}

	/**
	 * 生成路径构造列表
	 */
	private function make_list(){
		if($this->path == '' || '/' == $this->path || $_SERVER['SCRIPT_NAME'] == $this->path)
			return array();
		if(substr($this->path, 0, 1) != '/'){
			$this->path = "/" . $this->path;
		}
		if(substr($this->path, -1) == '/'){
			$this->url_list = explode(ROUTER_SPLIT_CHAR, substr($this->path, 1, -1));
		} else{
			$this->url_list = explode(ROUTER_SPLIT_CHAR, substr($this->path, 1));
		}
	}

	/**
	 * 产生初始化的网址信息
	 */
	private function init_param(){
		$this->protocol_name = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https' : 'http';
		$this->http_port = $_SERVER['SERVER_PORT'];
		$i = strrpos($_SERVER["HTTP_HOST"], ':');
		if($i === false || $i + 1 < strlen($_SERVER["HTTP_HOST"])){
			$this->http_host = $_SERVER["HTTP_HOST"];
		} else{
			if(substr($_SERVER["HTTP_HOST"], $i + 1) == $this->http_port){
				$this->http_host = substr($_SERVER["HTTP_HOST"], 0, $i);
			}
		}
		define('URL_NOW', $this->protocol_name . "://" . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI']);
		define('URL_PATH', str_replace("\\", "/", dirname($_SERVER['SCRIPT_NAME'])) . "");
		$i = strpos($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']);
		if($i === 0){
			$url_web = $this->protocol_name . "://" . $_SERVER["HTTP_HOST"] . $this->clean_url_more_char($_SERVER['SCRIPT_NAME'] . "/");
			$url_file = $this->protocol_name . "://" . $_SERVER["HTTP_HOST"] . $this->clean_url_more_char(dirname($_SERVER['SCRIPT_NAME']) . "/");
		} else{
			$url_web = $this->protocol_name . "://" . $_SERVER["HTTP_HOST"] . $this->clean_url_more_char(dirname($_SERVER['SCRIPT_NAME']) . "/");
			$url_file = $url_web;
		}
		define('URL_WEB', $url_web);
		define('URL_FILE', $url_file);
	}

	/**
	 * 清除网址中多余的字符
	 * @param string $url
	 * @return mixed
	 */
	private function clean_url_more_char($url){
		return preg_replace("/[\/\\\]+/", "/", trim($url));
	}

	/**
	 * 获取请求的路径信息，并进行相应处理
	 */
	private function make_req(){
		if(!isset($_SERVER['PATH_INFO'])){
			$i = strpos($_SERVER['REQUEST_URI'], '?');
			if($i === false){
				$this->path = $_SERVER['REQUEST_URI'];
			} else{
				$this->path = substr($_SERVER['REQUEST_URI'], 0, $i);
			}
			$dir = dirname($_SERVER['SCRIPT_NAME']);
			$j = strpos($this->path, $dir);
			if($j === 0){
				$this->path = substr($this->path, strlen($dir));
			}
		} else{
			$this->path = $_SERVER['PATH_INFO'];
		}
		$this->path = trim($this->path);
	}

	/**
	 * 返回HTTP域名
	 *
	 * @return string
	 */
	public function getHttpHost(){
		return $this->http_host;
	}

	/**
	 * 返回HTTP端口
	 *
	 * @return int
	 */
	public function getHttpPort(){
		return $this->http_port;
	}

	/**
	 * 返回路径
	 * @return string
	 */
	public function getPath(){
		return $this->path;
	}

	/**
	 * 返回协议名
	 * @return string
	 */
	public function getProtocolName(){
		return $this->protocol_name;
	}

	/**
	 * 返回URI信息列表
	 *
	 * @return array
	 */
	public function getUrlList(){
		return $this->url_list;
	}

	/**
	 * 返回URI最后一个信息
	 *
	 * @return string|null
	 */
	public function getUrlListLast(){
		if(($i = count($this->url_list)) == 0 || !isset($this->url_list[$i - 1]))
			return null;
		return $this->url_list[$i - 1];
	}

	/**
	 * 对路由信息进行二次处理
	 */
	public function process(){
		$this->url_list = hook()->apply('UriInfo_process', $this->url_list);
	}
}

?>