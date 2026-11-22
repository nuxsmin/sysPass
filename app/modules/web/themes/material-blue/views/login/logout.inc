<?php
/**
 * @var callable $_getvar
 */

use function SP\__;

?>
<div id="box-pub-noheader">
    <div class="sp-auth-brand">
        <img class="sp-auth-logo"
             src="<?php echo $_getvar('logo_stacked_primary'); ?>"
             alt="<?php echo htmlspecialchars($_getvar('app_name'), ENT_QUOTES); ?>"/>
        <h1 class="sp-auth-title" role="status"><?php echo __('Signed out'); ?></h1>
    </div>
    <div class="sp-panel sp-auth-signin">
        <a class="mdl-button sp-btn-primary" href="index.php?r=login"><?php echo __('Sign in'); ?></a>
    </div>
</div>