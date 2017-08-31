<?php
require_once 'config.inc.php';
require_once 'config_more.inc.php';
require_once 'common.inc.php';

$page = (!empty($_GET['msg'])) ? 'login.php?msg=' . $_GET['msg'] : 'login.php';

$auth_setting = AuthProvider::getSettings();

if (AuthProvider::isSamlAuth() && $auth_setting['saml_slo_endpoint'] && !empty($_GET['full'])) { // saml
    
    $cc = AppController::getClientController();
    
    $relay_state = APP_ADMIN_PATH . $page;
    
    $more_slo = array('return' => APP_ADMIN_PATH . 'index.php?module=home&page=home');
    $slo_url = $cc->getLink('logout', false, false, false, $more_slo);
    
    header('Location: ' . $cc->_replaceArgSeparator($slo_url));
    exit;
}

session_name($conf['session_name']);
session_start();

AuthPriv::logout();

// no frames
header("Location: " . $page);
exit;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>
<head>
    <title></title>
    <SCRIPT LANGUAGE="JavaScript" TYPE="text/javascript">
        if(parent.frames['top_navigation']) {
            var redirectURL = top.document.location.href='<?php echo $page; ?>';
        } else {
            var redirectURL = document.location.href='<?php echo $page; ?>';
        }
    </SCRIPT>
    <META HTTP-EQUIV="REFRESH" CONTENT="0; URL=document.write(redirectURL)">    
</head>

<body>

</body>
</html>