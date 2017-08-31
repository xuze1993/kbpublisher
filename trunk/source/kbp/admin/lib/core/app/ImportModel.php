<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KnowledgebasePublisher package                   |
// | KnowledgebasePublisher - web based knowledgebase publishing tool          |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2008 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+

require_once 'eleontev/Util/FileUtil.php';
require_once 'eleontev/SQL/MultiInsert.php';


class ImportModel
{

    var $model;
    var $index = array('pri' => 'PRIMARY', 'uni' => 'UNIQUE', 'mul' => 'INDEX');

    function __construct($model) {
        $this->model = $model;
    }


    function getFields() {
        $sql = "DESCRIBE {$this->model->tbl->table}";
        $result = $this->model->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function getIndex() {
        $sql = "SHOW INDEX FROM {$this->model->tbl->table}";
        $result = $this->model->db->Execute($sql) or die(db_error($sql));
        $data = $result->GetArray();

        $_data = array();
        foreach($data as $k => $v) {
            $_data[$v['Column_name']] = $v;
        }

        return $_data;
    }


    function getMySQLVersion($num = 3) {
        $sql = "show variables like 'version'";
        $result = $this->model->db->Execute($sql) or die(db_error($sql));
        $row = $result->FetchRow();
        $version = $row['Value'];
        $version = preg_replace("#[^\d]#", '', $version);
        $version = (int) substr($version, 0, $num);

        return $version;
    }


    // // As of MySQL 5.0.3, the column list can contain either column names or user variables.
    function isMoreFieldCompatible() {
        // $version = $this->getMySQLVersion();
        // return ($version >= 503);

        // always true if php implementation
        return true;
    }


    function getMoreFields($fields, $data, $filename) {
        return array();
    }


/*
    function getImportSql($fields, $data, $filename, $options) {

        if($this->isMoreFieldCompatible()) {
            $set = $this->getMoreFields($fields, $data, $filename);
        }

        $set = (!empty($set)) ? 'SET ' . implode(',', $set) : '';
        $fields = implode(',', $fields);

        $load_command = ($options['load_command'] == 1) ? 'LOAD DATA LOCAL INFILE' : 'LOAD DATA INFILE';
        $fields_terminated = $options['fields_terminated'];
        $optionally_enclosed = $options['optionally_enclosed'];
        $lines_terminated = stripslashes($options['lines_terminated']);


        $sql = "
        {$load_command} '{$filename}' IGNORE
        INTO TABLE {$this->model->tbl->table}
        FIELDS TERMINATED BY '{$fields_terminated}' OPTIONALLY ENCLOSED BY '{$optionally_enclosed}'
          LINES TERMINATED BY '{$lines_terminated}'
        ({$fields})
        {$set}";

        //echo "<pre>"; print_r($sql); echo "</pre>";
        //exit;

        return $sql;
    }



    function import($fields, $data, $filename, $options) {
        $sql = $this->getImportSql($fields, $data, $filename, $options);
        $result = $this->model->db->Execute($sql);// or die(db_error($sql, true));

        if($result) {
            return true;
        } else {
            return DbUtil::getError($this->model->db->ErrorNo(), $this->model->db->ErrorMsg(), $sql, 'full');
        }
    }
*/


    // php implementation, to avoid load data in file
    // isMoreFieldCompatible should return true
    function getImportSql($fields, $data, $filename, $options) {

        $fields_terminated = stripslashes($options['fields_terminated']);
        $optionally_enclosed = stripslashes($options['optionally_enclosed']);
        $lines_terminated = stripslashes($options['lines_terminated']);
        $lines_terminated = str_replace(array('\n','\r','\t'), array("\n","\r","\t"), $lines_terminated); // to double quoted


        $mfields = array();
        $mvalues = array();
        if($this->isMoreFieldCompatible()) {

            $more_fields = $this->getMoreFields($fields, $data, $filename);
            if($more_fields) {
                foreach($more_fields AS $v) {
                    $v2 = explode('=', $v);
                    $mfields[] = trim($v2[0]);
                    $mvalues[] = trim($v2[1]);
                }
            }
        }


        $ret = FileUtil::read($filename);
        if(!$ret) {
            return 'Cannot read file ' . $filename;
        }

        $ins = new MultiInsert;
        $ins->setFields($fields, $mfields);

        $fdata = explode("$lines_terminated", $ret);
        $fdata = array_filter($fdata);
        $fdata = array_chunk($fdata, 20);

        foreach(array_keys($fdata) as $k) {

            $values = array();
            foreach(array_keys($fdata[$k]) as $k2) {
                $values[$k2] = str_getcsv($fdata[$k][$k2], $fields_terminated, $optionally_enclosed);
            }

            $ins->setValues($values, $mvalues);
            $sql = $ins->getSql($this->model->tbl->table, 'INSERT IGNORE');

            $result = $this->model->db->Execute($sql);// or die(db_error($sql, true));
            if(!$result) {
                $sql_error = (_strlen($sql) > 400) ? _substr($sql, 0, 400) . ' ...' : $sql;
                $sql_error = str_replace('),(', "),<br>(", $sql_error);
                return DbUtil::getError($this->model->db->ErrorNo(), $this->model->db->ErrorMsg(), $sql_error, 'full');
            }

            // echo '<pre>values: ', print_r($values, 1), '</pre>';
            // echo '<pre>sql: ', print_r(str_replace('),(', "),<br>(", $sql), 1), '</pre>';
            // echo '<pre>', print_r("=================", 1), '</pre>';
        }

        // echo '<pre>file: ', print_r($ret, 1), '</pre>';
        // echo '<pre>fields: ', print_r($fields, 1), '</pre>';
        // echo '<pre>mfields: ', print_r($mfields, 1), '</pre>';
        // echo '<pre>', print_r("=================", 1), '</pre>';
        // echo '<pre>valies: ', print_r($values, 1), '</pre>';
        // echo '<pre>mvalues: ', print_r($mvalues, 1), '</pre>';

        // echo '<pre>fdata: ', print_r($fdata, 1), '</pre>';
        // echo '<pre>filename: ', print_r($filename, 1), '</pre>';
        // exit;

        return true;
    }


    function import($fields, $data, $filename, $options) {
        return $this->getImportSql($fields, $data, $filename, $options);
    }


    // $filesize in KB, 1020kb = 10mb
    function upload($filesize = 10240) {

        require_once 'eleontev/Dir/Uploader.php';

        $upload = new Uploader;
        $upload->store_in_db = false; // we move file
        //$upload->setAllowedType('text/plain');
        $upload->setAllowedExtension('txt', 'csv');
        //$upload->setDeniedExtension();

        $size_allowed = WebUtil::getIniSize('upload_max_filesize')/1024; // in kb
        $size_max = ($size_allowed < $filesize) ? $size_allowed : $filesize;
        $upload->setMaxSize($size_max);

        $upload->setUploadedDir(APP_CACHE_DIR);

        $f = $upload->upload($_FILES);

        if(isset($f['bad'])) {
            $f['error_msg'] = $upload->errorBox($f['bad']);
        } else{
            $f['filename'] = APP_CACHE_DIR . $f['good'][1]['name'];
        }

        return $f;
    }
}


/*
LOAD DATA [LOW_PRIORITY | CONCURRENT] [LOCAL] INFILE 'file_name'
    [REPLACE | IGNORE]
    INTO TABLE tbl_name
    [CHARACTER SET charset_name]
    [FIELDS
        [TERMINATED BY 'string']
        [[OPTIONALLY] ENCLOSED BY 'char']
        [ESCAPED BY 'char']
    ]
    [LINES
        [STARTING BY 'string']
        [TERMINATED BY 'string']
    ]
    [IGNORE number LINES]
    [(col_name_or_user_var,...)]
    [SET col_name = expr,...]
*/
?>