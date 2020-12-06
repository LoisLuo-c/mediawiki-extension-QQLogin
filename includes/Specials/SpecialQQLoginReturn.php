<?php
/**
 * SpecialQQLoginReturn implementation
 */
namespace QQLogin\Specials;

use QQLogin\Auth\QQPrimaryAuthenticationProvider;
use SpecialPage;
use UnlistedSpecialPage;

/**
 * QQ身份验证需要的永久重定向目标帮助特殊页面。使用适当的数据重定向到已经启动的身份验证工作流。
 */
class SpecialQQLoginReturn extends UnlistedSpecialPage {
	function __construct() {
		parent::__construct( 'QQLoginReturn' );
	}

	/**
	 * Special page executer
	 * @param string $par Subpage
	 */
	function execute( $par ) {
		$request = $this->getRequest();
		$session = $request->getSession();
		$out = $this->getOutput();
		$this->setHeaders();
		$authData = $session->getSecret( 'authData' );
		$token = $session->getToken( QQPrimaryAuthenticationProvider::TOKEN_SALT );
		$code = $request->getVal( 'code' );
		$redirectUrl =
			isset( $authData[QQPrimaryAuthenticationProvider::RETURNURL_SESSION_KEY] )
				? $authData[QQPrimaryAuthenticationProvider::RETURNURL_SESSION_KEY]
				: false;

		if ( !$redirectUrl || !$token->match( $request->getVal( 'state' ) ) ) {
			$out->redirect( SpecialPage::getTitleFor( 'Userlogin' )->getLocalURL() );
		}
		$code = $request->getVal( 'code' );
		//追加验证码
		if ( $code ) {
			$redirectUrl = wfAppendQuery( $redirectUrl, [ 'code' => $code ] );
		}
		//追加错误信息
		$error = $request->getVal( 'error' );
		if ( $error ) {
			$redirectUrl = wfAppendQuery( $redirectUrl, [ 'error' => $error ] );
		}
		$out->redirect( $redirectUrl );
	}

}
