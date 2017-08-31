<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+

class AppController extends Controller
{
    
    var $action = false;
    var $page_to_return;
    
    var $query = array('module'   =>'module',
                       'page'     =>'page',
                       'sub_page' =>'sub_page',
                       'action'   =>'action',
                       'id'       =>'id',
                       'show_msg' =>'show_msg'
                       );
    
    
    var $actions = array('insert'=>'insert',
                         'update'=>'update',
                         'delete'=>'delete',
                         'status'=>'status',
                         'detail'=>'detail'
                         );
    
    var $id_key = 'id';
    var $self_page;
    
    // it should be added to params is isset
    // when we return from good operations or from "cancel" from form
    var $params = array('module', 'page', 'sub_page');
    var $more_params = array('bpr', 'sort', 'order', 'letter', 'filter', 
                             'popup', 'field_name', 'field_id', 'referer', 'bp', 'bpt', 'no_attach', 'close'
                             );
    
    var $custom_return_params = array();
    var $custom_page_to_return;
    
    var $delay_time = '1000'; // js delay time on after action screen
    var $lang = APP_LANG;
    var $module_dir = APP_MODULE_DIR;
    var $extra_module_dir = APP_EXTRA_MODULE_DIR;
    var $extra_modules = array();
    
    var $working_dir;
    var $full_page;
    var $full_page_params;
    var $arg_separator = '&amp;';
    
    var $msg = array();
    var $encoding;
    
    
    function __construct() {
        
        parent::__construct();
        
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        $this->encoding = $conf['lang']['meta_charset'];
        
        $this->self_page = $_SERVER['PHP_SELF'];
        $this->action = $this->getRequestVar('action');
        $this->module = $this->getRequestVar('module');
        $this->page   = $this->getRequestVar('page');
        $this->sub_page = $this->getRequestVar('sub_page');
        
        $this->working_dir = $this->module_dir . $this->module . '/';
        $this->setCommonLink();
    }
    
    
    // when we need to add additonal lang file
    // actually we need it to add msg to view 
    function addMsg($file_name, $module = false) {
        
        if($module) {
            $file_name = AppMsg::getModuleMsgFile($module, $file_name);
        } else {
            $file_name = AppMsg::getCommonMsgFile($file_name);
        }        
        
        $this->msg = array_merge($this->msg, AppMsg::parseMsgs($file_name));
    }    
    
    
    function getRequestVar($var) {
        return (isset($_GET[$this->query[$var]])) ? urlencode(urldecode($_GET[$this->query[$var]])) : NULL;
    }
    
    
    function getRequestKey($key) {
        return @$this->query[$key];
    }
    
    
    function setRequestVar($var, $value) {
        $_GET[$this->query[$var]] = $value;
    }
    
    
    function getCurrentLink() {
        $query = http_build_query($_GET);
        return ($query) ? $_SERVER['PHP_SELF'] . '?' . $query : $_SERVER['PHP_SELF'];
    }
    
    
    function getCommonLink() {
        return $this->full_page;
    }
    
    
    //full_page
    function setCommonLink() {
        $this->full_page = $this->getFullPageUrl();
        $this->full_page_params = $this->getFullPageParams();
    }
    
    
    function getAction() {
        return $this->action;
    }
    
    
    // to change values for action key
    function setCustomAction($action, $value) {
        $this->actions[$action] = $value;
    }
    
    
    function getActionValue($action_key) {
        return @$this->actions[$action_key];
    }
    
    
    function getFullPageUrl() {
        return $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($this->getParams(), $this->getMoreParams()));
    }
    
    
    function getFullPageParams() {
        return array_merge($this->getParams(), $this->getMoreParams());
    }    
    
    
    // to generate main part of link
    function &_getParams($params) {
        $params_ = array();
        foreach($params as $v) {
            // echo '<pre>', print_r($v, 1), '</pre>';
            if(isset($_GET[$v])) {
                $params_[$v] = $_GET[$v];
            }
        }
        
        return $params_;
    }


    function &getParams() {
        return $this->_getParams($this->params);
    }

    
    function getMoreParams() {
        return $this->_getParams($this->more_params);
    }

    
    // set one more param
    //  more params always stay in GET if initialized
    function setMoreParams($val, $rebuilt_params = true) {
        $val = (is_array($val)) ? $val : array($val);
        foreach($val as $v) {
            $this->more_params[] = $v;    
        }
        
        if($rebuilt_params) {
            $this->setCommonLink();
        }        
    }
    
    
    function removeMoreParams($val, $rebuilt_params = true) {
        $val = (is_array($val)) ? $val : array($val);
        foreach($val as $v) {
            $key = array_search($v, $this->more_params);
            if($key) {
                unset($this->more_params[$key]);
            }            
        }
        
        if($rebuilt_params) {
            $this->setCommonLink();
        }
    }
    
    
    function getMoreParam($param) {
        return (!empty($_GET[$param])) ? urlencode($_GET[$param]) : false;
    }
    
    
    function setCustomPageToReturn($page, $unserialize = true) {
        $this->custom_page_to_return = ($unserialize) ? WebUtil::unserialize_url($page) : $page;
    }
        
    
    // set js delay time on after action screen
    // for $multiplier use 0.5, 1, 2 so on 
    // it will set delay time = 1000*$multiplier
    function setDelayTime($multiplier) {
        $this->delay_time = $this->delay_time*$multiplier;
    }
    
    
    function go($msg = 'success', $same_page = false, $after_msg = false, $action = false) {
        
        // activity
        require_once APP_MODULE_DIR . 'user/user/inc/UserActivityLog.php';
        $entry_id = $this->getRequestVar('id');
        $action = (!$action) ? $this->action : $action;
        
        $extra_data = false;
        if ($action == 'bulk') {
            
            if (in_array($this->rp->vars['bulk_action'], array('delete', 'trash'))) {
                $action = $this->rp->vars['bulk_action'];
                $entry_id = $this->rp->vars['id'];
                
            } else {
                $action = 'bulk_update';
                $extra_data = array(
                    'bulk_action' => $this->rp->vars['bulk_action'],
                    'ids' => $this->rp->vars['id']
                );
                
                if (!empty($this->rp->vars['value'][$extra_data['bulk_action'] . '_action'])) {
                    $extra_data['bulk_sub_action'] = $this->rp->vars['value'][$extra_data['bulk_action'] . '_action'];
                }
            }
        }
        
        if ($action != 'skip') {
            UserActivityLog::addAction($this->module, $this->page, $action, $entry_id, $extra_data);
        }
        
        // page to redirect
        $page = $this->getGoLink($msg, $same_page, $after_msg);
        
        if($same_page) {
            header("location: $page");
            exit();
        }
        
        // msg for crowl
        if($msg === 'success') {
            if(in_array($this->getRequestVar('action'), array('insert', 'clone'))) {
                $msg = 'added';
                
            } elseif($this->getRequestVar('action') == 'update') {
                $msg = 'updated';            
            
            } elseif(in_array($this->getRequestVar('action'), array('delete', 'trash'))) {
                $msg = 'deleted';
            
            } elseif($this->getRequestVar('action') == 'bulk') {
                $msg = 'data_updated';
            
            } else {
                $msg = 'success'; // Operation successfully completed
            }
        }

        $_SESSION['success_msg_'] = $msg;

        header("location: $page");
        exit();
    }
    
    
    function getGoLink($msg = 'success', $same_page = false, $after_msg = false) {
        
        ini_set('arg_separator.output', '&');
        $this->arg_separator = '&';
		$this->full_page = self::_replaceArgSeparator($this->full_page);
        
        // means on the same page dispaly message
        // message generated in index.php
        if($same_page) {
            $params = array($this->getRequestKey('show_msg') => $msg);
            $page = $this->full_page . $this->arg_separator . http_build_query($params);
            return $page;
        }
        
        // after msg
        $params = array();
        if($after_msg) {
            $params = array($this->getRequestKey('show_msg') => $after_msg);
            $this->full_page = $this->full_page . $this->arg_separator . http_build_query($params);
        }        
        
        // page to return 
        $page_to_return = $this->full_page;
        if($this->custom_page_to_return) {
			$page_to_return = self::_replaceArgSeparator($this->custom_page_to_return);
        }

        return $page_to_return;
    }


    function goUrl($url) {
        $url = $this->_replaceArgSeparator($url);
        header("Location: " . $url);
        exit();
    }

    
    function goPage($module = false, $page = false, $sub_page = false, $action = false, $more = array()) {
        $link = $this->getLink($module, $page, $sub_page, $action, $more);
        $link = $this->_replaceArgSeparator($link);
        
        header("location: $link");
        exit();        
    }
    
    
    function setWorkingDir($dirs = array()) {
    
        $ar[] = (isset($dirs['sub_page'])) ? $dirs['sub_page'] : $this->sub_page;
        $ar[] = (isset($dirs['page']))     ? $dirs['page']     : $this->page;
        $ar[] = (isset($dirs['module']))   ? $dirs['module']   : $this->module;
        
        if(!file_exists($this->module_dir . 'config_path.php')) { return; }
        require_once $this->module_dir . 'config_path.php';
        
        foreach($ar as $k => $v) {
            if(isset($conf_module[$v])){
                if(strpos($conf_module[$v], '{') !== false) {
                    $this->working_dir = str_replace('{extra_dir}', $this->extra_module_dir, $conf_module[$v]);
                } else {
                    $this->working_dir = $this->module_dir . $conf_module[$v];
                }
                
                break;
            }
        }
    }
    
    
    function getLink($module = false, $page = false, $sub_page = false, $action = false, $more = array()) {
        if($module == 'all') {
            return $this->_getLink($this->getRequestVar('module'),
                                   $this->getRequestVar('page'),
                                   $this->getRequestVar('sub_page'),
                                   $this->getRequestVar('action'),
                                   $more
                                   ); 
                                   
        } elseif($module == 'main') {
            return $this->_getLink($this->getRequestVar('module'),
                                   $this->getRequestVar('page'),
                                   $this->getRequestVar('sub_page')
                                   );
                                   
        } elseif($module == 'full') {
            $more = $this->getFullPageParams() + $more;
            unset($more['module'], $more['page'], $more['sub_page'], $more['action']);
            return $this->_getLink($this->getRequestVar('module'),
                                   $this->getRequestVar('page'),
                                   $this->getRequestVar('sub_page'),
                                   $this->getRequestVar('action'),
                                   $more
                                   );
                                   
                                                              
        } else {
            $module    = ($module == 'this') ? $this->getRequestVar('module') : $module;
            $page      = ($page == 'this') ? $this->getRequestVar('page') : $page;
            $sub_page  = ($sub_page == 'this') ? $this->getRequestVar('sub_page') : $sub_page;
            $action    = ($action == 'this') ? $this->getRequestVar('action') : $action;
            return $this->_getLink($module, $page, $sub_page, $action, $more);
        }
    }
    
    
    private function _getLink($module = false, $page = false, $sub_page = false, $action = false, $more = array()) {
        
        if($module)     { $link[1] = sprintf('%s=%s', $this->getRequestKey('module'),   $module); }
        if($page)       { $link[2] = sprintf('%s=%s', $this->getRequestKey('page'),     $page); }
        if($sub_page)   { $link[3] = sprintf('%s=%s', $this->getRequestKey('sub_page'), $sub_page); }
        if($action)     { $link[4] = sprintf('%s=%s', $this->getRequestKey('action'),   $action); }            
        
        $link = (@!$link) ? $this->self_page : $this->self_page . '?' . implode($this->arg_separator, $link);
        $link .= ($more) ? $this->arg_separator . http_build_query($more) :  '';
        
        return $link;
    }
    
    
    function getShortLink($module = false, $page = false, $sub_page = false, $action = false, $more = array()) {
        $this->self_page = '';
        $link = $this->getLink($module, $page, $sub_page, $action, $more);
        $link = str_replace('?', '', $link);
        
        return $link;
    }    
    
    
    function getFullLink($module = false, $page = false, $sub_page = false, $action = false, $more = array()) {
        $self_page = $this->self_page;
        $this->self_page = APP_ADMIN_PATH . 'index.php';
        $link = $this->getLink($module, $page, $sub_page, $action, $more);
        $this->self_page = $self_page;
        
        return $link;
    }    
    
    
    // truing to make compatible with IIS 
    function getAjaxLink($module = false, $page = false, $sub_page = false, $action = false, $more = array()) {
        
        if(empty($more) && isset($_GET['id'])) {
            $more = array('id' => (int) $_GET['id']);
        }
        
        $more['ajax'] = 1;
        $link = $this->getLink($module, $page, $sub_page, $action, $more);
        $link = $this->_replaceArgSeparator($link);
        
        return $link;
    }
    
    
    // to generate links to admin area and to be redireted after login 
    // used in e-mails and in client area
    static function getRefLink($module = false, $page = false, $sub_page = false, $action = false, 
                                $more = array(), $replace_arg = true) {

        $p = APP_ADMIN_PATH . 'index.php';
        if($module) {        
            $c = new AppController();
            $more['r'] = 1; // means save url if not logged and redirect to requestet page after login
            $link = $c->getShortLink($module, $page, $sub_page, $action, $more);
            if($replace_arg) {
                $link = $c->_replaceArgSeparator($link);
            }
            
            $p = $p . '?' . $link;
        }
        
        return $p;
    }
    
    
    function getClientLink($client_link, $serialize = false) {
        $cc = &$this->getClientController();
        @$link = $cc->getRedirectLink($client_link[0], $client_link[1], $client_link[2], $client_link[3], $client_link[4]);
        if($serialize) {
            $link = WebUtil::serialize_url($link);
        }
        
        // return 'client.php?page=' . $link; // 2015-08-17 avoid client.php to redirect to client area
        return $link;
    }


    // link to client_path/endpoint.php, add https if admin has https to avoid ssl errors
    static function getAjaxLinkToFile($type, $more = array()) {
        
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');        
        
        $client_path = APP_CLIENT_PATH;
        if($conf['ssl_admin']) {
            $client_path = str_replace('http://', 'https://', APP_CLIENT_PATH);
        }
        
        $link = $client_path . 'endpoint.php?type=' . $type;
        $link .= ($more) ? '&' . http_build_query($more) : '';
        $link = AppController::_replaceArgSeparator($link);
        return $link;
    }


	static function isAjaxCall() {
		return (!empty($_GET['ajax']));
	}


    static function _replaceArgSeparator($str) {
        return str_replace('&amp;', '&', $str);
    }
    
    
    static function &getClientController() {
    
        require_once APP_CLIENT_DIR . 'client/inc/KBClientController.php';
    
        $controller = new KBClientController();
        $controller->kb_path = APP_CLIENT_PATH;
        $controller->link_path = APP_CLIENT_PATH;
            
        return $controller;
    }
    
    
    function goView(&$obj, &$manager, $class, $values = array()) {
        
        require_once $this->working_dir . 'inc/' . $class . '.php';
        
        $view = new $class;
        $view->execute($obj, $manager, $values);
    }
    
    
    function getView(&$obj, &$manager, $class, $values = array()) {
    
        require_once $this->working_dir . 'inc/' . $class . '.php';
    
        $view = new $class;        
        return $view->execute($obj, $manager, $values);
    }
    
    
    // trying to load default classes
    // look in module dir and extra module dir
    function loadClass($class_name, $path = false) {
        
        $class_name = $class_name . '.php';
        
        if($path) {
            $p[] = $this->module_dir . $path . '/inc/';
            $p[] = $this->extra_module_dir . $path . '/inc/';
            
            foreach($p as $v) {
                if(file_exists($v . $class_name)) {
                    require_once $v . $class_name;
                }
            }
        } else {
            require_once $this->working_dir . 'inc/' . $class_name;
        }
    }
    
    
    function isClass($class_name, $path = false) {
        
        $class_name = $class_name . '.php';
        
        if($path) {
            $p[] = $this->module_dir . $path . '/inc/';
            $p[] = $this->extra_module_dir . $path . '/inc/';
            
            foreach($p as $v) {
                if(file_exists($v . $class_name)) {
                    return true;
                }
            }
        } else {
            if(file_exists($this->working_dir . 'inc/' . $class_name)) {
                return true;
            }
        }
        
        return false;
    }    
    
    
    function loadClasses($class_names) {
        foreach($class_names as $k => $v) {
            $path = (!is_numeric($k)) ? $k : false;
            $this->loadClass($v, $path);
        }
    }
    
    
/*
    function loadDefaultClasses() {
        //$classes = array();
        $this->default_class = 'FileCategory';
        $this->default_classes = array('Model', 'View_list', 'View_form');
        
        $this->loadClasses($this->getDefaultClasses());
    }
    
    function setDefaultClass($name) {
        $this->default_class = $name;
    }
    
    function setMoreDefaultClasses($name) {
        
        $names = (is_array($name)) ? $name : func_get_arg();
        foreach($names as $v) {
            $this->default_classes[] = $v;
        }
    }
    
    function getDefaultClasses() {
        
        $arr[] = $this->default_class;
        foreach($this->default_classes as $v) {
            $arr[] = $this->default_class . $v;
        }
        
        return $arr;
    }
*/
    
}
?>