<?php

namespace QQLogin;

use User;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\IResultWrapper;

class QQUserMatching
{
	/**
	 * @var ILoadBalancer
	 */
	private $loadBalancer;

	public function __construct(ILoadBalancer $loadBalancer)
	{
		$this->loadBalancer = $loadBalancer;
	}

	/**
	 * @param array $token qq登陆成功后的凭证
	 * @return User|null 如果存在，则关联了用户，否则为null
	 */
	public function getUserFromToken(array $token)
	{
		$candidate = $this->userIdMatcher($token);
		if ($candidate instanceof User) {
			return $candidate;
		}
		return null;
	}

	/**
	 * @param User $user 要匹配的用户
	 * @param array $token qq登陆成功后的凭证
	 * @return bool 
	 */
	public function match(User $user, array $token)
	{
		if ($user->isAnon()) {
			return false;
		}
		if (!isset($token['openid'])) {
			return false;
		}
		$db = $this->loadBalancer->getConnection(DB_MASTER);
		return $db->insert(
			'user_qq_user',
			[
				'user_id' => $user->getId(),
				'user_qqid' => $token['openid'],
			]
		);
	}

	/**
	 * @param User $user
	 * @param array $token
	 * @return bool True
	 */
	public function unmatch(User $user, array $token)
	{
		if ($user->isAnon()) {
			return false;
		}
		if (!isset($token['openid'])) {
			return false;
		}

		$db = $this->loadBalancer->getConnection(DB_MASTER);

		return (bool)$db->delete(
			"user_qq_user",
			[
				'user_id' => $user->getId(),
				'user_qqid' => $token['openid'],
			],
			__METHOD__
		);
	}
	/**
	 * 是否存在qq关联的账户
	 * @return bool/User 
	 */
	private function userIdMatcher(array $token)
	{
		if (!isset($token['openid'])) {
			return null;
		}
		$db = $this->loadBalancer->getConnection(DB_MASTER);

		$s = $db->selectRow(
			'user_qq_user',
			['user_id'],
			['user_qqid' => $token['openid']],
			__METHOD__
		);

		if ($s !== false) {
			return User::newFromId($s->user_id);
		}
		return null;
	}

	/**
	 * 判断用户名是否未存在
	 */
	public function usernameNoExists($username)
	{
		if (!isset($username)) {
			return null;
		}
		$db = $this->loadBalancer->getConnection(DB_MASTER);
		$s = $db->select(
			'user',
			['user_id'],
			[
				'user_name' => $username,
			],
			__METHOD__
		);
		if ($s instanceof IResultWrapper && $s->numRows() === 0) {
			return true;
		}
		return null;
	}
}
