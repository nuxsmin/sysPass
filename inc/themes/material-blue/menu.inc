<div id="actionsBar">
    <div id="actionsBar-logo"><img src="<?php use SP\Util\Checks;

        echo $logoNoText; ?>" alt="logo" /></div>
    <div id="actionsBar-icons">
        <?php foreach ($actions as $action): ?>
            <?php if ($action['checkaccess'] && !\SP\Core\Acl::checkUserAccess($action['name'])): ?>
                <?php continue; ?>
            <?php endif; ?>

            <?php if ($action['name'] == \SP\Core\ActionsInterface::ACTION_EVL && !Checks::logIsEnabled()): ?>
                <?php continue; ?>
            <?php endif; ?>

            <button class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-js-ripple-effect" title="<?php echo _($action['title']); ?>" OnClick="sysPassUtil.Common.doAction(<?php echo $action['name']; ?>)">
                <i class="material-icons"><?php echo $action['icon']; ?></i>
            </button>
        <?php endforeach; ?>
    </div>
</div>
<div id="content"></div>
