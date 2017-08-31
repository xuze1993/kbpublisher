<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2008 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


class SetupModelUpgrade extends SetupModel
{
	
	function factory($version) {
		$class = 'SetupModelUpgrade_' . $version;
		return new $class;
	}
	
	
	// upgrade setting_to_value table
	function setCommonSettings($values) {

		if(!empty($values['file_dir'])) {
			$ret = $this->setFileDirectory($values['file_dir'], 'REPLACE');
			if($ret !== true) {
				return $ret;
			}		
		}
		
		if(!empty($values['html_editor_upload_dir'])) {
			$ret = $this->setFckDirectory($values['html_editor_upload_dir'], 'REPLACE');
			if($ret !== true) {
				return $ret;
			}		
		}
		
		return true;
	}
}


class SetupModelUpgrade_skip extends SetupModelUpgrade
{
    function execute($values) {
        return true;
    }
}


// complete
class SetupModelUpgrade_20_to_301 extends SetupModelUpgrade
{
	
	var $tbl_pref_custom;
	var $tables = array('entry', 'category', 'entry_to_category', 'setting_to_value', 'comment');
	var $custom_tables = array('user','member');
	
	
	// some check implemented here 
	// this will be caled inside executeArray
	function checkSkipSql($key, $sql, $tbl_pref) {	
		
		// skip drop index in priv if no index "user"
		if($key == 0 && strpos($sql, 'DROP INDEX `user`') !== false) {
		
			$sql = "SHOW INDEX FROM {$tbl_pref}priv";			
			$result = &$this->db->_Execute($sql);
			while($row = &$result->FetchRow()) {
				if($row['Key_name'] == 'user') {
					return false; // no need to skip
				}				
			}
			
			return true; // skip, not user index found
		}
		
		return false;
	}
	
	
	function execute($values) {
		
		$file = 'db/upgrade_2.0_to_3.0.1.sql';
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));
		
		$tbl_pref = $values['tbl_pref']; // we have valid prefix here
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();

		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
		$ret = $this->executeArray($sql_array, $tbl_pref);

		if($ret !== true) {
			return $ret;
		}

		
		$this->setTables($tbl_pref, 'kb_');
		$ret = $this->upgradeEntries();
		if($ret !== true) {
			return $ret;
		}
		
		$this->setTables($tbl_pref, 'file_');
		$ret = $this->upgradeEntries();
		if($ret !== true) {
			return $ret;
		}

		$this->setTables($tbl_pref, 'kb_');
		$ret = $this->upgradeUsers(false);
		if($ret !== true) {
			return $ret;
		}
		
		// upgrade setting_to_value table
		$this->setTables($tbl_pref, '');
		
		if(!empty($values['file_dir'])) {
			$ret = $this->setFileDirectory($values['file_dir'], 'REPLACE');
			if($ret !== true) {
				return $ret;
			}		
		}
		
		if(!empty($values['html_editor_upload_dir'])) {
			$ret = $this->setFckDirectory($values['html_editor_upload_dir']);
			if($ret !== true) {
				return $ret;
			}		
		}
		
		return true;
	}
	
	
	// add category_id to entry, file
	function upgradeEntries() {
	
		$sql = "SELECT entry_id, category_id FROM {$this->tbl->entry_to_category} WHERE is_main = 1";
		$result =& $this->db->Execute($sql);
		if(!$result) {
			return DbUtil::error($sql);
		}		
		
		
		$sql_str = "UPDATE {$this->tbl->entry} SET date_updated = date_updated, category_id = %d WHERE id = %d";
		while($row = $result->FetchRow()) {
			$sql = sprintf($sql_str, $row['category_id'], $row['entry_id']);
			$result1 = $this->db->_Execute($sql);
			if(!$result1) {
				return DbUtil::error($sql);
			}
		}
		
		return true;
	}
	
	
	// move members to users
	function upgradeUsers() {
	
		$sql = $this->getDublicateMembersByEmailSql();
		$result = $this->db->Execute($sql);
		if(!$result) {
			return DbUtil::error($sql);
		}		
		
		
		$users = &$result->GetAssoc();
		if($users) {
			$users = array_chunk($users, 20);		
			
			foreach(array_keys($users) as $k) {
				$member_ids = (implode(',', $users[$k]));
				$sql = "DELETE FROM {$this->tbl->member} WHERE id IN ({$member_ids})";
				$result = &$this->db->_Execute($sql);
				if(!$result) {
					return DbUtil::error($sql);
				}
			}		
		}

		
		$sql = $this->moveMembersQuickSql();
		$result =& $this->db->_Execute($sql);
		if(!$result) {
			return DbUtil::error($sql);
		}
		
		
		$sql = "UPDATE {$this->tbl->comment} SET user_id = NULL";
		$result =& $this->db->_Execute($sql);
		if(!$result) {
			return DbUtil::error($sql);
		}	
	
		return true;
	}
	
	
	function getDublicateMembersByEmailSql() {
		$sql = "SELECT u.id AS user_id, m.id AS member_id 
		FROM 
			{$this->tbl->member} m,
			{$this->tbl->user} u
		WHERE m.email = u.email";
		return $sql;
	}
	
		
	function moveMembersQuickSql() {
		$sql = "
		INSERT IGNORE INTO {$this->tbl->user} 
		(first_name, last_name, middle_name, email, username, password, phone, date_registered, active, user_comment, admin_comment)
		SELECT 
		 first_name, last_name, middle_name, email, username, password, phone, date_registered, active, member_comment, admin_comment 
		FROM {$this->tbl->member}";
		return $sql;
	}
	
	
	// it is better to reasign order in new created field sort_order
	// in kb_entry_to_category
	function reassignArticleOrder() {
			
	}
}


class SetupModelUpgrade_301_to_35 extends SetupModelUpgrade
{
	
	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	
	
	
	function execute($values) {
		
		$file = 'db/upgrade_3.0.1_to_3.5.sql';
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));
		
		$tbl_pref = $values['tbl_pref'];
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();

		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);	
		$ret = $this->executeArray($sql_array);
		if($ret !== true) {
			return $ret;
		}	

		
		// upgrade setting_to_value table
		$this->setTables($tbl_pref, '');
	
		// admin email
		$ret = $this->getSetting('from_email');
		if(empty($ret['error'])) {
			$ret = $this->setAdminEmail($ret['val']);
			if($ret !== true) {
				return $ret;
			}
			
		} else {
			return $ret['error'];
		}

		
		// xpdf
		$ret = $this->getSetting('file_extract');
		if(empty($ret['error'])) {
			if($ret) {
				$file = $this->getSetupData('old_config_file');
				if($file && file_exists($file)) {
					
					$old_admin_dir = str_replace('config.inc.php', '', $file);
					$file = $old_admin_dir . 'extra/file_extractors/config.inc.php';
					if(file_exists($file)) {
						require_once $file;
						if(isset($file_conf['extract_tool']['pdf'])) {
							
							// file extract pdf
							$ret = $this->setSettingById(141, $file_conf['extract_tool']['pdf']);
							if($ret !== true) {
								return $ret;
							}						
						}
					}			
				}			
			}		

		} else {
			return $ret['error'];
		}


		// common settings to value
		$ret = $this->setCommonSettings($values);
		if($ret !== true) {
			return $ret;
		}
		
		return true;
	}
}


class SetupModelUpgrade_20_to_35 extends SetupModelUpgrade
{	
	
	function execute($values) {
		
		// 2.0 to 3.0.1
		$upgrade = new SetupModelUpgrade_20_to_301();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}
		
		
		// 3.0.1 to 3.5
		$upgrade = new SetupModelUpgrade_301_to_35();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}		
		
		return true;
	}
}


// ======== 4.0 ======================= >

class SetupModelUpgrade_20_to_402 extends SetupModelUpgrade
{	

	function execute($values) {

		// 2.0 to 3.5
		$upgrade = new SetupModelUpgrade_20_to_35();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 3.5.2 to 4.0.2
		$upgrade = new SetupModelUpgrade_352_to_402();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}		

		return true;
	}
}


class SetupModelUpgrade_301_to_402 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.0.1 to 3.5
		$upgrade = new SetupModelUpgrade_301_to_35();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 3.5.2 to 4.0.2
		$upgrade = new SetupModelUpgrade_352_to_402();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}		

		return true;
	}
}


class SetupModelUpgrade_352_to_402 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	


	function execute($values) {

		$file = 'db/upgrade_3.5.2_to_4.0.2.sql';
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));

		$tbl_pref = $values['tbl_pref'];
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();

		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);	
		$ret = $this->executeArray($sql_array);
		if($ret !== true) {
			return $ret;
		}	


		// upgrade setting_to_value table
		$this->setTables($tbl_pref, '');

		// common settings to value
		$ret = $this->setCommonSettings($values);
		if($ret !== true) {
			return $ret;
		}

		return true;
	}
}


// ======== 4.5 ======================= >

// update to 453 does not any special sql

class SetupModelUpgrade_20_to_452 extends SetupModelUpgrade
{	

	function execute($values) {

		// 2.0 to 4.0.2
		$upgrade = new SetupModelUpgrade_20_to_402();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 4.0.2 to 4.5.2
		$upgrade = new SetupModelUpgrade_402_to_452();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}		

		return true;
	}
}


class SetupModelUpgrade_301_to_452 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.0.1 to 4.0.2
		$upgrade = new SetupModelUpgrade_301_to_402();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 4.0.2 to 4.5.2
		$upgrade = new SetupModelUpgrade_402_to_452();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}		

		return true;
	}
}


class SetupModelUpgrade_352_to_452 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.5.2 to 4.0.2
		$upgrade = new SetupModelUpgrade_352_to_402();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 4.0.2 to 4.5.2
		$upgrade = new SetupModelUpgrade_402_to_452();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}		

		return true;
	}
}


class SetupModelUpgrade_402_to_452 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	


	function execute($values) {

		$file = 'db/upgrade_4.0.2_to_4.5.2.sql';
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));

		$tbl_pref = $values['tbl_pref'];
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();

		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
		$ret = $this->executeArray($sql_array);
		if($ret !== true) {
			return $ret;
		}	


		// upgrade setting_to_value table
		$this->setTables($tbl_pref, '');

		// common settings to value
		$ret = $this->setCommonSettings($values);
		if($ret !== true) {
			return $ret;
		}

		return true;
	}
}


class SetupModelUpgrade_45_to_452 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');


	function execute($values) {

		$file = 'db/upgrade_4.5_to_4.5.2.sql';
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));

		$tbl_pref = $values['tbl_pref'];
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();

		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
		$ret = $this->executeArray($sql_array);
		if($ret !== true) {
			return $ret;
		}	


		// upgrade setting_to_value table
		$this->setTables($tbl_pref, '');

		// common settings to value
		$ret = $this->setCommonSettings($values);
		if($ret !== true) {
			return $ret;
		}

		return true;
	}
}


class SetupModelUpgrade_451_to_452 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');


    function execute($values) {

        $file = 'db/upgrade_4.5.1_to_4.5.2.sql';
        $data = FileUtil::read($file);
        $this->setSetupData(array('sql_file' => $file));

        $tbl_pref = $values['tbl_pref'];
        $this->connect($values);
        $mysqlv = $this->getMySQLVersion();

        $sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
        $ret = $this->executeArray($sql_array);
        if($ret !== true) {
            return $ret;
        }   


        // upgrade setting_to_value table
        $this->setTables($tbl_pref, '');

        // common settings to value
        $ret = $this->setCommonSettings($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


class SetupModelUpgrade_452_to_453 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');


    function execute($values) {

        $tbl_pref = $values['tbl_pref'];
        $this->connect($values);
        $mysqlv = $this->getMySQLVersion();
        

        // upgrade setting_to_value table
        $this->setTables($tbl_pref, '');

        // common settings to value
        $ret = $this->setCommonSettings($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
}


// ======== 5.0 ======================= >

class SetupModelUpgrade_20_to_502 extends SetupModelUpgrade
{	

	function execute($values) {

		// 2.0 to 4.5.2
		$upgrade = new SetupModelUpgrade_20_to_452();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 4.5.2 to 5.0.2
		$upgrade = new SetupModelUpgrade_452_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}		

		return true;
	}
}


class SetupModelUpgrade_301_to_502 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.0.1 to 4.5.2
		$upgrade = new SetupModelUpgrade_301_to_452();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 4.5.2 to 5.0.2
		$upgrade = new SetupModelUpgrade_452_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		return true;
	}
}


class SetupModelUpgrade_352_to_502 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.5.2 to 4.5.2
		$upgrade = new SetupModelUpgrade_352_to_452();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 4.5.2 to 5.0.2
		$upgrade = new SetupModelUpgrade_452_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}
		
		return true;
	}
}


class SetupModelUpgrade_402_to_502 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	


	function execute($values) {

		// 4.0.2 to 4.5.2
		$upgrade = new SetupModelUpgrade_402_to_452();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		// 4.5.2 to 5.0.2
		$upgrade = new SetupModelUpgrade_452_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

		return true;
	}
}

// 451, 452, 453 to 502 
class SetupModelUpgrade_452_to_502 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');


	function execute($values) {

		$file = 'db/upgrade_4.5.2_to_5.0.1.sql';
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));

		$tbl_pref = $values['tbl_pref'];
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();

		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
		$ret = $this->executeArray($sql_array);
		if($ret !== true) {
			return $ret;
		}	

		// upgrade setting_to_value table
		$this->setTables($tbl_pref, '');

		// common settings to value
		$ret = $this->setCommonSettings($values);
		if($ret !== true) {
			return $ret;
		}

        // set language 
        if(!empty($values['lang'])) {
    		$ret = $this->setLanguage($values['lang']);
    		if($ret !== true) {
    			return $ret;
    		}   
        }

		return true;
	}	
	 
}

// 4.5, 4.5.1, 4.5.2, 4.5.3 to 502
class SetupModelUpgrade_45_to_502 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');


	function execute($values) {

		$file = 'db/upgrade_4.5_to_4.5.1.sql';
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));

		$tbl_pref = $values['tbl_pref'];
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();
		
		
		// 4.5 to 4.5.1 if required
		// if $ret is not bool then sql error, string with error
		$ret = $this->isVersion45($tbl_pref);
        if(is_bool($ret) === false) {
            return $ret;
        }
		
		if($ret) {
		    $sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
            $ret = $this->executeArray($sql_array);
    		if($ret !== true) {
    			return $ret;
    		}
		}
		
		
        // 4.5.2 to 5.0.2
		$upgrade = new SetupModelUpgrade_452_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}
		
		return true;
	}
		
	
	// to find out if db version 4.5 or above
	function isVersion45($tbl_pref) {
        $sql = "SHOW COLUMNS FROM `{$tbl_pref}news` WHERE Field = 'hits'";
		$result = &$this->db->_Execute($sql);
				
		if(!$result) {
			return DbUtil::error($sql);
		}
		
		return ($result->FetchRow()) ? false : true;
	}
	 
}

// 50, 501 to 502
class SetupModelUpgrade_50_to_502 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');


    function execute($values) {

        $file = 'db/upgrade_5.0_to_5.0.1.sql';
        $data = FileUtil::read($file);
        $this->setSetupData(array('sql_file' => $file));

        $tbl_pref = $values['tbl_pref'];
        $this->connect($values);
        $mysqlv = $this->getMySQLVersion();


		// 5.0 to 5.0.1 if required
		// if $ret is not bool then sql error, string with error
		$ret = $this->isVersion50($tbl_pref);
		if(is_bool($ret) === false) {
            return $ret;
		}
		
		if($ret) {
    		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
            $ret = $this->executeArray($sql_array);
    		if($ret !== true) {
    			return $ret;
    		}
		}
		

        // upgrade setting_to_value table
        $this->setTables($tbl_pref, '');

        // common settings to value
        $ret = $this->setCommonSettings($values);
        if($ret !== true) {
            return $ret;
        }

        return true;
    }
    
    
	// to find out if db version 5.0 or below
	function isVersion50($tbl_pref) {
        $sql = "SELECT id FROM `{$tbl_pref}setting` WHERE id = '280'";
		$result = &$this->db->_Execute($sql);
				
		if(!$result) {
			return DbUtil::error($sql);
		}
		
		return ($result->Fields('id')) ? false : true;
	}
}


// ======== 5.5 ======================= >

class SetupModelUpgrade_20_to_551 extends SetupModelUpgrade
{	

	function execute($values) {

		// 2.0 to 5.0.2
		$upgrade = new SetupModelUpgrade_20_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.0.2 to 5.5.1
        $upgrade = new SetupModelUpgrade_502_to_551();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_301_to_551 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.0.1 to 5.0.2
		$upgrade = new SetupModelUpgrade_301_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.0.2 to 5.5.1
        $upgrade = new SetupModelUpgrade_502_to_551();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_352_to_551 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.5.2 to 5.0.2
		$upgrade = new SetupModelUpgrade_352_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}
		
        // 5.0.2 to 5.5.1
        $upgrade = new SetupModelUpgrade_502_to_551();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
		
		return true;
	}
}


class SetupModelUpgrade_402_to_551 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	


	function execute($values) {

		// 4.0.2 to 5.0.2
		$upgrade = new SetupModelUpgrade_402_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.0.2 to 5.5.1
        $upgrade = new SetupModelUpgrade_502_to_551();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_45_to_551 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');
	

	function execute($values) {

		// 4.5 - 4.5.2 to 5.0.2
		$upgrade = new SetupModelUpgrade_45_to_502();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.0.2 to 5.5.1
        $upgrade = new SetupModelUpgrade_502_to_551();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


// 50, 501, 502 to 551
class SetupModelUpgrade_50_to_551 extends SetupModelUpgrade
{
	var $tbl_pref_custom;
	//var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting', 'trigger');	


	function execute($values) {

		$file = 'db/upgrade_5.0.2_to_5.5.1.sql';
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));

		$tbl_pref = $values['tbl_pref'];
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();
		

		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
        $ret = $this->executeArray($sql_array);
        if($ret !== true) {
            return $ret;
        }    

		// upgrade setting_to_value table
		$this->setTables($tbl_pref, '');

        // common settings to value
        $ret = $this->setCommonSettings($values);
        if($ret !== true) {
         return $ret;
        }
        
        // set language 
        if(!empty($values['lang'])) {
            $ret = $this->setLanguage($values['lang']);
            if($ret !== true) {
                return $ret;
            }
        }

        // set default sql
        $ret = $this->setDefaultSql($values, false);
        if($ret !== true) {
            return $ret;
        }


		return true;
	}
}


// the same as SetupModelUpgrade_50_to_551
// added for compability view
class SetupModelUpgrade_502_to_551 extends SetupModelUpgrade
{

	function execute($values) {

        // 5.0 to 5.5.1
        $upgrade = new SetupModelUpgrade_50_to_551();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_55_to_551 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');


    function execute($values) {

        $file = 'db/upgrade_5.5_to_5.5.1.sql';
        $data = FileUtil::read($file);
        $this->setSetupData(array('sql_file' => $file));

        $tbl_pref = $values['tbl_pref'];
        $this->connect($values);
        $mysqlv = $this->getMySQLVersion();


        $sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
        $ret = $this->executeArray($sql_array);
        if($ret !== true) {
            return $ret;
        }

        // upgrade setting_to_value table
        $this->setTables($tbl_pref, '');

        // common settings to value
        $ret = $this->setCommonSettings($values);
        if($ret !== true) {
            return $ret;
        }

        // set language 
        if(!empty($values['lang'])) {
            $ret = $this->setLanguage($values['lang']);
            if($ret !== true) {
                return $ret;
            }
        }

        return true;
    }
}




// ======== 6.0 ======================= >

class SetupModelUpgrade_20_to_602 extends SetupModelUpgrade
{	

	function execute($values) {

		// 2.0 to 5.5.1
		$upgrade = new SetupModelUpgrade_20_to_551();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.5.1 to 6.0.2
        $upgrade = new SetupModelUpgrade_551_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_301_to_602 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.0.1 to 5.5.1
		$upgrade = new SetupModelUpgrade_301_to_551();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.5.1 to 6.0.2
        $upgrade = new SetupModelUpgrade_551_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_352_to_602 extends SetupModelUpgrade
{	

	function execute($values) {

		// 3.5.2 to 5.5.1
		$upgrade = new SetupModelUpgrade_352_to_551();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}
		
        // 5.5.1 to 6.0.2
        $upgrade = new SetupModelUpgrade_551_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }
		
		return true;
	}
}


class SetupModelUpgrade_402_to_602 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	


	function execute($values) {

		// 4.0.2 to 5.5.1
		$upgrade = new SetupModelUpgrade_402_to_551();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.5.1 to 6.0.2
        $upgrade = new SetupModelUpgrade_551_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_45_to_602 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	


	function execute($values) {

		// 4.5.* to 5.5.1
		$upgrade = new SetupModelUpgrade_45_to_551();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.5.1 to 6.0.2
        $upgrade = new SetupModelUpgrade_551_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_50_to_602 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	


	function execute($values) {

		// 5.0.* to 5.5.1
		$upgrade = new SetupModelUpgrade_50_to_551();
		$ret = $upgrade->execute($values);
		if($ret !== true) {
			return $ret;
		}

        // 5.5.1 to 6.0.2
        $upgrade = new SetupModelUpgrade_551_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


class SetupModelUpgrade_551_to_602 extends SetupModelUpgrade
{
	var $tbl_pref_custom;
    var $tables = array('entry_draft_workflow', 'entry_draft_workflow_to_assignee');
	var $custom_tables = array('setting_to_value', 'setting', 'trigger');	


	function execute($values) {

		$file = 'db/upgrade_5.5.1_to_6.0.2.sql';
		$data = FileUtil::read($file);
		$this->setSetupData(array('sql_file' => $file));

		$tbl_pref = $values['tbl_pref'];
		$this->connect($values);
		$mysqlv = $this->getMySQLVersion();
		

		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
        $ret = $this->executeArray($sql_array);
        if($ret !== true) {
            return $ret;
        }    

		// upgrade setting_to_value table
		$this->setTables($tbl_pref, '');

        
        // upgrade draft assignee
		$ret = $this->upgradeDraftAssignee();
		if($ret !== true) {
			return $ret;
		}

        // common settings to value
        $ret = $this->setCommonSettings($values);
        if($ret !== true) {
         return $ret;
        }
        
        // set language 
        if(!empty($values['lang'])) {
            $ret = $this->setLanguage($values['lang']);
            if($ret !== true) {
                return $ret;
            }
        }

		return true;
	}
    
    
    function upgradeDraftAssignee() {
        
        require_once 'eleontev/SQL/MultiInsert.php';
        
        // get assignees
        $sql = "SELECT draft_id, id AS draft_workflow_id, assignee 
            FROM {$this->tbl->entry_draft_workflow} 
            WHERE assignee != ''";
		$result =& $this->db->Execute($sql);
		if(!$result) {
			return DbUtil::error($sql);
		}
	
        // add assignee to workflow_to_assignee 
        $data = array();
		while($row = $result->FetchRow()) {
            $assignees = explode(',', $row['assignee']);
            foreach($assignees as $assignee_id) {
                $data[] = array($row['draft_id'], $row['draft_workflow_id'], $assignee_id);
            }
		}
        
        if($data) {
            $chunks = array_chunk($data, 30);
            foreach($chunks as $v) {
                    
                $sql = MultiInsert::get("INSERT IGNORE {$this->tbl->entry_draft_workflow_to_assignee} (draft_id, draft_workflow_id, assignee_id) VALUES ?", $v);
        
    			$result1 = $this->db->_Execute($sql);
    			if(!$result1) {
    				return DbUtil::error($sql);
    			}
            }
        }
        
        // drop assignee field
        $sql = "ALTER TABLE {$this->tbl->entry_draft_workflow} DROP assignee";
		$result1 = $this->db->_Execute($sql);
		if(!$result1) {
            
            // empty workflow_to_assignee if error
            $sql2 = "TRUNCATE TABLE {$this->tbl->entry_draft_workflow_to_assignee}";
    		$result1 = $this->db->_Execute($sql2);
            
			return DbUtil::error($sql);
		}
	
		return true;
    }
}


// 55, 551 to 602
class SetupModelUpgrade_55_to_602 extends SetupModelUpgrade
{

	var $tbl_pref_custom;
	var $tables = array();
	var $custom_tables = array('setting_to_value', 'setting');	


	function execute($values) {
        
		// 5.5.* to 5.5.1 if required
		// if $ret is not bool then sql error, string with error
        // $this->connect($values);
        // $tbl_pref = $values['tbl_pref'];
        //
        // $ret = $this->isVersion55($tbl_pref);
        // if(is_bool($ret) === false) {
        //     return $ret;
        // }
        
        // we can't safely determine if version 5.0
        // so always run this upgrade, it is safe
        $ret = true;
        
        if($ret) {
            $upgrade = new SetupModelUpgrade_55_to_551();
            $ret = $upgrade->execute($values);
            if($ret !== true) {
                return $ret;
            }
        }

        // 5.5.1 to 6.0.2
        $upgrade = new SetupModelUpgrade_551_to_602();
        $ret = $upgrade->execute($values);
        if($ret !== true) {
            return $ret;
        }

		return true;
	}
}


// 60, 601 to 602
class SetupModelUpgrade_60_to_602 extends SetupModelUpgrade
{

    var $tbl_pref_custom;
    var $tables = array();
    var $custom_tables = array('setting_to_value', 'setting');


    function execute($values) {
        
        $file = 'db/upgrade_6.0_to_6.0.1.sql';
        $data = FileUtil::read($file);
        $this->setSetupData(array('sql_file' => $file));

        $tbl_pref = $values['tbl_pref'];
        $this->connect($values);
        $mysqlv = $this->getMySQLVersion();


		// 6.0 to 6.0.1 if required
		// if $ret is not bool then sql error, string with error
		$ret = $this->isVersion60($tbl_pref);
		if(is_bool($ret) === false) {
            return $ret;
		}
		
		if($ret) {
    		$sql_array = ParseSqlFile::parsePreparedString($data, array('kbp_' => $tbl_pref), $mysqlv);
            $ret = $this->executeArray($sql_array);
    		if($ret !== true) {
    			return $ret;
    		}
		}

        // upgrade setting_to_value table
        $this->setTables($tbl_pref, '');

        // common settings to value
        $ret = $this->setCommonSettings($values);
        if($ret !== true) {
            return $ret;
        }

        // set language 
        if(!empty($values['lang'])) {
            $ret = $this->setLanguage($values['lang']);
            if($ret !== true) {
                return $ret;
            }
        }

        return true;
    }
    
    
	// to find out if db version 6.0 or below
	function isVersion60($tbl_pref) {
        $sql = "SELECT id FROM `{$tbl_pref}setting` WHERE id = '378'";
		$result = &$this->db->_Execute($sql);
				
		if(!$result) {
			return DbUtil::error($sql);
		}
		
		return ($result->Fields('id')) ? false : true;
	}
}
?>