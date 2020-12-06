<?php

/**
 * QQServerAuthenticationRequest implementation
 */

namespace QQLogin\Auth;

use MediaWiki\Auth\AuthenticationRequest;

/**
 * QQServerAuthenticationRequest，该请求保存从QQ重定向到身份验证工作流所返回的数据。
 * 验证码或用户注册的用户名
 */
class QQServerAuthenticationRequest extends AuthenticationRequest
{
	/**
	 * 验证码由服务器提供。需要在授权流程的最后阶段发送回来。
	 * @var string
	 */
	public $authorizationCode;

	/**
	 * 身份验证失败时返回的错误代码
	 * @var string
	 */
	public $errorCode;

	/**
	 * 用户注册的用户名
	 * @var string
	 */
	public $qqUsername;

	public function getFieldInfo()
	{
		return [
			'error' => [
				'type' => 'string',
				'label' => wfMessage('qqlogin-param-error-label'),
				'help' => wfMessage('qqlogin-param-error-help'),
				'optional' => true,
			],
			'code' => [
				'type' => 'string',
				'label' => wfMessage('qqlogin-param-code-label'),
				'help' => wfMessage('qqlogin-param-code-help'),
				'optional' => true,
			],
			'qqusername' => [
				'type' => 'string',
				'label' => 'qqusername',
				'help' => 'init-qqusername',
				'optional' => true,
			],
		];
	}

	/**
	 * 在OAuth返回URL中从查询参数加载数据
	 * @param array $data 重定向地址带有的参数
	 * @return bool
	 */
	public function loadFromSubmission(array $data)
	{
		if (isset($data['code'])) {
			$this->authorizationCode = $data['code'];
			return true;
		}
		if (isset($data['qqusername'])) {
			$this->qqUsername = $data['qqusername'];
			return true;
		}

		if (isset($data['error'])) {
			$this->errorCode = $data['error'];
			return true;
		}
		return false;
	}
}
