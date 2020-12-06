--
-- qq登陆关联wiki用户表
--
CREATE TABLE /*$wgDBprefix*/user_qq_user (
  user_qqid varchar(255) NOT NULL PRIMARY KEY,
  user_id int(10) unsigned NOT NULL,
  KEY(user_id)
) /*$wgDBTableOptions*/;
