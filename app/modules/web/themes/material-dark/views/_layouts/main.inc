<?php
/**
 * @var callable $_getvar
 * @var Template $this
 */

use SP\Mvc\View\Template;

?>
<!DOCTYPE html>
<html lang="<?php echo $_getvar('lang'); ?>">
<head>
    <title><?php printf('%s :: %s', $_getvar('app_name'), $_getvar('app_desc')); ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png"
          href="<?php echo $_getvar('logo_icon'); ?>">
    <?php foreach ($_getvar('cssLinks') as $cssLink): ?>
        <link rel="stylesheet" href="<?php echo $cssLink; ?>"/>
    <?php endforeach; ?>
</head>
<body class="<?php echo $_getvar('page'); ?>">
<div id="wrap">
    <noscript>
        <div id="nojs"><?php echo __('Javascript is needed in order to run correctly'); ?></div>
    </noscript>

    <div id="wrap-loading">
        <div id="loading"
             class="mdl-spinner mdl-spinner--single-color mdl-js-spinner"></div>
        <div id="taskStatus"></div>
    </div>

    <div id="container" class="<?php echo $_getvar('page'); ?>"
         data-page="<?php echo $_getvar('page'); ?>"
         data-upgraded="<?php echo (int)$this->isUpgraded(); ?>">
        <?php
        if ($_getvar('useFixedHeader')):
            include $this->includePartial('fixed-header');
        elseif ($this->hashContentTemplates()):
            foreach ($this->getContentTemplates() as $template):
                include $template;
            endforeach;
        endif;
        ?>
    </div> <!-- Close container -->

    <?php include $this->includePartial('footer'); ?>
</div> <!-- Close wrap -->

<?php foreach ($_getvar('jsLinks') as $jsLink): ?>
    <script type="text/javascript" src="<?php echo $jsLink; ?>"></script>
<?php endforeach; ?>

<script type="text/javascript">
    $(document).ready(function (e) {
        "use strict";

        sysPassApp.log.info("ready");

        sysPassApp.sk.set("<?php echo $_getvar('sk'); ?>");
    });
</script>

</body> <!-- Close BODY...almost done..go..go..go -->
</html> <!-- Close HTML...ufff too much work!! :)) -->
<!-- Insert coin . . . -->
