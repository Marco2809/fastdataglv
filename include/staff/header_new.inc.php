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
    <link href="<?php echo ROOT_PATH; ?>include/staff/css/as_style.min.css" rel="stylesheet" type="text/css">
    <script type="text/javascript" src="./js/jquery.dropdown.js?a7d44f8"></script>
    <link href='https://fonts.googleapis.com/css?family=Play' rel='stylesheet' type='text/css'>
    <!--<script src="respond.min.js"></script>-->

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

<style type="text/css">
@charset "utf-8";
/* Simple fluid media
   Note: Fluid media requires that you remove the media's height and width attributes from the HTML
   http://www.alistapart.com/articles/fluid-images/
*/
img, object, embed, video {
	max-width: 100%;
}

/* IE 6 does not support max-width so default to width 100% */
.ie6 img {
	width:100%;
}

/*
	Dreamweaver Fluid Grid Properties
	----------------------------------
	dw-num-cols-mobile:		4;
	dw-num-cols-tablet:		8;
	dw-num-cols-desktop:	12;
	dw-gutter-percentage:	25;

	Inspiration from "Responsive Web Design" by Ethan Marcotte
	http://www.alistapart.com/articles/responsive-web-design

	and Golden Grid System by Joni Korpi
	http://goldengridsystem.com/
*/

.fluid {
	clear: both;
	margin-left: 0;
	width: 100%;
	float: left;
	display: block;
}

.fluidList {
    list-style:none;
    list-style-image:none;
    margin:0;
    padding:0;
}

/* Mobile Layout: 480px and below. */

.gridContainer {
	margin-left: auto;
	margin-right: auto;
	width: 86.45%;
	padding-left: 2.275%;
	padding-right: 2.275%;
	clear: none;
	float: none;
}

.zeroMargin_mobile {
margin-left: 0;
}
.hide_mobile {
display: none;
}

/* Tablet Layout: 481px to 768px. Inherits styles from: Mobile Layout. */

@media only screen and (min-width: 481px) {

.gridContainer {
	width: 90.675%;
	padding-left: 1.1625%;
	padding-right: 1.1625%;
	clear: none;
	float: none;
	margin-left: auto;
}

.hide_tablet {
display: none;
}
.zeroMargin_tablet {
margin-left: 0;
}
}

/* Desktop Layout: 769px to a max of 1232px.  Inherits styles from: Mobile Layout and Tablet Layout. */

@media only screen and (min-width: 769px) {

.gridContainer {
	width: 100%;
	max-width: 1800px;
	padding-left: 0%;
	padding-right: 0%;
	margin: auto;
	clear: none;
	float: none;
	margin-left: auto;
}


.zeroMargin_desktop {
margin-left: 0;
}
.hide_desktop {
display: none;
}

html,body {
  /* Extra Styles */
  background: #ccc;
  color: #669933;
  font-family: avenir, 'segoe ui', sans-serif;
}

@keyframes move {
	0% {
		background-position: 0 60%, 0 50%;
	}
	100% {
	  background-position: 0 60%, 100% 50%;
	}
}

.container {
  background-color:#1E1E1E;
	background-image:url(http://5.249.147.181:8080/include/staff/img_new/parete_piccola.png), radial-gradient(300px 500px at center, #669933 0%, rgba(0,0,0,0) 50%, rgba(0,0,0,0));

	background-size: 125%, 50% 100%;
  background-position: 0 60%, 0 100%;
  animation: move 8s linear infinite;

  /* Extra Styles */
  height: 95px;

  /*box-shadow: inset 0 0 25px rgba(0,0,0,.5);*/
  border-right: 0;
  border-left: 0;
}


/* Extra Styles */
h1 {
  text-align: center;
  font-weight: 600;
  font-size: 1em;
}

p {
  text-align: center;
  font-size: .75em;
}

a {
  color: #ffffff;
}


/* CSS DEL MENU */

@charset "utf-8";
/* Simple fluid media
   Note: Fluid media requires that you remove the media's height and width attributes from the HTML
   http://www.alistapart.com/articles/fluid-images/
*/
img, object, embed, video {
	max-width: 100%;
}

/* IE 6 does not support max-width so default to width 100% */
.ie6 img {
	width:100%;
}

/*
	Dreamweaver Fluid Grid Properties
	----------------------------------
	dw-num-cols-mobile:		4;
	dw-num-cols-tablet:		8;
	dw-num-cols-desktop:	12;
	dw-gutter-percentage:	25;

	Inspiration from "Responsive Web Design" by Ethan Marcotte
	http://www.alistapart.com/articles/responsive-web-design

	and Golden Grid System by Joni Korpi
	http://goldengridsystem.com/
*/

.fluid {
	clear: both;
	margin-left: 0;
	width: 100%;
	float: left;
	display: block;
}

.fluidList {
    list-style:none;
    list-style-image:none;
    margin:0;
    padding:0;
}

/* Mobile Layout: 480px and below. */

.gridContainer {
	margin-left: auto;
	margin-right: auto;
	width:100%;
	padding-left:0%;
	padding-right:0%;
	clear: none;
	float: none;
	background:#f1f1f1;
}

#logo {
	margin-right:auto;
	margin-left:auto;
	aligment-adjust:center;


}

#div_utente {
	float:right;
	width:230px;
	height:auto;
	margin-top:-45;
	font-family:Play;
	font-size:12px;
	color:#669933;
}


#div_icone {
	width:100%;
	float:right;
	height:75px;
	font-family:Play;
	font-size:11px;
	margin-left:auto;
	margin-right:auto;
	margin-top: -22px;
	padding-top:15px;
	aligment-adjust:center;
	text-align:center;
	color:#666666;
	overflow: auto;
	background-color:#333333;
	}




/* Tablet Layout: 481px to 768px. Inherits styles from: Mobile Layout. */

@media only screen and (min-width: 481px) {

.gridContainer {
	width:100%;
	padding-left:0%;
	padding-right:0%;
	clear: none;
	float: none;
	margin-left: auto;
	background:#f1f1f1;
}

#logo {
	margin-right:auto;
	margin-left:auto;
	aligment-adjust:center;


}

#div_utente {
	float:right;
	width:200px;
	height:auto;
	margin-top:-60px;
}
#div_icone {
	width:100%;
	float:right;
	height:75px;
	font-family:Play;
	font-size:11px;
	margin-left:auto;
	margin-right:auto;
	margin-top:0;
	padding-top:15px;
	aligment-adjust:center;
	text-align:center;
	color:#666666;
	overflow: auto;
	background-color:#333333;}


/* Desktop Layout: 769px to a max of 1232px.  Inherits styles from: Mobile Layout and Tablet Layout. */

@media only screen and (min-width: 769px) {

.gridContainer {
	width: 100%;
	max-width: 1800px;
	padding-left: 0%;
	padding-right: 0%;
	margin: auto;
	clear: none;
	float: none;
	margin-left: auto;
	background:#f1f1f1;
}

#logo {
	margin-right:auto;
	margin-left:auto;
	aligment-adjust:center;


}

#div_utente {
	float:right;
	width:auto;
	height:auto;
	margin-top:15px;
	font-family:Play;
	font-size:16px;
	color:#669933;
}


#div_icone {
	width:100%;
	height:75px;
	font-family:Play;
	font-size:11px;
	margin-left:auto;
	margin-right:auto;
	margin-top:-40px;
	padding-top:15px;
	aligment-adjust:center;
	text-align:center;
	color:#666666;

	background-color:#333333;}

.button {
    heigh:auto;
	background-color:#3333333;
    border: none;
    padding: 15px 32px;
    display: inline-block;
    margin: 2px 10px;
    cursor: pointer;
}
}
</style>
</head>
<!--<script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>-->
<!--<body style="background-color:#EBEBEB">-->
<body>
	  <div class="gridContainer clearfix">

<div class="container">
    <?php

    if($ost->getError())
        echo sprintf('<div id="error_bar">%s</div>', $ost->getError());
    elseif($ost->getWarning())
        echo sprintf('<div id="warning_bar">%s</div>', $ost->getWarning());
    elseif($ost->getNotice())
        echo sprintf('<div id="notice_bar">%s</div>', $ost->getNotice());
    ?>




<img src="../include/staff/img_new/fastdata.png" style="margin-left:40px; margin-top:2px; float:left;">
             <div id="logout_stampa" style="float: right; margin-top: 20px; text-align: center; margin-right:20px;">
        <span style="text-align: center;"></span><span style="color:#FFFFFF; font-size:16px;"><?php echo '<strong>Benvenuto,'.$thisstaff->getFirstName().'</strong>'; ?> <br>
          <a href="#" title="Stampa"><img src="../include/staff/img_new/stampa.png" style="width:26px; height:18px;"></a><a href="logout.php?auth=<?php echo $ost->getLinkToken(); ?>" class="no-pjax"><img src="../include/staff/img_new/lucchetto.png" style="width:46px; height:46px;"></a></div>


<!-- MENU GRANDE -->
<div id="div_icone" class="fluid" style="margin-top:5px; height:90px;">
<center>
<table width="100%" border="0" cellpadding="5px" cellspacing="5px">
  <tr>
    <th width="90px" scope="col"><a href="#"><img src="../include/staff/img_new/home_3d_off.png"/></a></th>
    <th width="90px" scope="col"><a href="#"><img src="../include/staff/img_new/soggettiterzi_3d_off.png"/></a></th>
      <?php
      $db = new PDO('mysql:host=localhost;dbname=dolibarr;charset=utf8', 'admin', 'Iniziale1!?');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);


try {

foreach($db->query("SELECT llx_entrepot.rowid as rowid FROM llx_entrepot LEFT JOIN llx_user ON (llx_entrepot.fk_user=llx_user.rowid) WHERE llx_user.login='".$thisstaff->getUserName()."'") as $row) {
	$id=$row['rowid'];
}
$db = null;
} catch(PDOException $ex) {
    echo "Errore!";
    echo($ex->getMessage());
}

      //echo $id;?>
     <?php if ($thisstaff->getId()!=80){?>
    <th width="90px" scope="col"><a href="http://5.249.147.181:8081/product/stock/fiche.php?id=<?php echo $id;?>"><img src="../include/staff/img_new/magazzino_3d.png"
      onmouseover="this.src='../include/staff/img_new/asset-mngm.png';"
      onmouseout="this.src='../include/staff/img_new/magazzino_3d.png';"/></a></th>
    <?php }else{?>
	<th width="90px" scope="col"><a href="http://5.249.147.181:8081/product/elenco_ordini.php?mainmenu=products&leftmenu="><img src="../include/staff/img_new/magazzino_3d.png"
      onmouseover="this.src='../include/staff/img_new/asset-mngm.png';"
      onmouseout="this.src='../include/staff/img_new/magazzino_3d.png';"/></a></th>
	<?php }?>
    <th width="90px" scope="col"><a href="#"><img src="../include/staff/img_new/commerciale_3d_off.png"/></a></th>
    <th width="90px" scope="col"><a href="#"><img src="../include/staff/img_new/fatturazione_3d_off.png"/></a></th>
    <th width="90px" scope="col"><a href="#"><img src="../include/staff/img_new/cassa_3d_off.png"/></a></th>
    <th width="90px" scope="col"><a href="#"><img src="../include/staff/img_new/commesse_3d_off.png"/></a></th>
    <th width="90px" scope="col"><a href="#"><img src="../include/staff/img_new/hr_3d_off.png"/></a></th>
    <th width="90px" scope="col"><a href="#"><img src="../include/staff/img_new/strumenti_3d_off.png" /></a></th>
    <th width="90px" scope="col"><a href="#"><img src="../include/staff/img_new/membri_3d_off.png"/></a></th>
    <th width="90px" scope="col"><a href="#"><img src="../include/staff/img_new/documenti_3d_off.png"/></a></th>
    <th width="90px" scope="col"><a href="#"><img src="../include/staff/img_new/puntovendita_3d_off.png"/></a></th>
    <th width="90px" scope="col"><a href="#"><img src="../include/staff/img_new/ordinegiorno_3d_off.png"/></a></th>
    <th width="90px" scope="col"><a href="#"><img src="../include/staff/img_new/ticket_3d.png"
      onmouseover="this.src='../include/staff/img_new/ticketp.png';"
      onmouseout="this.src='../include/staff/img_new/ticket_3d.png';"/></a></th>
  </tr>

</table>
</center>
</div>

    <!--nuovo search-->


    <div id="div_iconepiccole" class="fluid" style="margin-top:20px; margin-bottom:20px; height:50px;">
		<div id='sub_nav_new'>
    <form action="tickets.php" method="get">
    <?php csrf_token(); ?>
    <input type="hidden" name="a" value="search">
    <table>
        <tr>
            <td><input type="text" id="basic-ticket-search" name="query" size=15 value="<?php echo Format::htmlchars($_REQUEST['query'],true); ?>"
                autocomplete="off" autocorrect="off" autocapitalize="off"></td>
            <td><input class="ricerca" type="submit" name="basic_search" value="<?php echo __('Search'); ?>"></td>
            <?php if($thisstaff->getDeptId()==5 or $thisstaff->getDeptId()==6) {?>
            <td>&nbsp;&nbsp;<a href="#" id="go-advanced"><img src="../images/avanzata.png" style="margin-top:0px;"></a></td>
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


    <div style="min-width:100%;">
        <?php if($errors['err']) { ?>
            <div id="msg_error"><?php echo $errors['err']; ?></div>
        <?php }elseif($msg) { ?>
            <div id="msg_notice"><?php echo $msg; ?></div>
        <?php }elseif($warn) { ?>
            <div id="msg_warning"><?php echo $warn; ?></div>
        <?php } ?>
