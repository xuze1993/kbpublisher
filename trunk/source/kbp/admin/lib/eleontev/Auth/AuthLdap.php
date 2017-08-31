<?php

class AuthLdap 
{
    
    private $options = array();
    private $_connection = null;
    private $regexp_fields = array(
        'first' => 'remote_auth_map_fname',
        'last' => 'remote_auth_map_lname'
        );
        
    private $fields_names = array(
        'remote_auth_map_fname' => 'first name',
        'remote_auth_map_lname' => 'last name',
        'remote_auth_map_email' => 'email'
        );
        
    
    public function __construct($options) {
        $this->validateOptions($options);
        $this->options = $options;
    }
        
    
    private function validateOptions($options) {
        $required = array(
            'ldap_host', 'ldap_port', 'ldap_base_dn',
            'remote_auth_map_fname', 'remote_auth_map_lname',
            'remote_auth_map_email', 'remote_auth_map_ruid'
            );
        
        foreach($required as $option) {
            if(empty($options[$option])) {
                throw new Exception("Option {$option} can't be empty.");
            }    
        }
    }
    
    
    public function validateCompability() {
        if (!function_exists('ldap_connect')) {
            throw new Exception('No LDAP support for PHP.  See: http://www.php.net/ldap');
        }
    }
    
    
    public function connect() {
        
        $hostname = $this->options['ldap_host'];
        if (!empty($this->options['ldap_use_ssl'])) {
            $hostname = 'ldaps://' . $hostname;
        }
        
        $this->_connection = @ldap_connect($hostname, $this->options['ldap_port']);
        if (!is_resource($this->_connection)) {
            throw new Exception("Could not connect to {$this->options['ldap_host']}");
        }
        
        if (!empty($this->options['ldap_use_tls'])) {
            if (!@ldap_start_tls($this->_connection)) {
                throw new Exception(ldap_error($this->_connection));
            }
        }

        if (!empty($this->options['ldap_use_v3'])) {
            if (!@ldap_set_option($this->_connection, LDAP_OPT_PROTOCOL_VERSION, 3)) {
                throw new Exception('Can\'t set protocol version to 3');
            }
        }
        
        @ldap_set_option($this->_connection, LDAP_OPT_REFERRALS, 0);
    }
    
    
    public function bind($user_dn, $password) {
        if (!$user_dn) { // anonymous
            $result = @ldap_bind($this->_connection);
            
        } else {
            $result = @ldap_bind($this->_connection, $user_dn, $password);
        }
        
        if (!$result) {
            throw new Exception(ldap_error($this->_connection));
        }
    }
    
    
    public function searchUser($username, $test = false) {
        
        $filter = '(%s=%s)';
        $filter = sprintf($filter, $this->options['remote_auth_map_ruid'], $username);
        
        $result = @ldap_search($this->_connection, $this->options['ldap_base_dn'], $filter);
        if (!$result) {
            throw new Exception(ldap_error($this->_connection));
        }

        $count = ldap_count_entries($this->_connection, $result);
        
        if ($count === false) {
            throw new Exception(ldap_error($this->_connection));
            
        } elseif ($count === 0) {
            
            $msg_ = 'User not found.';
            if($test) {
                $msg_ = 'User not found. Make sure that test/debug credentials are valid and mapping field "Remote User Id" matches your LDAP unique user identifier.';
            }
            
            throw new Exception($msg_);
            
        } elseif($count > 1) {
            throw new Exception('Found more than one user for given username.');
        }
        
        $user_data = array();
        $entry = ldap_first_entry($this->_connection, $result);
        
        $result = ldap_get_attributes($this->_connection, $entry);
                
        foreach ($result as $k => $v) {
            if (is_array($v)) {
                $subcount = $v['count'];
                $user_data[$k] = array();
                
                for ($j = 0; $j < $subcount; $j ++) {
                    $user_data[$k][$j] = $v[$j];
                }
            }
        }
        
        $this->checkRequiredAttributes($user_data);
        
        // add some data
        $user_data['dn'] = ldap_get_dn($this->_connection, $entry);
                
        return $user_data;
    }
    
    
    private function checkRequiredAttributes($user_data) {
        $required_attrs = array('remote_auth_map_email' => $this->options['remote_auth_map_email'],
                                'remote_auth_map_ruid' => $this->options['remote_auth_map_ruid']); 
        
        foreach ($this->regexp_fields as $field) {
            $r = explode('|', $this->options[$field]);
            if (!empty($r[1])) {
                $required_attrs[$field] = $r[0];
            } else {
                $required_attrs[$field] = $this->options[$field];
            }
        }
        
        // check the required user's attributes
        foreach ($required_attrs as $k => $attr) {
            if (empty($user_data[$attr])) {
                $msg = "Unable to fetch user's %s - it cannot be empty, given attribute '%s' is empty or does not exist.";
                throw new Exception(sprintf($msg, $this->fields_names[$k], $attr));
            }
        }
    }
    
    
    public function getUserMapped($ldap_user) {
        
        $user = array();
        
        // get the user's first and last names if the entry holds his full name attribute
        foreach ($this->regexp_fields as $k => $v) {
            $r = explode('|', $this->options[$v]);
            if (!empty($r[1])) { // regexp should be applied
                if (!preg_match($r[1], $ldap_user[$r[0]][0], $matches)) {
                    throw new Exception("Unable split the user's $k name from his full name.");
                }
                
                $user[$k . '_name'] = $matches[1];
            } else {
                $user[$k . '_name'] = $ldap_user[$this->options[$v]][0];
            }
        }
        
        // email
        if (!empty($this->options['remote_auth_email_template'])) {
            //$user['email'] = str_replace(..., ..., $this->options['remote_auth_email_template'])
        } else {
            $user['email'] = $ldap_user[$this->options['remote_auth_map_email']][0];
        }
        
        $user['remote_user_id'] = $ldap_user[$this->options['remote_auth_map_ruid']][0];
        
        
        // group and custom mapping
        $group_type = $this->options['remote_auth_group_type'];
        if ($group_type == 'static') {
            $user_groups = $this->searchGroupMembership($ldap_user['dn']); // searching in groups
        }
        
        $credentials = array('priv', 'role');
        
        foreach ($credentials as $key) {
            
            $kbp_field = sprintf('%s_id', $key);
            $user[$kbp_field] = 'off';
            
            $group_rules_key = sprintf('remote_auth_map_group_to_%s', $key);
            $custom_rules_key = sprintf('remote_auth_map_%s_id', $key);
            
            if (!empty($this->options[$group_rules_key])) { // groups
                $user[$kbp_field] = 0;
                                
                $rules = explode("\n", $this->options[$group_rules_key]);
                foreach ($rules as $rule) {
                    $r = explode('|', trim($rule));
                    if (count($r) != 2) {
                        throw new Exception("Bad format for the '$key' field.");
                    }
                    
                    $ldap_group = $r[0];
                    $kbp_param = $r[1];
                    
                    if ($group_type == 'static') {
                        if (in_array($ldap_group, $user_groups)) {
                            $user[$kbp_field] = $kbp_param;
                            break;
                        }
                        
                    } else {
                        $user_to_group_attr = @$this->options['remote_auth_group_attribute'];
                        $user_value = @$ldap_user[$user_to_group_attr];
                        
                        if (!empty($user_value)) {
                            foreach($user_value as $v) {
                                if ($ldap_group == trim($v)) {
                                    $user[$kbp_field] = $kbp_param;
                                    break 2;
                                }
                            }
                        }
                    }
                }
                
            }
            
            if (!empty($this->options[$custom_rules_key])) { // custom
                if ($user[$kbp_field] == 'off') {
                    $user[$kbp_field] = 0;
                }
                
                $rules = explode("\n", $this->options[$custom_rules_key]);
                
                foreach ($rules as $rule) {
                    $r = explode('|', trim($rule));
                    
                    $custom_attribute = $r[0];
                    $custom_attribute_value = $r[1];
                    $kbp_param = $r[2];
                    
                    $user_value = @$ldap_user[$custom_attribute];
                    if(isset($user_value[0])) {
                        if ($custom_attribute_value == $user_value[0]) { // keep for compability 
                            $user[$kbp_field] = $kbp_param;
                            break;
                        
                        // wildcard support 2016-08-02  
                        } elseif (fnmatch($custom_attribute_value, $user_value[0], FNM_CASEFOLD)) {   
                            $user[$kbp_field] = $kbp_param;
                            break;
                        }
                    }
    
                }
            }
        }
        
        return $user;
    }
    
    
    private function searchGroupMembership($user_dn) {
        
        $this->bind($this->options['ldap_connect_dn'], $this->options['ldap_connect_password']);
        
        $user_dn = str_replace(array('\\', '(', ')', '*'), array('\\5c', '\28', '\29', '\2A'), $user_dn);
        $filter = sprintf('(%s=%s)', @$this->options['remote_auth_group_attribute'], $user_dn);
        $result = @ldap_search($this->_connection, $this->options['ldap_base_dn'], $filter, array('dn'));
        
        if (!$result) {
            throw new Exception(ldap_error($this->_connection));
        }
        
        $entries = ldap_get_entries($this->_connection, $result);
        
        $user_groups = array();
        for ($i = 0; $i < $entries['count']; $i ++) {
            $user_groups[] = $entries[$i]['dn'];
        }
        
        return $user_groups;
    }
    
    
    
    public function getGroupList() {
        $filter = '(|(objectCategory=group)(objectClass=groupOfNames)(objectClass=groupOfUniqueNames)(objectClass=posixGroup))';
        
        $result = @ldap_search($this->_connection, $this->options['ldap_base_dn'], $filter);
        if (!$result) {
            throw new Exception(ldap_error($this->_connection));
        }

        $count = ldap_count_entries($this->_connection, $result);
        
        if ($count === false) {
            throw new Exception(ldap_error($this->_connection));
            
        } elseif ($count === 0) {
            $msg_ = 'LDAP groups not found.';
            throw new Exception($msg_);
        }
        
        $entries = ldap_get_entries($this->_connection, $result);
        
        $groups_data = array();
        for ($i = 0; $i < $entries['count']; $i ++) {
            $dn = $entries[$i]['dn'];
            
            if (!empty($entries[$i]['cn'])) {
                $name = $entries[$i]['cn'][0];
                
            } elseif (!empty($entries[$i]['sAMAccountName'])) {
                $name = $entries[$i]['sAMAccountName'][0];
                
            } else {
                $name = $dn;
            }
            
            
            $groups_data[$dn] = $name;
        }
        
        return $groups_data;
    }
    
    
    function getUserToken($ldap_user) {
        return md5(serialize($ldap_user));
    }
    
    
    static function getUserTokenByUid($uid, $setting) {
        
        $ret = false;
        
        try {
            
            $ldap = new AuthLdap($setting);
            
            $ldap->connect();
            $ldap->bind($setting['ldap_connect_dn'], $setting['ldap_connect_password']);
        
            $ldap_user = $ldap->searchUser($uid);
            if(!empty($ldap_user)) {
                $ret = $ldap->getUserToken($ldap_user);
            }
        
            return $ret;
        
        } catch (Exception $e) {
            return $ret;
        }
    }
   
}
?>