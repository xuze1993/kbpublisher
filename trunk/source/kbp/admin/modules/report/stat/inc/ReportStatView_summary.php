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


class ReportStatView_summary extends ReportStatView
{
    
    var $template = 'summary.html';

        
    function execute(&$obj, &$manager) {
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        
        // all article
        $tpl->tplAssign('article_all', $this->getArticleAll($manager));                         
        
        // all file 
        $tpl->tplAssign('file_all', $this->getFileAll($manager));    
        
        // all user
        $tpl->tplAssign('user_all', $this->getUserAll($manager));
            
        // all news
        $tpl->tplAssign('news_all', $this->getNewsAll($manager));        
        
        // all feedback
        $tpl->tplAssign('feedback_all', $this->getFeedbackAll($manager)); 

        
        $tpl->tplAssign($this->msg);
            
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getArticleAll($manager, $type = 'html', $send = false) {
        
        require_once 'ReportStatModel_article.php';
        require_once 'ReportStatView_article.php';
        
        $view = new ReportStatView_article;
        $manager = new  ReportStatModel_article;
        
        $view->msg = $this->msg;
        $data = $view->getAll($manager);

        return $data;
    }
    
    
    function getFileAll($manager, $type = 'html', $send = false) {
        
        require_once 'ReportStatModel_file.php';
        require_once 'ReportStatView_file.php';
        
        $view = new ReportStatView_file;
        $manager = new  ReportStatModel_file;
        
        $view->msg = $this->msg;
        $data = $view->getAll($manager);

        return $data;
    }
        
    
    function getUserAll($manager, $type = 'html', $send = false) {
        
        require_once 'ReportStatModel_user.php';
        require_once 'ReportStatView_user.php';
        
        $view = new ReportStatView_user;
        $manager = new  ReportStatModel_user;
        
        $view->msg = $this->msg;
        $data = $view->getAll($manager);

        return $data;
    }
    
    
    function getNewsAll($manager, $type = 'html', $send = false) {

        require_once 'ReportStatModel_news.php';
        require_once 'ReportStatView_news.php';
        
        $view = new ReportStatView_news;
        $manager = new  ReportStatModel_news;
        
        $view->msg = $this->msg;
        $data = $view->getAll($manager);

        return $data;
    }
    
    
    function getFeedbackAll($manager, $type = 'html', $send = false) {

        require_once 'ReportStatModel_feedback.php';
        require_once 'ReportStatView_feedback.php';
        
        $view = new ReportStatView_feedback;
        $manager = new  ReportStatModel_feedback;
        
        $view->msg = $this->msg;
        $data = $view->getAll($manager);

        return $data;
    }

}
?>