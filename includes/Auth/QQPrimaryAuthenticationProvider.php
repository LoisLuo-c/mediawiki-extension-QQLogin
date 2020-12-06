<?php

/**
 * QQPrimaryAuthenticationProvider implementation
 */

namespace QQLogin\Auth;

use Exception;
use QQLogin\Constants;
use QQLogin\QQLogin;
use QQLogin\QQUserMatching;
use MediaWiki\Auth\AbstractPrimaryAuthenticationProvider;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Auth\AuthManager;
use MediaWiki\MediaWikiServices;
use MWException;
use SpecialPage;
use StatusValue;
use User;

/**
 * 实现QQ授权登陆认证流程
 * 在身份验证开始时，会将用户重定向到外部身份验证提供者(QQ)，以便在对用户进行实际身份验证之前允许访问外部帐户的数据。
 * */
class QQPrimaryAuthenticationProvider extends AbstractPrimaryAuthenticationProvider
{
	/** 保存原始重定向URL */
	const RETURNURL_SESSION_KEY = 'qqLoginReturnToUrl';
	/** 当用户从QQ被重定向时，QQLogin使用的CSRF */
	const TOKEN_SALT = 'QQPrimaryAuthenticationProvider:redirect';
	/** qq登陆按钮的名称 */
	const QQLOGIN_BUTTONREQUEST_NAME = 'qqlogin';
	/** @var string 会话数据键，用于在请求之间识别已保存的QQ授权的access_token和openid */
	const QQ_ACCOUNT_TOKEN_KEY = 'qqlogin:account:token';

	public function beginPrimaryAuthentication(array $reqs)
	{
		return $this->beginQQAuthentication($reqs, self::QQLOGIN_BUTTONREQUEST_NAME);
	}

	public function continuePrimaryAuthentication(array $reqs)
	{
		$request =
			AuthenticationRequest::getRequestByClass(
				$reqs,
				QQServerAuthenticationRequest::class
			);
		if (!$request) {
			//请求了继续已经开始的QQ身份验证工作流，但这里没有已经开始的工作流。请通过尝试重新登录，开始新的身份验证工作流。
			return AuthenticationResponse::newFail(
				wfMessage('qqlogin-error-no-authentication-workflow')
			);
		}

		try {
			$verifiedToken = $this->manager->getAuthenticationSessionData(self::QQ_ACCOUNT_TOKEN_KEY);
			//如果qq还没有关联用户名，则继续QQ登陆
			if (empty($verifiedToken)) {
				$verifiedToken = $this->getVerifiedToken($request);
				if ($verifiedToken instanceof AuthenticationResponse) {
					return $verifiedToken;
				}
			}

			/** @var QQUserMatching $userMatchingService */
			$userMatchingService =
				MediaWikiServices::getInstance()->getService(Constants::SERVICE_QQ_USER_MATCHING);
			$user = $userMatchingService->getUserFromToken($verifiedToken);

			//如果根据openid能查询到用户，证明已经关联用户
			if ($user) {
				return AuthenticationResponse::newPass($user->getName());
			} else {
				//用户名不存在，跳转链接，让用户注册用户名
				if (empty($request->qqUsername)) {
					$this->manager->setAuthenticationSessionData(
						self::QQ_ACCOUNT_TOKEN_KEY,
						$verifiedToken
					);
					return AuthenticationResponse::newRedirect([
						new QQServerAuthenticationRequest($verifiedToken),
					], SpecialPage::getTitleFor('ManageQQLogin')
						->getFullURL('', false, PROTO_CURRENT));
				}
				$resp = AuthenticationResponse::newPass($request->qqUsername);
				$resp->linkRequest = new QQUserInfoAuthenticationRequest($verifiedToken);
				$resp->createRequest = $resp->linkRequest;

				return $resp;
			}
		} catch (Exception $e) {
			return AuthenticationResponse::newFail(wfMessage(
				'qqlogin-generic-error',
				$e->getMessage()
			));
		}
	}

	public function autoCreatedAccount($user, $source)
	{
		/** @var QQUserMatching $userMatchingService */
		$userMatchingService =
			MediaWikiServices::getInstance()->getService(Constants::SERVICE_QQ_USER_MATCHING);

		$verifiedToken =
			$this->manager->getAuthenticationSessionData(self::QQ_ACCOUNT_TOKEN_KEY);
		$userMatchingService->match($user, $verifiedToken);
		$this->manager->removeAuthenticationSessionData(self::QQ_ACCOUNT_TOKEN_KEY);
	}

	public function getAuthenticationRequests($action, array $options)
	{
		switch ($action) {
			case AuthManager::ACTION_LOGIN:
				return [
					new QQAuthenticationRequest(
						wfMessage('qqlogin'),
						wfMessage('qqlogin-loginbutton-help')
					),
				];
				break;
			default:
				return [];
		}
	}

	public function testUserExists($username, $flags = User::READ_NORMAL)
	{
		return false;
	}

	public function providerAllowsAuthenticationDataChange(
		AuthenticationRequest $req,
		$checkData = true
	) {
		return StatusValue::newGood('ignored');
	}

	public function providerChangeAuthenticationData(AuthenticationRequest $req)
	{
	}

	public function providerNormalizeUsername($username)
	{
		return null;
	}

	public function accountCreationType()
	{
		return self::TYPE_LINK;
	}

	public function beginPrimaryAccountCreation($user, $creator, array $reqs)
	{
		return $this->beginQQAuthentication($reqs, self::QQLOGIN_BUTTONREQUEST_NAME);
	}

	public function finishAccountCreation($user, $creator, AuthenticationResponse $response)
	{
		return null;
	}

	public function beginPrimaryAccountLink($user, array $reqs)
	{
		return $this->beginQQAuthentication($reqs, self::QQLOGIN_BUTTONREQUEST_NAME);
	}

	/**
	 * todowg
	 * 主身份验证的处理程序，当前开始。检查身份验证请求是否可以由QQLogin处理，
	 * 如果可以，返回一个重定向到QQ的外部身份验证站点的AuthenticationResponse，否则返回弃权响应。
	 * @param array $reqs
	 * @param string $buttonAuthenticationRequestName
	 * @return AuthenticationResponse
	 */
	private function beginQQAuthentication(array $reqs, $buttonAuthenticationRequestName)
	{
		$req =
			QQAuthenticationRequest::getRequestByName(
				$reqs,
				$buttonAuthenticationRequestName
			);
		if (!$req) {
			return AuthenticationResponse::newAbstain();
		}
		$this->manager->setAuthenticationSessionData(
			self::RETURNURL_SESSION_KEY,
			$req->returnToUrl
		);
		$QQLogin = new QQLogin();
		$clientUrl = $QQLogin->GetAuthorization(SpecialPage::getTitleFor('QQLoginReturn')
			->getFullURL('', false, PROTO_CURRENT), $this->manager->getRequest()
			->getSession()
			->getToken(self::TOKEN_SALT)
			->toString());

		return AuthenticationResponse::newRedirect([
			new QQServerAuthenticationRequest(),
		], $clientUrl);
	}

	/**
	 * 从QQServerAuthenticationRequest创建一个新的经过身份验证的QQ服务。
	 * 获取到access_token和openid
	 * @param QQServerAuthenticationRequest $request
	 * @return array|AuthenticationResponse
	 * @throws MWException
	 */
	private function getVerifiedToken(QQServerAuthenticationRequest $request)
	{
		if (!$request->authorizationCode || $request->errorCode) {
			switch ($request->errorCode) {
				case 'access_denied':
					return AuthenticationResponse::newFail(wfMessage('qqlogin-access-denied'));
					break;
				default:
					return AuthenticationResponse::newFail(wfMessage(
						'qqlogin-generic-error',
						$request->errorCode ? $request->errorCode : 'unknown'
					));
			}
		}
		$QQLogin = new QQLogin();
		$verifiedToken = $QQLogin->GetQQVerifyInfo($request->authorizationCode);

		if ($verifiedToken === null) {
			throw new MWException('The access_token could not be verified.');
		}
		return $verifiedToken;
	}
}
