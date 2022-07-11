<?php
include_once(INCLUDE_DIR.'staff/login.header.php');
defined('OSTSCPINC') or die('Invalid path');
$info = ($_POST && $errors)?Format::htmlchars($_POST):array();
?>

<div id="loginBox">
    <h1 id="logo"><a href="index.php">
        <span class="valign-helper"></span>
        <img src="logo.php?login" alt="Fastdata2 :: <?php echo __('Agent Password Reset');?>" />
    </a></h1>
    <h3><?php echo Format::htmlchars($msg); ?></h3>
    <form action="pwreset.php" method="post">
        <?php csrf_token(); ?>
        <input type="hidden" name="do" value="sendmail">
        <fieldset>
            <input type="text" name="userid" id="name" value="<?php echo
            $info['userid']; ?>" placeholder="<?php echo __('Email or Username'); ?>" autocorrect="off"
                autocapitalize="off">
        </fieldset>
        <input class="submit" type="submit" name="submit" value="<?php echo __('Send Email'); ?>"/>
    </form>

</div>

<div id="copyRights">Copyright &copy; <a href='http://www.service-tech.org' target="_blank">service-tech.org</a></div>
</body>
</html>
