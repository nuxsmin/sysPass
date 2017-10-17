<body>
<div id="wrap">
    <noscript>
        <div id="nojs"><?php echo __('Javascript es necesario para el correcto funcionamiento'); ?></div>
    </noscript>
    <div id="wrap-loading">
        <div id="loading" class="mdl-spinner mdl-spinner--single-color mdl-js-spinner"></div>
        <div id="taskStatus"></div>
    </div>
    <div id="container" class="<?php echo $page; ?>" data-page="<?php echo $page; ?>">
        <?php if ($useLayout === true): ?>
            <div class="mdl-layout mdl-js-layout mdl-layout--fixed-header mdl-layout--no-desktop-drawer-button">
        <?php endif; ?>