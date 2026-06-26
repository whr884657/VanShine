-- VanShine 1.0.35：本地储存对外直链随机 slug
INSERT INTO `config` (`key`, `value`) VALUES
('storage_local_public_slug', '')
ON DUPLICATE KEY UPDATE `key` = `key`;
