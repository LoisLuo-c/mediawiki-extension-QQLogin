<?php

/**
 * QQAuthenticationRequest implementation
 */

namespace QQLogin\Auth;

use MediaWiki\Auth\ButtonAuthenticationRequest;

/**
 * 通过扩展ButtonAuthenticationRequest实现QQAuthenticationRequest，并描述此AuthenticationRequest需要的凭证。
 */
class QQAuthenticationRequest extends ButtonAuthenticationRequest
{
	public function __construct(\Message $label, \Message $help)
	{
		parent::__construct(
			QQPrimaryAuthenticationProvider::QQLOGIN_BUTTONREQUEST_NAME,
			$label,
			$help,
			true
		);
	}

	public function getFieldInfo()
	{
		return parent::getFieldInfo();
	}
}
