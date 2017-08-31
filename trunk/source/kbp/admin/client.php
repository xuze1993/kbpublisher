<?php
require_once 'config.inc.php';
require_once 'config_more.inc.php';
require_once 'eleontev/Assorted.inc.php';

$page = APP_CLIENT_PATH . '?View=logout';
if(!empty($_GET['page'])) {
    $page = WebUtil::unserialize_url($_GET['page']);
}


// no frames
header("Location: " . $page);
exit;
?>
<!DOCTYPE html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <title></title>
    <SCRIPT LANGUAGE="JavaScript" TYPE="text/javascript">
        //if(parent.frames['top_navigation']) {
            var redirectURL = top.document.location.href='<?php echo $page; ?>';
        //} else {
        //    var redirectURL = document.location.href='<?php echo $page; ?>';
        //}
    </SCRIPT>
    <META HTTP-EQUIV="REFRESH" CONTENT="0; URL=document.write(redirectURL)">
</head>

<body>

</body>
</html>
