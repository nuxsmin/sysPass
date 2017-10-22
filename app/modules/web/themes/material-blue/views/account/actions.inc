<?php

/** @var $icons \Theme\Icons */
/** @var \SP\Account\AccountAcl $AccountAcl */
?>
<div class="item-actions">
    <ul>
        <?php if ($gotData && $accountIsHistory): ?>
            <li>
                <button id="btnBack" type="button"
                        class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                        data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW; ?>"
                        data-item-id="<?php echo $accountId; ?>"
                        data-sk="<?php echo $sk; ?>"
                        data-onclick="account/show"
                        title="<?php echo __('Ver Actual'); ?>">
                    <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                </button>
            </li>
        <?php else: ?>
            <li>
                <button id="btnBack" type="button"
                        class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                        title="<?php echo __('Atrás'); ?>">
                    <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                </button>
            </li>
        <?php endif; ?>

        <?php /** @var \SP\Html\DataGrid\DataGridActionInterface[] $actions */
        foreach ($actions as $action): ?>
            <li>
                <button id="<?php echo $action->getId(); ?>"
                        class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $action->getIcon()->getClassButton(); ?>"
                        data-sk="<?php echo $sk; ?>"
                    <?php foreach ($action->getData() as $key => $value): ?>
                        <?php echo 'data-', $key, '="', $value, '"'; ?>
                    <?php endforeach; ?>
                    <?php foreach ($action->getAttributes() as $key => $value): ?>
                        <?php echo $key, '="', $value, '""'; ?>
                    <?php endforeach; ?>
                        title="<?php echo $action->getTitle(); ?>">
                    <i class="material-icons"><?php echo $action->getIcon()->getIcon(); ?></i>
                </button>
            </li>
        <?php endforeach; ?>

    </ul>
</div>