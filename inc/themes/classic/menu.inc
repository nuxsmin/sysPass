<div id="actionsBar-wrap">
    <div id="actionsBar">
        <ul>
            <?php
            use SP\Util\Checks;

            foreach ($actions as $action): ?>
                <?php if ($action['checkaccess'] && !\SP\Core\Acl::checkUserAccess($action['name'])): ?>
                    <?php continue; ?>
                <?php endif; ?>

                <?php if ($action['name'] == \SP\Core\ActionsInterface::ACTION_EVL && !Checks::logIsEnabled()): ?>
                    <?php continue; ?>
                <?php endif; ?>

                <li class="round" title="<?php echo _($action['title']); ?>"
                    OnClick="sysPassUtil.Common.doAction(<?php echo $action['name']; ?>)"><img
                        src="<?php echo \SP\Core\Init::$WEBROOT, '/imgs/', $action['img']; ?>"/></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<div id="content"></div>
