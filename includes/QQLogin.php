<?php

namespace QQLogin;

use ConfigFactory;

/**
 * QQ登陆的接口，详情可参考官网：
 */
class QQLogin
{
	private static $mConfig;

	private $client_id = null;
	private $app_secret = null;
	private $redirect = null;

	public function __construct()
	{
		$glConfig = self::getGLConfig();
		$this->client_id = $glConfig->get('QQAppId');
		$this->app_secret = $glConfig->get('QQSecret');
		$this->redirect = $glConfig->get('QQRedirect');
	}


	/**
	 * 获取Authorization Code
	 * @return String QQ认证站点地址
	 */
	public function GetAuthorization($returnToUrl, $state)
	{
		$url = "https://graph.qq.com/oauth2.0/show?which=Login&display=pc
     &response_type=code&client_id=" . $this->client_id
			. "&redirect_uri=" . urlencode($this->redirect) . "&state=" . $state;
		return $url;
	}

	/**
	 * 获取openid
	 * @return Array 返回access_token和openid
	 */
	function GetQQVerifyInfo($code)
	{
		$ret = array();
		//根据code获取access_token
		$url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&"
			. "client_id=" . $this->client_id . "&redirect_uri=" . urlencode($this->redirect)
			. "&client_secret=" . $this->app_secret . "&code=" . $code;
		$response = $this->get_contents($url);
		$access_token = "";
		if ($response) {
			$arr = explode('&', $response);
			if (sizeof($arr) == 3) {
				$access_token = str_replace("access_token=", "", $arr[0]);
			} else {
				$access_token = null;
			}
		}
		if (empty($access_token)) {
			return null;
		}
		$ret['access_token'] = $access_token;

		//根据access_token获取openid
		$graph_url = "https://graph.qq.com/oauth2.0/me?access_token=" . $access_token;
		$str  = $this->get_contents($graph_url);
		if (strpos($str, "callback") !== false) {
			$lpos = strpos($str, "(");
			$rpos = strrpos($str, ")");
			$str  = substr($str, $lpos + 1, $rpos - $lpos - 1);
		}
		$user = json_decode($str);
		if (isset($user->error)) {
			return null;
		}
		$ret['openid'] = $user->openid;

		return $ret;
	}
	/**
	 * get_contents
	 * 服务器通过get请求获得内容
	 * @param string $url       请求的url,拼接后的
	 * @return string           请求返回的内容
	 */
	public function get_contents($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_URL, $url);
		$response =  curl_exec($ch);
		curl_close($ch);
		return $response;
	}


	/**
	 * 返回配置对象在QQLogin中使用。
	 * @return \Config
	 */
	public static function getGLConfig()
	{
		if (self::$mConfig === null) {
			self::$mConfig = ConfigFactory::getDefaultInstance()->makeConfig('qqlogin');
		}
		return self::$mConfig;
	}
}
