<div id="actionsBar" class="round">
    <ul>
        <?php use SP\Acl;
        use SP\Init;
        use SP\Util;

        foreach ($actions as $action): ?>
            <?php if ($action['checkaccess'] && !Acl::checkUserAccess($action['name'])): ?>
                <?php continue; ?>
            <?php endif; ?>

            <?php if ($action['name'] == Acl::ACTION_EVL && !Util::logIsEnabled()): ?>
                <?php continue; ?>
            <?php endif; ?>

            <li class="round" title="<?php echo _($action['title']); ?>" OnClick="doAction(<?php echo $action['name']; ?>)"><img src="<?php echo Init::$WEBROOT,'/imgs/',$action['img']; ?>"/></li>
        <?php endforeach; ?>
    </ul>
</div>
<div id="content"></div>
