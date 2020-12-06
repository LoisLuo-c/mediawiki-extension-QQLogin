<?php

namespace QQLogin;

use QQLogin\HtmlForm\HTMLQQLoginButtonField;

class QQLoginHooks {
	public static function onUserLogoutComplete() {
		global $wgRequest;
		if ( $wgRequest->getSessionData( 'access_token' ) !== null ) {
			$wgRequest->setSessionData( 'access_token', null );
		}
	}
	/**
	 * 加大qq登陆按钮的权重
	 * 隐藏原有的登陆方式，只保留QQ按钮登陆
	 * @param array $requests AuthenticationRequests for the current auth attempt
	 * @param array $fieldInfo Array of field information
	 * @param array &$formDescriptor Array of fields in a descriptor format
	 * @param string $action one of the AuthManager::ACTION_* constants.
	 */
	public static function onAuthChangeFormFields(
		array $requests, array $fieldInfo, array &$formDescriptor, $action
	) {
		if ( isset( $formDescriptor['qqlogin'] ) ) {
			
			$formDescriptor['qqlogin'] = array_merge( $formDescriptor['qqlogin'], [
				'weight' => 101,
				'flags' => [],
				'class' => HTMLQQLoginButtonField::class,
			] );
			unset( $formDescriptor['qqlogin']['type'] );

			$formdata['rememberMe']=$formDescriptor['rememberMe'];
			$formdata['qqlogin']=$formDescriptor['qqlogin'];
			$formDescriptor=$formdata;
		}
	}

}
