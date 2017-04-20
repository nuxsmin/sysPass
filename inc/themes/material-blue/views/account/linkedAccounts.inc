<fieldset class="data round">
    <legend>
        <?php echo __('Cuentas Vinculadas'); ?>
        <i id="btn-filters" class="material-icons show-extra-info" data-state="0"
           data-target="#data-linkedAccounts"
           data-icon-down="<?php echo $icons->getIconDown()->getIcon(); ?>"
           data-icon-up="<?php echo $icons->getIconUp()->getIcon(); ?>"><?php echo $icons->getIconDown()->getIcon(); ?></i>
    </legend>

    <div id="data-linkedAccounts">
        <table>
            <tr>
                <td class="descField"><?php echo __('Cuentas'); ?></td>
                <td class="valField">
                    <div class="list-wrap">
                        <ul class="mdl-list">
                            <?php foreach ($linkedAccounts as $account): ?>
                                <li class="mdl-list__item">
                                <span class="btn-action mdl-list__item-primary-content"
                                      data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW; ?>"
                                      data-item-id="<?php echo $account->account_id; ?>"
                                      data-onclick="account/show"
                                      data-sk="<?php echo $sk; ?>">
                                <i class="material-icons mdl-list__item-icon">layers</i>
                                    <?php printf('%s (%s)', $account->account_name, $account->customer_name); ?>
                                </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</fieldset>