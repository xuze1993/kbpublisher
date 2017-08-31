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

require_once 'Pear/HTTP/Request2.php';


class KBHelpView_ticket extends AppView
{
    
    var $tmpl = 'ticket_form.html';
    
    function execute(&$obj, &$manager) {

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
    
        $ret = $this->sendRequest();
        $tpl->tplAssign('response', $ret);
        $tpl->tplAssign('current_version', $this->conf['product_version']);
        $tpl->tplAssign('back_link', $this->controller->getLink('help', 'help'));
    
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function sendRequest() {
        
        $page = 'http://www.kbpublisher.com/rpc/request.php';
        
        $req = new HTTP_Request2($page);
        $req->setMethod(HTTP_Request2::METHOD_POST);
        $req->setConfig('follow_redirects', true);
        
        $req->addPostParameter('a', 'support');
        $req->addPostParameter('v', $this->conf['product_version']);
        $req->addPostParameter('http_host', $_SERVER['HTTP_HOST']);
        $req->addPostParameter('remote_addr', $_SERVER['REMOTE_ADDR']);
        $req->addPostParameter('dir', getcwd());
        
        
        //$req->setHeader('User-Agent', 'KBPublisher');
        //$req->setHeader('Accept', 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5');
        //$req->setHeader('Accept-Language', 'en-us;q=0.7,en;q=0.3');
        //$req->setHeader('Accept-Encoding', 'gzip,deflate');
        //$req->setHeader('Accept-Charset', 'iso-8859-1,utf-8;q=0.7,*;q=0.7');
        //$req->setHeader('Keep-Alive', '300');
        //$req->setHeader('Referer', APP_SITE_ADDRESS);
        //$req->setHeader('Connection', 'keep-alive');
        
        //echo "<pre>"; print_r($req); echo "</pre>";
        
        //if($this->proxy) {
        //    $req->setProxy($this->proxy['url'], $this->proxy['port']);
        //}
        
        //foreach($this->cookies as $k => $v) {
        //    $req->addCookie($k, $v);
        //}

        try {
            $response = $req->send();
            
            if ($response->getStatus() == 200) {
                return $response->getBody();
                
            } else {
                $msg = AppMsg::getMsgs();
                return 'Service Unavailable';
            }
            
        } catch (HTTP_Request2_Exception $e) {
            return 'Error: ' . $e->getMessage();
        }

        return false;
    }
}
?>
