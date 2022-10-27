<?php

include_once(INCLUDE_DIR.'staff/login.header.php');
$info = ($_POST && $errors)?Format::htmlchars($_POST):array();

?>
<div id="loginBox">
    <h1 id="logo"><a href="index.php">
        <span class="valign-helper"></span>
        <img src="logo.php?login" alt="Fastdata2 :: <?php echo __('Staff Control Panel');?>" />
    </a></h1>
    <h3><?php echo Format::htmlchars($msg); ?></h3>
    <div class="banner"><small><?php echo ($content) ? Format::display($content->getBody()) : ''; ?></small></div>
    <form action="login.php" method="post">
        <?php csrf_token(); ?>
        <input type="hidden" name="do" value="scplogin">
        <fieldset>
        <input type="text" name="userid" id="name" value="<?php echo $info['userid']; ?>" placeholder="<?php echo __('Email or Username'); ?>" autocorrect="off" autocapitalize="off">
        <input type="password" name="passwd" id="pass" placeholder="<?php echo __('Password'); ?>" autocorrect="off" autocapitalize="off">
            <?php if ($show_reset && $cfg->allowPasswordReset()) { ?>
            <h3 style="display:inline"><a href="pwreset.php"><?php echo __('Forgot my password'); ?></a></h3>
            <?php } ?>
            <input class="submit" type="submit" name="submit" value="<?php echo __('Log In'); ?>" style="background: #f5f6f6; 
background: -moz-linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); 
background: -webkit-linear-gradient(top, #f5f6f6 0%,#dbdce2 21%,#b8bac6 49%,#dddfe3 80%,#f5f6f6 100%); 
background: linear-gradient(to bottom, #f5f6f6 0%,#dbdce2 21%,#b8bac6 49%,#dddfe3 80%,#f5f6f6 100%); width:100%;">
        </fieldset>
    </form>
<?php
$ext_bks = array();
foreach (StaffAuthenticationBackend::allRegistered() as $bk)
    if ($bk instanceof ExternalAuthentication)
        $ext_bks[] = $bk;

if (count($ext_bks)) { ?>
<div class="or">
    <hr/>
</div><?php
    foreach ($ext_bks as $bk) { ?>
<div class="external-auth"><?php $bk->renderExternalLink(); ?></div><?php
    }
} ?>
</div>
<div id="copyRights"><?php echo __('Copyright'); ?> &copy;
<a href='http://www.service-tech.org' target="_blank">Service Tech s.r.l.</a></div>
<center>
<div id="logo_ticket" style="margin-top:50px;">
<img src="http://5.249.147.181:8080/include/staff/img/logo-ticket-texture.png" style=" width:700px; height:230;"></div>
</center>
</body>
</div>
</html>
