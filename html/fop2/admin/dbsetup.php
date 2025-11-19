<?php

if(!isset($before_end_body)) {
    $before_end_body='<script>';
}

$unlink_old_file['js/jquery.stickOnScroll.js']=1;
$unlink_old_file['js/jquery.twinkle.js']=1;

$querycreate['fop2buttons'] = "CREATE TABLE IF NOT EXISTS `fop2buttons` (
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
    `server` varchar(100) NOT NULL default '',
    `cssclass` varchar(200) NOT NULL default '',
    `originatevariables` text,
    `autoanswerheader` varchar(255) default '__SIPADDHEADER51=Call-Info: answer-after=0.001',
    `sip_username` varchar(255) default '',
    `sip_password` varchar(255) default '',
    PRIMARY KEY  (`id`),
    UNIQUE KEY `devname` (`device`)
   ) DEFAULT CHARSET=UTF8;";

$querycreate['fop2users'] = "CREATE TABLE IF NOT EXISTS `fop2users` (
    `id` int(11) NOT NULL auto_increment,
    `context_id` int(11) NOT NULL,
    `exten` varchar(30) NOT NULL,
    `secret` varchar(20) NOT NULL,
    `permissions` text default NULL,
    PRIMARY KEY  (`id`),
    UNIQUE KEY `extctx` (`exten`,`context_id`)
   ) DEFAULT CHARSET=UTF8;";

$querycreate['fop2groups'] = "CREATE TABLE `fop2groups` (
  `id` int(11) NOT NULL DEFAULT '0',
  `context_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  UNIQUE KEY `idcont` (`id`,`context_id`),
  UNIQUE KEY `contextname` (`context_id`,`name`)
) DEFAULT CHARSET=utf8;";

$querycreate['fop2templates'] = "CREATE TABLE IF NOT EXISTS `fop2templates` (
    `id` int(11) NOT NULL auto_increment,
    `context_id` int(11) NOT NULL,
    `name` varchar(30) default NULL,
    `permissions` text default NULL,
    `groups` varchar(200) default NULL,
    `plugins` text default NULL,
    `custom` text,
    `isdefault` int(6) default '0',
    PRIMARY KEY  (`id`)
  ) DEFAULT CHARSET=UTF8;";

$querycreate['fop2permissions'] = "CREATE TABLE IF NOT EXISTS `fop2permissions` (
    `id` int(11) NOT NULL auto_increment,
    `context_id` int(11) NOT NULL,
    `name` varchar(30) default NULL,
    `permissions` varchar(200) default NULL,
    PRIMARY KEY  (`id`)
  ) DEFAULT CHARSET=UTF8;";

$querycreate['fop2settings'] = "CREATE TABLE IF NOT EXISTS `fop2settings` (
    `id` int(11) NOT NULL auto_increment,
    `keyword` varchar(250) NOT NULL,
    `value` text NOT NULL,
    PRIMARY KEY  (`id`),
    UNIQUE KEY `kw` (`keyword`)
  ) DEFAULT CHARSET=UTF8;";

$querycreate['fop2setup'] = "CREATE TABLE IF NOT EXISTS `fop2setup` (
  `id` int(11) NOT NULL auto_increment,
  `keyword` varchar(150) NOT NULL,
  `parameter` varchar(150) default '',
  `value` varchar(150) default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `keypar` (`keyword`,`parameter`)
) DEFAULT CHARSET=UTF8";

$querycreate['fop2plugins'] = "CREATE TABLE IF NOT EXISTS `fop2plugins` (
    `id` int(11) NOT NULL auto_increment,
    `rawname` varchar(50) default NULL,
    `name` varchar(100) default NULL,
    `version` varchar(10) default NULL,
    `description` tinytext,
    `global` int(11) default '0',
    PRIMARY KEY  (`id`),
    UNIQUE KEY `rname` (`rawname`)
  ) DEFAULT CHARSET=UTF8;";

$querycreate['fop2GroupButton'] = "CREATE TABLE IF NOT EXISTS `fop2GroupButton` (
    `id` int(11) NOT NULL auto_increment,
    `context_id` int(11) NOT NULL,
    `group_name` varchar(50) default NULL,
    `id_button` int(11) NOT NULL,
    PRIMARY KEY  (`id`)
  ) DEFAULT CHARSET=UTF8;";

$querycreate['fop2UserGroup'] = "CREATE TABLE IF NOT EXISTS `fop2UserGroup` (
    `id` int(11) NOT NULL auto_increment,
    `context_id` int(11) NOT NULL,
    `exten` varchar(30) NOT NULL,
    `id_group` int(11) NOT NULL,
    PRIMARY KEY  (`id`),
    UNIQUE KEY `uni` (`exten`,`id_group`,`context_id`)
  ) DEFAULT CHARSET=UTF8;";

$querycreate['fop2PermGroup'] = "CREATE TABLE IF NOT EXISTS `fop2PermGroup` (
    `id` int(11) NOT NULL auto_increment,
    `context_id` int(11) NOT NULL,
    `name` varchar(30) default NULL,
    `id_group` int(11) default NULL,
    `name_group` varchar(100) default NULL,
    PRIMARY KEY  (`id`)
  ) DEFAULT CHARSET=UTF8;";

$querycreate['fop2UserPlugin'] = "CREATE TABLE IF NOT EXISTS `fop2UserPlugin` (
    `id` int(11) NOT NULL auto_increment,
    `context_id` int(11) NOT NULL,
    `exten` varchar(30) default NULL,
    `id_plugin` varchar(50) default NULL,
    PRIMARY KEY  (`id`),
    UNIQUE KEY `uni` (`exten`,`id_plugin`)
  ) DEFAULT CHARSET=UTF8;";

$querycreate['fop2UserContext'] = "CREATE TABLE `fop2UserContext` (
  `id_user` int(11) NOT NULL,
  `id_context` int(11) default NULL,
  PRIMARY KEY  (`id_user`)
) DEFAULT CHARSET=UTF8;";

$querycreate['fop2ButtonContext'] = "CREATE TABLE `fop2ButtonContext` (
  `id_button` int(11) NOT NULL,
  `id_context` int(11) default NULL,
  PRIMARY KEY  (`id_button`)
) DEFAULT CHARSET=UTF8;";

$querycreate['fop2contexts'] = " CREATE TABLE `fop2contexts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `exclude` int(2) NOT NULL default 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ctx` (`context`)
) DEFAULT CHARSET=UTF8;";

$querycreate['fop2UserTemplate'] = "CREATE TABLE IF NOT EXISTS `fop2UserTemplate` (
    `id` int(11) NOT NULL auto_increment,
    `context_id` int(11) NOT NULL,
    `exten` varchar(30) NOT NULL,
    `id_template` int(11) NOT NULL,
    PRIMARY KEY  (`id`),
    UNIQUE KEY `uni` (`exten`,`id_template`)
  ) DEFAULT CHARSET=UTF8;";

$querycreate['fop2recordings'] = "CREATE TABLE `fop2recordings` (
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
) DEFAULT CHARSET=UTF8;";

$querycreate['fop2managerusers'] = "CREATE TABLE `fop2managerusers` (
  `id` int(11) NOT NULL auto_increment,
  `user` varchar(50) default NULL,
  `password` varchar(100) default NULL,
  `level` varchar(30) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`user`)
) DEFAULT CHARSET=UTF8;";

$querycreate['fop2manageracl'] = "CREATE TABLE `fop2manageracl` (
  `id` int(11) NOT NULL auto_increment,
  `resource` varchar(50) default NULL,
  `level` varchar(30) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `byresource` (`resource`)
) DEFAULT CHARSET=UTF8;";

$querycreate['fop2managersecurelevel'] = "
CREATE TABLE `fop2managersecurelevel` (
  `level` int(11) NOT NULL default '0',
  `detail` varchar(30) default NULL,
  `icon` varchar(50) default NULL,
  PRIMARY KEY  (`level`),
  UNIQUE KEY `det` (`detail`)
) DEFAULT CHARSET=UTF8;";

$querycreate['fop2managerUserContext']="CREATE TABLE `fop2managerUserContext` (
  `id_user` int(11) NOT NULL,
  `id_context` int(11) default NULL,
  KEY `us` (`id_user`),
  KEY `te` (`id_context`),
  KEY `uste` (`id_user`,`id_context`)
) DEFAULT CHARSET UTF8;";

$insert_new_users   = 0;
$insert_new_buttons = 0;
$insert_new_manager_users   = 0;
$insert_new_manager_secure_level   = 0;

// Check if tables exists, if not create them
foreach($querycreate as $table=>$query) {
    if($db->is_connected()) {
        if(!$db->table_exists($table) ) {
            $ras = $db->consulta($query);
            if(!$ras) {
                if (php_sapi_name() !='cli') {
                    $before_end_body.= "alertify.error('".sprintf(__("Error creating table %s!"), $table)."');\n";
                } else {
                    echo sprintf(__("Error creating table %s!"), $table)."\n";
                }
            } else {
                if (php_sapi_name() !='cli') {
                    $before_end_body.= "alertify.success('".sprintf(__("Table %s created successfully"), $table)."');\n";
                } else {
                    echo sprintf(__("Table %s created successfully"), $table)."\n";
                }
                if($table == 'fop2buttons') { $insert_new_buttons = 1; }
                if($table == 'fop2users')   { $insert_new_users   = 1; }
                if($table == 'fop2managerusers')   { $insert_new_manager_users   = 1; }
                if($table == 'fop2managersecurelevel')   { $insert_new_manager_secure_level   = 1; }
            }
        }
    }
}

foreach($unlink_old_file as $file=>$nothing) {
    $unlink_old_file = dirname(__FILE__)."/".$file;
    if(is_file($unlink_old_file)) {
        if(is_writable($unlink_old_file)) { 
            $result = unlink($unlink_old_file); 
            if($result) {
                if (php_sapi_name() !='cli') {
                    $before_end_body.= "alertify.success('".sprintf(__('File %s removed successfully'), $file)."');\n";
                } else {
                    echo sprintf(__('File %s removed successfully'), $file)."\n";
                }
            } else {
                if($DEBUG==1) {
                    if (php_sapi_name() !='cli') {
                        $before_end_body.= "alertify.error('".sprintf(__("Could not remove file %s!"), $file)."');\n";
                    } else {
                        echo sprintf(__('Could not remove file %s!'), $file)."\n";
                    }
                }
            }
        }
    }
}

if(!is_dir("uploads")) {
    @mkdir("uploads");
}

if($db->is_connected()) {

    // Modificamos indice para soporte multi contexto
    $res = $db->consulta("DESC fop2UserGroup");
    if($res) {
       $res = $db->consulta("SHOW INDEX FROM fop2UserGroup where Key_name='uni'");
       if($res) {
          $cont=0;
          while($row = $db->fetch_assoc($res)) {
             $cont++;
          }
          if($cont<3) {
              $db->consulta("alter table fop2UserGroup drop index uni");
              $db->consulta("alter table fop2UserGroup add unique uni (exten,id_group,context_id)");
          }
       }
    }

    $updated_field = array();
    $alldbfields   = array();

    $alldbfields['fop2buttons']['customastdb']= "ALTER TABLE `fop2buttons` ADD `customastdb` varchar(50) default '' AFTER originatechannel";
    $alldbfields['fop2buttons']['spyoptions'] = "ALTER TABLE `fop2buttons` ADD `spyoptions` varchar(100) default '' AFTER customastdb";
    $alldbfields['fop2buttons']['channel']    = "ALTER TABLE `fop2buttons` ADD `channel` varchar(200) default '' AFTER mailbox";
    $alldbfields['fop2buttons']['external']   = "ALTER TABLE `fop2buttons` ADD `external` varchar(100) default '' AFTER spyoptions";
    $alldbfields['fop2buttons']['accountcode']= "ALTER TABLE `fop2buttons` ADD `accountcode` varchar(100) default '' AFTER external";
    $alldbfields['fop2buttons']['exclude']    = "ALTER TABLE `fop2buttons` ADD `exclude` int(2) NOT NULL default 0 AFTER id";
    $alldbfields['fop2contexts']['exclude']   = "ALTER TABLE `fop2contexts` ADD `exclude` int(2) NOT NULL default 0 AFTER id";
    $alldbfields['fop2buttons']['tags']       = "ALTER TABLE `fop2buttons` ADD `tags` varchar(255) default '' AFTER external";
    $alldbfields['fop2buttons']['sortorder']  = "ALTER TABLE `fop2buttons` ADD `sortorder` int(6) NOT NULL default 0 AFTER exclude";
    $alldbfields['fop2buttons']['cssclass']   = "ALTER TABLE `fop2buttons` ADD `cssclass` varchar(200) NOT NULL default '' AFTER tags";
    $alldbfields['fop2buttons']['queuecontext']   = "ALTER TABLE `fop2buttons` ADD `queuecontext` varchar(200) NOT NULL default '' AFTER tags";
    $alldbfields['fop2buttons']['server']     = "ALTER TABLE `fop2buttons` ADD `server` varchar(100) NOT NULL default '' AFTER queuecontext";
    $alldbfields['fop2buttons']['extenvoicemail'] = "ALTER TABLE `fop2buttons` ADD `extenvoicemail` varchar(200) NOT NULL default '' AFTER tags";
    $alldbfields['fop2buttons']['originatevariables'] = "ALTER TABLE `fop2buttons` ADD `originatevariables` text NOT NULL default '' AFTER tags";
    $alldbfields['fop2buttons']['autoanswerheader'] = "ALTER TABLE `fop2buttons` ADD `autoanswerheader` varchar(255) NOT NULL default '__SIPADDHEADER51=Call-Info: answer-after=0.001' AFTER tags";
    $alldbfields['fop2buttons']['sip_username'] = "ALTER TABLE `fop2buttons` ADD `sip_username` varchar(255) NOT NULL default '' AFTER autoanswerheader";
    $alldbfields['fop2buttons']['sip_password'] = "ALTER TABLE `fop2buttons` ADD `sip_password` varchar(255) NOT NULL default '' AFTER sip_username";
    $alldbfields['fop2templates']['plugins']     = "ALTER TABLE `fop2templates` ADD `plugins` text NOT NULL";
    $alldbfields['fop2templates']['custom']      = "ALTER TABLE `fop2templates` ADD `custom` text NOT NULL";

    $alldbfields['fop2buttons']['context_id'] = "ALTER TABLE `fop2buttons` ADD `context_id` int(11) NOT NULL AFTER id";
    $alldbfields['fop2users']['context_id'] = "ALTER TABLE `fop2users` ADD `context_id` int(11) NOT NULL AFTER id";
    $alldbfields['fop2groups']['context_id'] = "ALTER TABLE `fop2groups` ADD `context_id` int(11) NOT NULL AFTER id";
    $alldbfields['fop2permissions']['context_id'] = "ALTER TABLE `fop2permissions` ADD `context_id` int(11) NOT NULL AFTER id";
    $alldbfields['fop2templates']['context_id'] = "ALTER TABLE `fop2templates` ADD `context_id` int(11) NOT NULL AFTER id";
    $alldbfields['fop2users']['context_id'] = "ALTER TABLE `fop2users` ADD `context_id` int(11) NOT NULL AFTER id";
    $alldbfields['fop2GroupButton']['context_id'] = "ALTER TABLE `fop2GroupButton` ADD `context_id` int(11) NOT NULL AFTER id";
    $alldbfields['fop2UserPlugin']['context_id'] = "ALTER TABLE `fop2UserPlugin` ADD `context_id` int(11) NOT NULL AFTER id";
    $alldbfields['fop2UserGroup']['context_id'] = "ALTER TABLE `fop2UserGroup` ADD `context_id` int(11) NOT NULL AFTER id";
    $alldbfields['fop2PermGroup']['context_id'] = "ALTER TABLE `fop2PermGroup` ADD `context_id` int(11) NOT NULL AFTER id";
    $alldbfields['fop2contexts']['name'] = "ALTER TABLE `fop2contexts` ADD `name` varchar(100) NOT NULL";

    $populate_after_alter['fop2buttons']['customastdb'] = "UPDATE fop2buttons SET customastdb=CONCAT('CF/',exten) WHERE type='extension'";
    $populate_after_alter['fop2buttons']['queuecontext'] = "UPDATE fop2buttons SET queuecontext='from-queue' WHERE type='extension'";
    $populate_after_alter['fop2buttons']['extenvoicemail'] = "UPDATE fop2buttons SET extenvoicemail=IF(mailbox<>'',CONCAT('*',exten),'') WHERE type='extension'";

    $res = $db->consulta("DESC fop2groups");
    if($res) {
        while($row = $db->fetch_assoc($res)) {
            if($row['Field']=='context_id') {
                if($row['Key']<>'PRI') {
                    $queries = array();
                    $queries[] = "ALTER TABLE fop2groups DROP index name";
                    $queries[] = "ALTER TABLE fop2groups CHANGE id id int(11);";
                    $queries[] = "ALTER TABLE fop2groups DROP primary key";
                    $queries[] = "ALTER TABLE fop2groups ADD UNIQUE idcont (id,context_id)";
                    $queries[] = "ALTER TABLE fop2groups ADD UNIQUE contextname (context_id,name);";
                    foreach($queries as $myq) {
                        $res = $db->consulta($myq);
                    }
                }
            }
        }
    } 

    // Updates fop2buttons Unique index to include context_id (upgrade from 1.0.4 to 1.0.5)
    $queries = array();
    $res = $db->consulta("DESC fop2buttons");
    if($res) {
        while($row = $db->fetch_assoc($res)) {
            if($row['Field']=='context_id') {
                if($row['Key']<>'MUL') {
                    $queries[] = "ALTER TABLE fop2buttons DROP index devname";
                    $queries[] = "ALTER TABLE fop2buttons ADD UNIQUE devname (context_id,device);";
                }
            }
        }
        foreach($queries as $myq) {
            $res = $db->consulta($myq);
        }
    }

    $queries = array();
    $res = $db->consulta("DESC fop2plugins");
    if($res) {
        while($row = $db->fetch_assoc($res)) {
            if($row['Field']=='description') {
                if($row['Type']!='text') {
                    $queries[] = 'ALTER TABLE fop2plugins CHANGE description description text';
                }
            }
        }
        foreach($queries as $myq) {
            $res = $db->consulta($myq);
        }
    }

    $queries = array();
    $res = $db->consulta("DESC fop2templates");
    if($res) {
        while($row = $db->fetch_assoc($res)) {
            if($row['Field']=='permissions') {
                if($row['Type']!='text') {
                    $queries[] = 'ALTER TABLE fop2templates CHANGE permissions permissions text';
                }
            } else 
            if($row['Field']=='plugins') {
                if($row['Type']!='text') {
                    $queries[] = 'ALTER TABLE fop2templates CHANGE plugins plugins text';
                }
            }
        }
        foreach($queries as $myq) {
            $res = $db->consulta($myq);
        }
    }

    $queries = array();
    $res = $db->consulta("DESC fop2users");
    if($res) {
        while($row = $db->fetch_assoc($res)) {
            if($row['Field']=='permissions') {
                if($row['Type']!='text') {
                    $queries[] = 'ALTER TABLE fop2users CHANGE permissions permissions text';
                }
            }
        }
        foreach($queries as $myq) {
            $res = $db->consulta($myq);
        }
    }

    foreach($alldbfields as $table => $rest) {
        if($db->table_exists($table)) {
            foreach($rest as $campo=>$query) {
                if(!$db->column_exists($table,$campo)) {
                    $db->consulta($query);
                    $updated_field[$table][$campo]=1;
                    if($DEBUG==1) {
                        if (php_sapi_name() !='cli') {
                            $before_end_body.= "alertify.success('".sprintf(__('Field %s added to table %s'), $campo, $table)."');\n";
                        } else { 
                            echo sprintf(__('Field %s added to table %s'), $campo, $table)."\n";
                        }
                    }
                }
            }
        }
    }

    foreach($updated_field as $table=>$rest) {
        foreach($rest as $field=>$nada) {
            if(isset($populate_after_alter[$table][$field])) {
                $db->consulta($populate_after_alter[$table][$field]);
                if($DEBUG==1) {
                    if (php_sapi_name() !='cli') {
                        $before_end_body.="alertify.success('".sprintf( __('Field %s from table %s populated with data from backend'), $field, $table )."');\n";
                    } else {
                        echo sprintf( __('Field %s from table %s populated with data from backend'), $field, $table )."\n";
                    }
                }
            }
        }
    }

    plugin_insert_missing_db();

    if($insert_new_buttons == 1) {
        // INSERTAR BOTONES NUEVOS
        $system_buttons = system_all_buttons();
        $cont=0;
        foreach($system_buttons as $chan=>$dat) {
            $cont++;
            fop2_add_button($dat);
        }
        if($cont>0) {
            if (php_sapi_name() !='cli') {
                $before_end_body.="alertify.success('".sprintf( __('%s buttons added'), $cont )."');\n";
            } else {
                echo sprintf( __('%s buttons added'), $cont )."\n";
            }
         }
    }

    if($insert_new_users == 1) {

        $cont = fop2_insert_users();

        if($cont>0) {
            if (php_sapi_name() !='cli') {
                $before_end_body.="alertify.success('".sprintf( __('%s users added'), $cont )."');\n";
            } else {
                echo sprintf( __('%s users added'), $cont )."\n";
            }
        }
 
    }

    if($insert_new_manager_users == 1) {
        $db->consulta("INSERT INTO fop2managerusers (user,password,level) VALUES ('$ADMINUSER',sha1('$ADMINPWD'),'admin')");
    }

    if($insert_new_manager_secure_level == 1) {
        $db->consulta("INSERT INTO fop2managersecurelevel (level,detail,icon) VALUES (1,'admin','fa fa-shield-alt')");
        $db->consulta("INSERT INTO fop2managersecurelevel (level,detail,icon) VALUES (2,'user','fa fa-user')");
    }
}

$before_end_body.='</script>';

