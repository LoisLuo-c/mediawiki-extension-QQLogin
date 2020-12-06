<?php
/**
 * QQUserInfoAuthenticationRequest implementation
 */

namespace QQLogin\Auth;

use MediaWiki\Auth\AuthenticationRequest;

/**
 *  保存QQ用户信息的身份验证请求。
 */
class QQUserInfoAuthenticationRequest extends AuthenticationRequest {
	public $userInfo;

	public function __construct( $userInfo ) {
		$this->userInfo = $userInfo;
	}
	public function getFieldInfo() {
		return [];
	}
}
