<?php /** @var \SP\Mvc\View\Template $this */ ?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <title><?php echo $appInfo['appname'], ' :: ', $appInfo['appdesc']; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo $logoIcon; ?>">
    <link rel="stylesheet" href="<?php echo $cssLink; ?>"/>
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

    <div id="container" class="<?php echo $page; ?>" data-page="<?php echo $page; ?>">
        <div class="mdl-layout mdl-js-layout mdl-layout--fixed-header mdl-layout--no-desktop-drawer-button">
            <header class="mdl-layout__header mdl-color--indigo-400">
                <div class="mdl-layout__header-row">
                    <!-- Title -->
                    <span class="mdl-layout-icon">
                <img class="logo btn-menu" src="<?php echo $logonobg; ?>"
                     alt="logo"/>
            </span>
                </div>
            </header>
            <main class="mdl-layout__content">
                <div id="content">
                    <?php echo $error; ?>
                </div>
            </main>
        </div>
    </div> <!-- Close container -->
</div> <!-- Close wrap -->
</body> <!-- Close BODY...almost done..go..go..go -->
</html> <!-- Close HTML...ufff too much work!! :)) -->
<!-- Insert coin . . . -->
