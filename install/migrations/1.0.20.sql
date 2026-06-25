-- VanShine 1.0.20：管理员自定义头像字段
ALTER TABLE `{prefix}admin`
    ADD COLUMN `avatar_url` varchar(500) NOT NULL DEFAULT '' COMMENT '自定义头像链接' AFTER `email`;
