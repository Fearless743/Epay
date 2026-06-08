ALTER TABLE `pre_user` ADD `usdt_chain` varchar(128) DEFAULT NULL AFTER `account`;
ALTER TABLE `pre_settle` ADD `usdt_chain` varchar(128) DEFAULT NULL AFTER `account`;
