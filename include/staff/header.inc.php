<?php
header("Content-Type: text/html; charset=UTF-8");
if (!isset($_SERVER['HTTP_X_PJAX'])) { ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html <?php
if (($lang = Internationalization::getCurrentLanguage())
        && ($info = Internationalization::getLanguageInfo($lang))
        && (@$info['direction'] == 'rtl'))
    echo 'dir="rtl" class="rtl"';
?>>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta http-equiv="cache-control" content="no-cache" />
    <meta http-equiv="pragma" content="no-cache" />
    <meta http-equiv="x-pjax-version" content="<?php echo GIT_VERSION; ?>">
    <title><?php echo ($ost && ($title=$ost->getPageTitle()))?$title:'Fastdata2 :: '.__('Staff Control Panel'); ?></title>
    <!--[if IE]>
    <style type="text/css">
        .tip_shadow { display:block !important; }
    </style>
    <![endif]-->
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-1.8.3.min.js?a7d44f8"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-ui-1.10.3.custom.min.js?a7d44f8"></script>
    <script type="text/javascript" src="./js/scp.js?a7d44f8"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery.pjax.js?a7d44f8"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/filedrop.field.js?a7d44f8"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery.multiselect.min.js?a7d44f8"></script>
    <script type="text/javascript" src="./js/tips.js?a7d44f8"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor.min.js?a7d44f8"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-osticket.js?a7d44f8"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-fonts.js?a7d44f8"></script>
    <script type="text/javascript" src="./js/bootstrap-typeahead.js?a7d44f8"></script>
    <link rel="stylesheet" href="<?php echo ROOT_PATH ?>css/thread.css?a7d44f8" media="all"/>
    <link rel="stylesheet" href="./css/scp.css?a7d44f8" media="all"/>
    <!--<link rel="stylesheet" href="../include/staff/css/boilerplate.css" media="all"/>
    <link rel="stylesheet" href="../include/staff/css/style-planner.css" media="all"/>-->
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/redactor.css?a7d44f8" media="screen"/>
    <link rel="stylesheet" href="./css/typeahead.css?a7d44f8" media="screen"/>
    <link type="text/css" href="<?php echo ROOT_PATH; ?>css/ui-lightness/jquery-ui-1.10.3.custom.min.css?a7d44f8"
         rel="stylesheet" media="screen" />
     <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/font-awesome.min.css?a7d44f8"/>
    <!--[if IE 7]>
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/font-awesome-ie7.min.css?a7d44f8"/>
    <![endif]-->
    <link type="text/css" rel="stylesheet" href="./css/dropdown.css?a7d44f8"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/loadingbar.css?a7d44f8"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/rtl.css?a7d44f8"/>
    <script type="text/javascript" src="./js/jquery.dropdown.js?a7d44f8"></script>
    <script src="http://use.edgefonts.net/play:n4:default.js" type="text/javascript"></script>

    <?php
    if($ost && ($headers=$ost->getExtraHeaders())) {
        echo "\n\t".implode("\n\t", $headers)."\n";
    }
    ?>
    <style type="text/css"> .pic
{
	filter: invert(0);
	-webkit-filter: invert(0);
	-moz-filter: invert(0);
	-o-filter: invert(0);
	-ms-filter: invert(0);
	
}

.pic:hover
{
	background-image:url(../include/staff/img/logout-verde.png);
}
</style>
<style>
	.ricerca
{
width:80px; height:25px;  text-align:center; font-family:Play; color:#333; -webkit-border-radius: 12px; -moz-border-radius: 8px;
border-radius: 8px; border: 0px solid #CCC;
	border-radius: 8px;
	background:#CCC;
			-moz-box-shadow:    inset 0 0 10px #333;
   -webkit-box-shadow: inset 0 0 10px #333;
   box-shadow:         inset 0 0 10px #333; padding-bottom:4px;
}

</style>
</head>
<body>
<div id="container">
    <?php
    if($ost->getError())
        echo sprintf('<div id="error_bar">%s</div>', $ost->getError());
    elseif($ost->getWarning())
        echo sprintf('<div id="warning_bar">%s</div>', $ost->getWarning());
    elseif($ost->getNotice())
        echo sprintf('<div id="notice_bar">%s</div>', $ost->getNotice());
    ?>
     <a href="index.php" class="no-pjax" id="logo">
            <span class="valign-helper"></span>
            <img src="logo.php" alt="osTicket &mdash; <?php echo __('Customer Support System'); ?>"/>
        </a>
    <div><?php echo sprintf(__('Welcome, %s.'), '<strong>'.$thisstaff->getFirstName().'</strong>'); ?> </div> 
    <div id="header"> 
        <!--
           <?php
            if($thisstaff->isAdmin() && !defined('ADMINPAGE')) { ?>
            | <a href="admin.php" class="no-pjax"><?php echo __('Admin Panel'); ?></a>
            <?php }else{ ?>
            | <a href="index.php" class="no-pjax"><?php echo __('Agent Panel'); ?></a>
            <?php } ?>
            | <a href="profile.php"><?php echo __('My Preferences'); ?></a>
            | <a href="logout.php?auth=<?php echo $ost->getLinkToken(); ?>" class="no-pjax"><?php echo __('Log Out'); ?></a>
        </p>-->
       
  
<div id="nav">
   <div id="menu">
     <div class="icons"><img src="../include/staff/img/home.png"><br><a href="#">Home</a></div>
   <div class="icons"><img src="../include/staff/img/hr.png"><br><a href="#" >HR</a></div>
    <div class="icons"><img src="../include/staff/img/doc.png"><br><a href="#">Documenti</a></div>
     <div class="icons"><img src="../include/staff/img/strumenti.png"><br><a href="#">Strumenti</a></div>
 <div class="icons"><img src="../include/staff/img/finanziario.png"><br><a href="#">Finanziario</a>  </div> 
 <div class="icons"><img src="../include/staff/img/profili.png"><br><a href="#">Profili</a></div>
 <div class="icons"><img src="../include/staff/img/ticket.png"><br><a href="#">Ticket</a></div>
 <div class="icons"><img src="../include/staff/img/magazzino.png"><br><a href="#">Magazzino</a></div>
 <div class="icons"><img src="../include/staff/img/laboratorio.png"><br><a href="#">Laboratorio</a></div> 
 <div class="icons"><img class="pic" src="../include/staff/img/logout.png"><br><a href="logout.php?auth=<?php echo $ost->getLinkToken(); ?>" class="no-pjax">Logout</a></div> 
	</div>
</div>

    </div>
    <!--nuovo search-->
    <div id='sub_nav'>
    <form action="tickets.php" method="get">
    <?php csrf_token(); ?>
    <input type="hidden" name="a" value="search">
    <table>
        <tr style="background-color:transparent; border-radius:0px; -webkit-box-shadow: 0 0px 0px 0px; -moz-box-shadow: 0 0px 0px 0px; box-shadow: 0 0px 0px 0px;">
            <td><input type="text" id="basic-ticket-search" name="query" size=15 value="<?php echo Format::htmlchars($_REQUEST['query'],true); ?>"
                autocomplete="off" autocorrect="off" autocapitalize="off"></td>          
            <td><input class="ricerca" type="submit" name="basic_search" value="<?php echo __('Search'); ?>"></td>
            <?php if(($thisstaff->getDeptId()!=17 AND $thisstaff->getDeptId()!=12  AND $thisstaff->getId()!=19) ) {?>
            <td>&nbsp;&nbsp;<a href="#" id="go-advanced"><img src="../images/avanzata.png" style="margin-top:8px;"><!--[<?php echo __('advanced'); ?>]--></a><!--&nbsp;<i class="help-tip icon-question-sign" href="#advanced"></i>--></td>
            <?php }?>
            <td>
			<ul>
            <?php include STAFFINC_DIR . "templates/sub-navigation.tmpl.php"; ?>
            </ul>
            </td>
        </tr>
    </table>
    </form>
    
</div>
    <div id="pjax-container" class="<?php if ($_POST) echo 'no-pjax'; ?>">
<?php } else {
    header('X-PJAX-Version: ' . GIT_VERSION);
    if ($pjax = $ost->getExtraPjax()) { ?>
    <script type="text/javascript">
    <?php foreach (array_filter($pjax) as $s) echo $s.";"; ?>
    </script>
    <?php }
    foreach ($ost->getExtraHeaders() as $h) {
        if (strpos($h, '<script ') !== false)
            echo $h;
    } ?>
    <title><?php echo ($ost && ($title=$ost->getPageTitle()))?$title:'Fastdata2 :: '.__('Staff Control Panel'); ?></title><?php
} # endif X_PJAX ?>


   <?php if ($thisstaff->getId()==1) { //backend per me?>
   <ul id="nav">
<?php include STAFFINC_DIR . "templates/navigation.tmpl.php"; ?>
    </ul>
    <ul id="sub_nav" style="background: #CCC; border: 0px solid #CCC;">
<?php include STAFFINC_DIR . "templates/sub-navigation.tmpl.php"; ?>
    </ul>
    <?php } ?>
 
  
    <div id="content">
        <?php if($errors['err']) { ?>
            <div id="msg_error"><?php echo $errors['err']; ?></div>
        <?php }elseif($msg) { ?>
            <div id="msg_notice"><?php echo $msg; ?></div>
        <?php }elseif($warn) { ?>
            <div id="msg_warning"><?php echo $warn; ?></div>
        <?php } ?>
