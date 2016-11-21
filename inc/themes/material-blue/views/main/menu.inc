<header class="mdl-layout__header">
    <div class="mdl-layout__header-row">
        <!-- Title -->
        <span class="mdl-layout-icon"><img class="logo" src="imgs/logo_bg_dark.png" alt="logo"/></span>
        <span class="mdl-layout-title"><?php echo $appInfo['appname']; ?></span>
        <!-- Add spacer, to align navigation to the right -->
        <div class="mdl-layout-spacer"></div>
        <!-- Navigation -->
        <nav class="mdl-navigation mdl-layout--large-screen-only">
            <?php /** @var \SP\Html\DataGrid\DataGridAction $action */
            foreach ($actions as $action): ?>
                <a class="btn-menu mdl-navigation__link"
                        title="<?php echo $action->getTitle(); ?>"
                        data-action-id="<?php echo $action->getId(); ?>"
                    <?php foreach ($action->getData() as $dataName => $dataValue):?>
                        <?php echo 'data-', $dataName, '="', $dataValue , '"'; ?>
                    <?php endforeach;?>>
                    <i class="material-icons"><?php echo $action->getIcon()->getIcon(); ?></i>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>
<div class="mdl-layout__drawer">
    <span class="mdl-layout-title"><?php echo $appInfo['appname']; ?></span>
    <nav class="mdl-navigation">
        <?php /** @var \SP\Html\DataGrid\DataGridAction $action */
        foreach ($actions as $action): ?>
            <a class="btn-menu mdl-navigation__link"
               title="<?php echo $action->getTitle(); ?>"
               data-action-id="<?php echo $action->getId(); ?>"
                <?php foreach ($action->getData() as $dataName => $dataValue):?>
                    <?php echo 'data-', $dataName, '="', $dataValue , '"'; ?>
                <?php endforeach;?>>
                <i class="material-icons"><?php echo $action->getIcon()->getIcon(); ?></i>
                <?php echo $action->getTitle(); ?>
            </a>
        <?php endforeach; ?>
    </nav>
</div>
<main class="mdl-layout__content">
    <div id="content"></div>

