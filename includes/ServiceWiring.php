<?php

use QQLogin\Constants;
use QQLogin\QQUserMatching;
use MediaWiki\MediaWikiServices;

return [
	Constants::SERVICE_QQ_USER_MATCHING => function ( MediaWikiServices $services ) {
		return new QQUserMatching( $services->getDBLoadBalancer() );
	}
];
