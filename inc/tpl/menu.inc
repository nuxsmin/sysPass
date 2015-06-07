<div id="actionsBar" class="round">
    <ul>
        <?php foreach ($actions as $action): ?>
            <?php if ($action['checkaccess'] && !SP_Acl::checkUserAccess($action['name'])): ?>
                <?php continue; ?>
            <?php endif; ?>

            <?php if ($action['name'] == SP_Acl::ACTION_EVL && !SP_Util::logIsEnabled()): ?>
                <?php continue; ?>
            <?php endif; ?>

            <li class="round" title="<?php echo _($action['title']); ?>" OnClick="doAction(<?php echo $action['name']; ?>)"><img src="<?php echo SP_Init::$WEBROOT,'/imgs/',$action['img']; ?>"/></li>
        <?php endforeach; ?>
    </ul>
</div>
<div id="content"></div>
