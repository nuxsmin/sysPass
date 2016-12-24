<!DOCTYPE html>
<html lang="es">
    <head>
        <title><?php echo $appInfo['appname'],' :: ',$appInfo['appdesc']; ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <link rel="icon" TYPE="image/png" href="<?php echo $logoIcon; ?>">
        <link rel="stylesheet" href="<?php echo $cssLink;?>" />
        <?php if (isset($cssLinkTheme)): ?>
            <link rel="stylesheet" href="<?php echo $cssLinkTheme; ?>"/>
        <?php endif; ?>
    </head>
