<?php /** @var \SP\Mvc\View\Template $this */ ?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <title><?php echo $appInfo['appname'], ' :: ', $appInfo['appdesc']; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo $logoIcon; ?>">
    <?php foreach ($cssLinks as $cssLink): ?>
        <link rel="stylesheet" href="<?php echo $cssLink; ?>"/>
    <?php endforeach; ?>
</head>
<body>
<div id="wrap">
    <noscript>
        <div id="nojs"><?php echo __('Javascript es necesario para el correcto funcionamiento'); ?></div>
    </noscript>

    <div id="wrap-loading">
        <div id="loading" class="mdl-spinner mdl-spinner--single-color mdl-js-spinner"></div>
        <div id="taskStatus"></div>
    </div>

    <div id="container" class="<?php echo $page; ?>" data-page="<?php echo $page; ?>" data-upgraded="<?php echo (int)$this->isUpgraded(); ?>">
        <?php
        if (isset($useFixedHeader)):
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

<?php foreach ($jsLinks as $jsLink): ?>
    <script type="text/javascript" src="<?php echo $jsLink; ?>"></script>
<?php endforeach; ?>

<script type="text/javascript">
    $(document).ready(function (e) {
        "use strict";

        sysPassApp.log.info("ready");

        sysPassApp.sk.set("<?php echo $sk; ?>");
    });
</script>

</body> <!-- Close BODY...almost done..go..go..go -->
</html> <!-- Close HTML...ufff too much work!! :)) -->
<!-- Insert coin . . . -->
