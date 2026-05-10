ALTER TABLE `pre_order`
ADD COLUMN `deleted` tinyint(1) NOT NULL DEFAULT '0';

ALTER TABLE `pre_settle`
ADD COLUMN `deleted` tinyint(1) NOT NULL DEFAULT '0';

ALTER TABLE `pre_transfer`
ADD COLUMN `deleted` tinyint(1) NOT NULL DEFAULT '0';

ALTER TABLE `pre_psorder`
ADD COLUMN `deleted` tinyint(1) NOT NULL DEFAULT '0';
