-- WineHQ AppDB Table Structure

--
-- Table structure for table `appBundle`
--

DROP TABLE IF EXISTS `appBundle`;
CREATE TABLE `appBundle` (
  `bundleId` int(11) NOT NULL DEFAULT 0,
  `appId` int(11) NOT NULL DEFAULT 0,
  KEY `bundleId` (`bundleId`),
  KEY `appId` (`appId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `appCategory`
--

DROP TABLE IF EXISTS `appCategory`;
CREATE TABLE `appCategory` (
  `catId` int(11) NOT NULL AUTO_INCREMENT,
  `catName` varchar(64) NOT NULL DEFAULT '',
  `catDescription` text DEFAULT NULL,
  `catParent` int(11) DEFAULT 0,
  KEY `catId` (`catId`),
  KEY `catName` (`catName`),
  KEY `catParent` (`catParent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `appComments`
--

DROP TABLE IF EXISTS `appComments`;
CREATE TABLE `appComments` (
  `time` datetime DEFAULT NULL,
  `commentId` int(11) NOT NULL AUTO_INCREMENT,
  `parentId` int(11) DEFAULT 0,
  `versionId` int(11) DEFAULT 0,
  `userId` int(11) DEFAULT NULL,
  `subject` varchar(128) DEFAULT NULL,
  `body` text DEFAULT NULL,
  KEY `commentId` (`commentId`),
  KEY `versionId` (`versionId`),
  KEY `parentId` (`parentId`),
  KEY `userId` (`userId`),
  KEY `subject` (`subject`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `appData`
--

DROP TABLE IF EXISTS `appData`;
CREATE TABLE `appData` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appId` int(11) NOT NULL DEFAULT 0,
  `versionId` int(11) DEFAULT 0,
  `type` enum('screenshot','url','bug','downloadurl') DEFAULT NULL,
  `description` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `submitTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `submitterId` int(11) NOT NULL DEFAULT 0,
  `state` enum('accepted','queued','rejected') NOT NULL DEFAULT 'accepted',
  `testedVersion` text NOT NULL DEFAULT '',
  KEY `id` (`id`),
  KEY `versionId` (`versionId`),
  KEY `type` (`type`),
  KEY `submitTime` (`submitTime`),
  KEY `state` (`state`),
  KEY `testedVersion` (`testedVersion`(3072))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `appFamily`
--

DROP TABLE IF EXISTS `appFamily`;
CREATE TABLE `appFamily` (
  `appId` int(11) NOT NULL AUTO_INCREMENT,
  `appName` varchar(100) NOT NULL DEFAULT '',
  `vendorId` int(11) NOT NULL DEFAULT 0,
  `keywords` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `webPage` varchar(100) DEFAULT NULL,
  `catId` int(11) DEFAULT NULL,
  `submitTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `submitterId` int(11) NOT NULL DEFAULT 0,
  `state` enum('accepted','queued','rejected','deleted') NOT NULL DEFAULT 'accepted',
  `hasMaintainer` enum('true','false') NOT NULL DEFAULT 'false',
  KEY `appId` (`appId`),
  KEY `appName` (`appName`),
  KEY `vendorId` (`vendorId`),
  KEY `keywords` (`keywords`(3072)),
  KEY `catId` (`catId`),
  KEY `submitTime` (`submitTime`),
  KEY `submitterId` (`submitterId`),
  KEY `state` (`state`),
  KEY `hasMaintainer` (`hasMaintainer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `appMaintainers`
--

DROP TABLE IF EXISTS `appMaintainers`;
CREATE TABLE `appMaintainers` (
  `maintainerId` int(11) NOT NULL AUTO_INCREMENT,
  `appId` int(11) DEFAULT NULL,
  `versionId` int(11) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `maintainReason` text DEFAULT NULL,
  `superMaintainer` tinyint(1) DEFAULT NULL,
  `submitTime` datetime DEFAULT NULL,
  `notificationLevel` int(11) NOT NULL DEFAULT 0,
  `notificationTime` datetime DEFAULT NULL,
  `state` enum('accepted','queued','pending') NOT NULL DEFAULT 'accepted',
  KEY `maintainerId` (`maintainerId`),
  KEY `appId` (`appId`),
  KEY `versionId` (`versionId`),
  KEY `userId` (`userId`),
  KEY `superMaintainer` (`superMaintainer`),
  KEY `submitTime` (`submitTime`),
  KEY `notificationLevel` (`notificationLevel`),
  KEY `notificationTime` (`notificationTime`),
  KEY `state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `appMonitors`
--

DROP TABLE IF EXISTS `appMonitors`;
CREATE TABLE `appMonitors` (
  `monitorId` int(11) NOT NULL AUTO_INCREMENT,
  `appId` int(11) NOT NULL DEFAULT 0,
  `versionId` int(11) NOT NULL DEFAULT 0,
  `submitTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `userId` int(11) NOT NULL DEFAULT 0,
  KEY `monitorId` (`monitorId`),
  KEY `appId` (`appId`),
  KEY `versionId` (`versionId`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `appNotes`
--

DROP TABLE IF EXISTS `appNotes`;
CREATE TABLE `appNotes` (
  `noteId` int(11) NOT NULL AUTO_INCREMENT,
  `noteTitle` varchar(255) DEFAULT NULL,
  `noteDesc` text DEFAULT NULL,
  `versionId` int(11) NOT NULL DEFAULT 0,
  `appId` int(11) NOT NULL,
  `submitterId` int(11) NOT NULL DEFAULT 0,
  `submitTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `linkedWith` int(11) NOT NULL,
  `type` enum('note','warning','howto') NOT NULL DEFAULT 'note',
  KEY `noteId` (`noteId`),
  KEY `noteTitle` (`noteTitle`),
  KEY `versionId` (`versionId`),
  KEY `appId` (`appId`),
  KEY `submitterId` (`submitterId`),
  KEY `submitTime` (`submitTime`),
  KEY `linkedWith` (`linkedWith`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `appVersion`
--

DROP TABLE IF EXISTS `appVersion`;
CREATE TABLE `appVersion` (
  `versionId` int(11) NOT NULL AUTO_INCREMENT,
  `appId` int(11) NOT NULL DEFAULT 0,
  `versionName` varchar(100) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `rating` text DEFAULT NULL,
  `ratingRelease` text DEFAULT NULL,
  `submitTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `submitterId` int(11) NOT NULL DEFAULT 0,
  `license` enum('Retail','Subscription','Open Source','Demo','Shareware','Free to use','Free to use and share') DEFAULT NULL,
  `obsoleteBy` int(11) NOT NULL DEFAULT 0,
  `state` enum('accepted','queued','rejected','pending','deleted') NOT NULL DEFAULT 'accepted',
  `hasMaintainer` enum('true','false') NOT NULL DEFAULT 'false',
  KEY `versionId` (`versionId`),
  KEY `appId` (`appId`),
  KEY `versionName` (`versionName`),
  KEY `rating` (`rating`(3072)),
  KEY `ratingRelease` (`ratingRelease`(3072)),
  KEY `submitTime` (`submitTime`),
  KEY `license` (`license`),
  KEY `state` (`state`),
  KEY `hasMaintainer` (`hasMaintainer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `appVotes`
--

DROP TABLE IF EXISTS `appVotes`;
CREATE TABLE `appVotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `versionId` int(11) NOT NULL DEFAULT 0,
  `userId` int(11) NOT NULL DEFAULT 0,
  `slot` int(11) NOT NULL DEFAULT 0,
  KEY `id` (`id`),
  KEY `userId` (`userId`),
  KEY `versionId` (`versionId`),
  KEY `time` (`time`),
  KEY `slot` (`slot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `buglinks`
--

DROP TABLE IF EXISTS `buglinks`;
CREATE TABLE `buglinks` (
  `linkId` int(11) NOT NULL AUTO_INCREMENT,
  `bug_id` int(11) NOT NULL DEFAULT 0,
  `versionId` int(11) NOT NULL DEFAULT 0,
  `submitTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `submitterId` int(11) NOT NULL DEFAULT 0,
  `state` enum('accepted','queued') NOT NULL DEFAULT 'accepted',
  KEY `linkId` (`linkId`),
  KEY `bug_id` (`bug_id`),
  KEY `versionId` (`versionId`),
  KEY `submitTime` (`submitTime`),
  KEY `submitterId` (`submitterId`),
  KEY `state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `commonReplies`
--

DROP TABLE IF EXISTS `commonReplies`;
CREATE TABLE `commonReplies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state` enum('accepted','queued','rejected','pending','deleted') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'accepted',
  `reply` text COLLATE utf8_unicode_ci DEFAULT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `debug`
--

DROP TABLE IF EXISTS `debug`;
CREATE TABLE `debug` (
  `time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `message` text DEFAULT NULL,
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `distributions`
--

DROP TABLE IF EXISTS `distributions`;
CREATE TABLE `distributions` (
  `distributionId` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `submitTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `submitterId` int(11) NOT NULL DEFAULT 0,
  `state` enum('accepted','queued','deleted') NOT NULL DEFAULT 'accepted',
  KEY `distributionId` (`distributionId`),
  KEY `name` (`name`),
  KEY `submitTime` (`submitTime`),
  KEY `submitterId` (`submitterId`),
  KEY `state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `error_log`
--

DROP TABLE IF EXISTS `error_log`;
CREATE TABLE `error_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `submitTime` datetime DEFAULT NULL,
  `userid` int(11) NOT NULL DEFAULT 0,
  `type` enum('sql_error','general_error') COLLATE utf8_unicode_ci DEFAULT NULL,
  `log_text` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `request_text` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `deleted` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `prefs_list`
--

DROP TABLE IF EXISTS `prefs_list`;
CREATE TABLE `prefs_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) DEFAULT NULL,
  `def_value` text DEFAULT NULL,
  `value_list` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `show_for_group` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `show_for_group` (`show_for_group`(3072))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `tags_CommonReply`
--

DROP TABLE IF EXISTS `tags_CommonReply`;
CREATE TABLE `tags_CommonReply` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state` enum('accepted','queued','rejected','pending','deleted') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'accepted',
  `textId` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `multipleAssignments` tinyint(4) NOT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `tags_CommonReply_assignments`
--

DROP TABLE IF EXISTS `tags_CommonReply_assignments`;
CREATE TABLE `tags_CommonReply_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state` enum('accepted','queued','rejected','pending','deleted') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'accepted',
  `tagId` int(11) NOT NULL,
  `taggedId` int(11) NOT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `tags_NoteVersion_assignments`
--

DROP TABLE IF EXISTS `tags_NoteVersion_assignments`;
CREATE TABLE `tags_NoteVersion_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state` enum('accepted','queued','rejected','pending','deleted') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'accepted',
  `tagId` int(11) NOT NULL,
  `taggedId` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `testResults`
--

DROP TABLE IF EXISTS `testResults`;
CREATE TABLE `testResults` (
  `testingId` int(11) NOT NULL AUTO_INCREMENT,
  `versionId` int(11) NOT NULL DEFAULT 0,
  `whatWorks` text DEFAULT NULL,
  `whatDoesnt` text DEFAULT NULL,
  `whatNotTested` text DEFAULT NULL,
  `testedDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `distributionId` int(11) NOT NULL DEFAULT 0,
  `testedRelease` tinytext DEFAULT NULL,
  `staging` tinyint(1) NOT NULL DEFAULT 0,
  `installs` enum('Yes','No','No, but has workaround','N/A') NOT NULL DEFAULT 'Yes',
  `runs` enum('Yes','No','Not installable') NOT NULL DEFAULT 'Yes',
  `usedWorkaround` enum('Yes','No') DEFAULT 'No',
  `workarounds` text DEFAULT NULL,
  `testedRating` enum('Platinum','Gold','Silver','Bronze','Garbage') NOT NULL DEFAULT 'Platinum',
  `comments` text DEFAULT NULL,
  `submitTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `submitterId` int(11) NOT NULL DEFAULT 0,
  `state` enum('accepted','queued','rejected','pending','deleted') NOT NULL DEFAULT 'accepted',
  `gpuMfr` enum('AMD','Intel','Nvidia','Other','Unknown') DEFAULT NULL,
  `graphicsDriver` enum('open source','proprietary','unknown') DEFAULT NULL,
  `hasProblems` enum('Yes','No') DEFAULT NULL,
  KEY `testingId` (`testingId`),
  KEY `versionId` (`versionId`),
  KEY `testedDate` (`testedDate`),
  KEY `distributionId` (`distributionId`),
  KEY `testedRelease` (`testedRelease`(255)),
  KEY `staging` (`staging`),
  KEY `installs` (`installs`),
  KEY `runs` (`runs`),
  KEY `usedWorkaround` (`usedWorkaround`),
  KEY `testedRating` (`testedRating`),
  KEY `submitTime` (`submitTime`),
  KEY `submitterId` (`submitterId`),
  KEY `state` (`state`),
  KEY `gpuMfr` (`gpuMfr`),
  KEY `graphicsDriver` (`graphicsDriver`),
  KEY `hasProblems` (`hasProblems`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `user_list`
--

DROP TABLE IF EXISTS `user_list`;
CREATE TABLE `user_list` (
  `stamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `userid` int(11) NOT NULL AUTO_INCREMENT,
  `password` text NOT NULL,
  `realname` text NOT NULL,
  `email` text NOT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `inactivity_warn_stamp` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `inactivity_warned` enum('true','false') NOT NULL DEFAULT 'false',
  UNIQUE KEY `userid` (`userid`),
  KEY `stamp` (`stamp`),
  KEY `realname` (`realname`(3072)),
  KEY `email` (`email`(3072)),
  KEY `created` (`created`),
  KEY `inactivity_warn_stamp` (`inactivity_warn_stamp`),
  KEY `inactivity_warned` (`inactivity_warned`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `user_prefs`
--

DROP TABLE IF EXISTS `user_prefs`;
CREATE TABLE `user_prefs` (
  `userid` int(11) NOT NULL DEFAULT 0,
  `name` varchar(64) NOT NULL DEFAULT '',
  `value` text DEFAULT NULL,
  KEY `userid` (`userid`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `user_privs`
--

DROP TABLE IF EXISTS `user_privs`;
CREATE TABLE `user_privs` (
  `userid` int(11) NOT NULL DEFAULT 0,
  `priv` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`userid`),
  KEY `priv` (`priv`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `vendor`
--

DROP TABLE IF EXISTS `vendor`;
CREATE TABLE `vendor` (
  `vendorId` int(11) NOT NULL AUTO_INCREMENT,
  `vendorName` varchar(100) NOT NULL DEFAULT '',
  `vendorURL` varchar(200) DEFAULT NULL,
  `state` enum('accepted','queued','deleted') NOT NULL DEFAULT 'accepted',
  PRIMARY KEY (`vendorId`),
  KEY `vendorName` (`vendorName`),
  KEY `state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
