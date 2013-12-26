<?php
namespace UView;
/**
 * 主页
 */
class Home extends \Core\Page{
	/**
	 * 构造器，设置页面类型
	 */
	function __construct(){
		header("Content-Type: text/html; charset=utf-8");
		lib()->load('menu')->add('menu', new \ULib\Menu());
	}

	/**
	 * 默认首页
	 */
	public function main(){
		if(u()->getUriInfo()->getUrlListLast() !== null){
			$this->__load_404();
		} else{
			theme()->set_desc(site_title(false) . " - " . site_desc(false));
			theme()->set_keywords(site_keywords());
			$this->__view('comm/header.php');
			$this->__view('home/home.php');
			$this->__view('comm/footer.php');
		}
	}

	/**
	 * 登录页面
	 * @param int $code
	 */
	public function login($code = 0){
		$this->__view('comm/header.php');
		if(is_login()){
			$this->__view('home/logged.php');
		} else{
			$this->__view('home/login.php');
		}
		$this->__view('comm/footer.php');
	}

	/**
	 * 登出页面
	 */
	public function logout(){
		user()->logout();
		hook()->apply('Home_logout', null);
		redirect(user()->login_go());
	}

	/**
	 * 用户登录POST页面
	 */
	public function post(){
		if(!req()->is_post()){
			$this->__load_404();
		} else{
			hook()->apply('Home_post_login', null);
			user()->login(req()->post('user'), req()->post('password'), req()->post('remember'));
			if(user()->login_status()){
				hook()->apply('Home_post_login_success', null);
				redirect(user()->login_go());
			} else{
				hook()->apply('Home_post_login_error', null);
				redirect(get_url(array(
					'Home',
					'login'
				), "?code=" . user()->login_code()));
			}
		}
	}
}