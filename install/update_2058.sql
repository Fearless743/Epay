-- 数据库升级脚本 v2058
-- 性能优化：补充缺失的复合索引

-- pre_order 补充关键复合索引
ALTER TABLE `pre_order` ADD INDEX `idx_notify` (`notify`, `notifytime`, `deleted`);
ALTER TABLE `pre_order` ADD INDEX `idx_uid_type` (`uid`, `type`, `deleted`);
ALTER TABLE `pre_order` ADD INDEX `idx_uid_channel` (`uid`, `channel`, `deleted`);
ALTER TABLE `pre_order` ADD INDEX `idx_date_status` (`date`, `status`, `deleted`);
ALTER TABLE `pre_order` ADD INDEX `idx_addtime_status` (`addtime`, `status`, `deleted`);
ALTER TABLE `pre_order` ADD INDEX `idx_uid_addtime` (`uid`, `addtime`, `deleted`);
ALTER TABLE `pre_order` ADD INDEX `idx_uid_date_type` (`uid`, `date`, `type`, `deleted`);
ALTER TABLE `pre_order` ADD INDEX `idx_buyer_status` (`buyer`, `status`, `deleted`);
ALTER TABLE `pre_order` ADD INDEX `idx_ip_status` (`ip`, `status`, `deleted`);
ALTER TABLE `pre_order` ADD INDEX `idx_chan_sub` (`channel`, `subchannel`, `addtime`, `deleted`);

-- pre_user 补充索引（邀请返现 + 状态查询）
ALTER TABLE `pre_user` ADD INDEX `idx_upid` (`upid`);
ALTER TABLE `pre_user` ADD INDEX `idx_status` (`status`, `settle`);

-- pre_psreceiver 补充索引（分账回调）
ALTER TABLE `pre_psreceiver` ADD INDEX `idx_channel_uid_status` (`channel`, `uid`, `status`);
ALTER TABLE `pre_psreceiver` ADD INDEX `idx_channel_sub_status` (`channel`, `subchannel`, `status`);

-- pre_record 补充索引
ALTER TABLE `pre_record` ADD INDEX `idx_uid_type_trade` (`uid`, `type`, `trade_no`);

-- pre_transfer 补充索引
ALTER TABLE `pre_transfer` ADD INDEX `idx_uid_addtime` (`uid`, `addtime`, `status`);

-- pre_roll 补充索引
ALTER TABLE `pre_roll` ADD INDEX `idx_type_status` (`type`, `status`);

-- pre_channel 补充索引
ALTER TABLE `pre_channel` ADD INDEX `idx_type_status` (`type`, `status`);

-- pre_subchannel 补充索引
ALTER TABLE `pre_subchannel` ADD INDEX `idx_uid_status` (`uid`, `status`);

-- pre_complain 表（如果存在）补充索引
SET @sql_complain = (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pre_complain') > 0,
  'ALTER TABLE `pre_complain` ADD INDEX `idx_status_paytype_edittime` (`status`, `paytype`, `edittime`)',
  'SELECT 1'
));
PREPARE stmt_complain FROM @sql_complain; EXECUTE stmt_complain; DEALLOCATE PREPARE stmt_complain;

SET @sql_complain2 = (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'pre_complain') > 0,
  'ALTER TABLE `pre_complain` ADD INDEX `idx_uid_addtime` (`uid`, `addtime`)',
  'SELECT 1'
));
PREPARE stmt_complain2 FROM @sql_complain2; EXECUTE stmt_complain2; DEALLOCATE PREPARE stmt_complain2;