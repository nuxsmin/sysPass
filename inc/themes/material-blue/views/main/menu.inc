<div id="actionsBar">
    <div id="actionsBar-logo"><img src="<?php echo $logoNoText; ?>" alt="logo"/></div>
    <div id="actionsBar-icons">
        <?php foreach ($actions as $action): ?>
            <?php if (($action['id'] == \SP\Core\ActionsInterface::ACTION_EVL && !SP\Util\Checks::logIsEnabled())
                || ($action['checkaccess'] && !\SP\Core\Acl::checkUserAccess($action['id']))
            ) {
                continue;
            } ?>

            <button class="btn-menu mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-js-ripple-effect"
                    title="<?php echo _($action['title']); ?>"
                    data-action-id="<?php echo $action['id']; ?>"
                    data-history-reset="<?php echo $action['historyReset']; ?>">
                <i class="material-icons"><?php echo $action['icon']; ?></i>
            </button>
        <?php endforeach; ?>
    </div>
</div>
<div id="content"></div>
