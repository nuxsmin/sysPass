<?php
/**
 * @var \SP\Core\UI\ThemeIcons                             $icons
 * @var \SP\Html\DataGrid\Action\DataGridActionInterface[] $accountActions
 * @var \SP\Html\DataGrid\Action\DataGridActionInterface[] $accountActionsMenu
 */
?>
<div class="item-actions">
    <ul>
        <?php foreach ($accountActions as $action): ?>
            <li>
                <button id="<?php echo $action->getId(); ?>"
                        class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $action->getClassesAsString(), ' ', $action->getIcon()->getClassButton(); ?>"
                    <?php foreach ($action->getData() as $key => $value): echo 'data-', $key, '="', $value, '"'; endforeach; ?>
                    <?php foreach ($action->getAttributes() as $key => $value): echo $key, '="', $value, '"'; endforeach; ?>
                        title="<?php echo $action->getTitle(); ?>">
                    <i class="material-icons"><?php echo $action->getIcon()->getIcon(); ?></i>
                </button>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if (isset($accountActionsMenu) && count($accountActionsMenu) > 0): ?>
        <div>
            <button id="actions-menu-account"
                    class="mdl-button mdl-js-button mdl-button--icon">
                <i class="material-icons">more_vert</i>
            </button>
            <ul class="mdl-menu mdl-menu--top-right mdl-js-menu mdl-js-ripple-effect"
                for="actions-menu-account">
                <?php foreach ($accountActionsMenu as $action): ?>
                    <li class="mdl-menu__item btn-action <?php echo $action->getClassesAsString(); ?>"
                        id="<?php echo $action->getId(); ?>"
                        <?php foreach ($action->getData() as $key => $value): echo 'data-', $key, '="', $value, '"'; endforeach; ?>
                        <?php foreach ($action->getAttributes() as $key => $value): echo $key, '="', $value, '"'; endforeach; ?>
                        title="<?php echo $action->getTitle(); ?>">
                        <i class="material-icons <?php echo $action->getIcon()->getClass(); ?>"><?php echo $action->getIcon()->getIcon(); ?></i>
                        <?php echo $action->getName(); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>