CREATE TABLE `fop2ButtonContext` (
 `id_button` int(11) NOT NULL,
 `id_context` int(11) default NULL,
 PRIMARY KEY (`id_button`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `fop2GroupButton` (
 `id` int(11) NOT NULL auto_increment,
 `context_id` int(11) NOT NULL,
 `group_name` varchar(50) default NULL,
 `id_button` int(11) NOT NULL,
 PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `fop2PermGroup` (
 `id` int(11) NOT NULL auto_increment,
 `context_id` int(11) NOT NULL,
 `name` varchar(30) default NULL,
 `id_group` int(11) default NULL,
 `name_group` varchar(100) default NULL,
 PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `fop2UserContext` (
 `id_user` int(11) NOT NULL,
 `id_context` int(11) default NULL,
 PRIMARY KEY (`id_user`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `fop2UserGroup` (
 `id` int(11) NOT NULL auto_increment,
 `context_id` int(11) NOT NULL,
 `exten` varchar(30) NOT NULL,
 `id_group` int(11) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `uni` (`exten`,`id_group`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `fop2UserPlugin` (
 `id` int(11) NOT NULL auto_increment,
 `context_id` int(11) NOT NULL,
 `exten` varchar(30) default NULL,
 `id_plugin` varchar(50) default NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `uni` (`exten`,`id_plugin`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `fop2buttons` (
 `id` int(11) NOT NULL auto_increment,
 `context_id` int(11) NOT NULL,
 `exclude` int(2) NOT NULL default '0',
 `sortorder` int(6) NOT NULL default '0',
 `type` varchar(40) NOT NULL,
 `device` varchar(100) NOT NULL,
 `privacy` varchar(30) default NULL,
 `label` varchar(100) default NULL,
 `group` varchar(60) NOT NULL default '',
 `exten` varchar(30) default NULL,
 `email` varchar(100) NOT NULL default '',
 `context` varchar(100) default NULL,
 `mailbox` varchar(100) default NULL,
 `channel` varchar(200) default '',
 `queuechannel` text,
 `originatechannel` varchar(200) default '',
 `customastdb` varchar(50) default '',
 `spyoptions` varchar(100) default '',
 `external` varchar(100) default '',
 `accountcode` varchar(100) default '',
 `tags` varchar(255) default '',
 `extenvoicemail` varchar(200) NOT NULL default '',
 `queuecontext` varchar(200) NOT NULL default '',
 `cssclass` varchar(200) NOT NULL default '',
 `originatevariables` text,
 `autoanswerheader` varchar(255) default '__SIPADDHEADER51=Call-Info: answer-after=0.001',
 PRIMARY KEY (`id`),
 UNIQUE KEY `devname` (`device`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `fop2contexts` (
 `id` int(11) NOT NULL auto_increment,
 `context` varchar(50) default NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `ctx` (`context`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `fop2groups` (
  `id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  UNIQUE KEY `idcont` (`id`,`context_id`),
  UNIQUE KEY `contextname` (`context_id`,`name`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `fop2permissions` (
 `id` int(11) NOT NULL auto_increment,
 `context_id` int(11) NOT NULL,
 `name` varchar(30) default NULL,
 `permissions` varchar(200) default NULL,
 PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `fop2plugins` (
 `id` int(11) NOT NULL auto_increment,
 `rawname` varchar(50) default NULL,
 `name` varchar(100) default NULL,
 `version` varchar(10) default NULL,
 `description` tinytext,
 `global` int(11) default '0',
 PRIMARY KEY (`id`),
 UNIQUE KEY `rname` (`rawname`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `fop2settings` (
 `id` int(11) NOT NULL auto_increment,
 `keyword` varchar(250) NOT NULL,
 `value` text NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `kw` (`keyword`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `fop2templates` (
 `id` int(11) NOT NULL auto_increment,
 `context_id` int(11) NOT NULL,
 `name` varchar(30) default NULL,
 `permissions` varchar(200) default NULL,
 `groups` varchar(200) default NULL,
 `plugins` varchar(200) default NULL,
 `isdefault` int(6) default '0',
 PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `fop2users` (
 `id` int(11) NOT NULL auto_increment,
 `context_id` int(11) NOT NULL,
 `exten` varchar(30) NOT NULL,
 `secret` varchar(20) NOT NULL,
 `permissions` varchar(200) default NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `extctx` (`exten`,`context_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `fop2UserTemplate` (
 `id` int(11) NOT NULL auto_increment,
 `context_id` int(11) NOT NULL,
 `exten` varchar(30) NOT NULL,
 `id_template` int(11) NOT NULL,
 PRIMARY KEY  (`id`),
 UNIQUE KEY `uni` (`exten`,`id_template`)
) DEFAULT CHARSET=UTF8;

CREATE TABLE IF NOT EXISTS `fop2recordings` (
 `id` int(11) NOT NULL auto_increment,
 `uniqueid` varchar(50) default NULL,
 `datetime` datetime default NULL,
 `ownerextension` varchar(20) default NULL,
 `targetextension` varchar(20) default NULL,
 `filename` tinytext,
 `duration` int(11) default '0',
 `context` varchar(200) default NULL,
 PRIMARY KEY  (`id`),
 UNIQUE KEY `uni` (`uniqueid`)
) DEFAULT CHARSET=UTF8;
