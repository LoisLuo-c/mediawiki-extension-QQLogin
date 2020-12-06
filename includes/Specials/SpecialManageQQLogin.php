<?php

/**
 * 绑定QQ与WIKI用户名
 */

namespace QQLogin\Specials;

use QQLogin\Constants;
use QQLogin\QQUserMatching;
use HTMLForm;
use SpecialPage;
use User;
use MediaWiki\MediaWikiServices;
use QQLogin\Auth\QQPrimaryAuthenticationProvider;


/**
 * 新用户注册用户名，用于QQ账户绑定wiki用户名
 */
class SpecialManageQQLogin extends SpecialPage
{
	private $userMatchingService = null;
	function __construct()
	{

		parent::__construct('ManageQQLogin', 'manageqqlogin');
		/** @var QQUserMatching*/
		$this->userMatchingService = MediaWikiServices::getInstance()
			->getService(Constants::SERVICE_QQ_USER_MATCHING);
	}

	public function doesWrites()
	{
		return true;
	}

	/**
	 * Special page executer
	 * @param SubPage $par Subpage
	 */
	function execute($par)
	{
		$out = $this->getOutput();
		$this->setHeaders();
		$out->addModules('mediawiki.userSuggest');
		$out->addModules('mediawiki.special.createaccount');
		$formFields = [
			'username' => [
				'class' => 'HTMLTextField',
				'id' => 'qqWikiUsername',
				'placeholder-message' => 'userlogin-yourname-ph',
				'validation-callback' => [$this, 'validateUserName'],
			],
		];
		$htmlForm = HTMLForm::factory('ooui', $formFields, $this->getContext(), 'qqlogin-manage');
		$htmlForm->setWrapperLegendMsg('qqlogin-wikiusername');
		$htmlForm->setSubmitCallback([$this, 'submitUserName']);
		$htmlForm->show();
	}
	/**
	 * 校验用户名是否符合规范
	 * @param username 用户输入的用户名
	 * @return bool
	 */
	public function validateUserName($username)
	{
		if ($this->userMatchingService && !empty($username)) {
			if ($this->userMatchingService->usernameNoExists(ucfirst($username))) {
				return true;
			} else {
				return $username . "名称已经存在！";
			}
		}
		return false;
	}

	/**
	 * @param array $data Formdata
	 * @return bool
	 */
	public function submitUserName(array $data)
	{
		$this->submitForm($data, false);
		return true;
	}
	/**
	 * 若输入的用户名符合规范，则跳转到验证身份工作流程
	 * @param array $data Formdata
	 * @return bool
	 */
	public function submitForm(array $data)
	{
		$request = $this->getRequest();
		$session = $request->getSession();
		$authData = $session->getSecret('authData');
		$out = $this->getOutput();
		$name = (isset($data['username']) ? $data['username'] : '');
		$redirectUrl =
			isset($authData[QQPrimaryAuthenticationProvider::RETURNURL_SESSION_KEY])
			? $authData[QQPrimaryAuthenticationProvider::RETURNURL_SESSION_KEY]
			: false;
		if ($name) {
			$redirectUrl = wfAppendQuery($redirectUrl, ['qqusername' => $name]);
		}
		if (!empty($name)) {
			$out->redirect($redirectUrl);
		}
		return false;
	}
}
