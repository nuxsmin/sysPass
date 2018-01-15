<?php
/** @var \SP\Account\AccountAcl $AccountAcl */
/** @var \SP\DataModel\AccountExtData $accountData */
?>

<fieldset class="data round">
    <legend>
        <?php echo __('Detalles'); ?>
        <i id="btn-filters" class="material-icons show-extra-info"
           data-state="0"
           data-target="#data-details"
           data-icon-down="<?php echo $icons->getIconDown()->getIcon(); ?>"
           data-icon-up="<?php echo $icons->getIconUp()->getIcon(); ?>"><?php echo $icons->getIconDown()->getIcon(); ?></i>
    </legend>

    <div id="data-details">
        <table>
            <tr>
                <td class="descField"><?php echo __('Visitas'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Visitas'); ?></div>

                    <?php echo $accountData->getCountView() . '(' . $accountData->getCountDecrypt() . ')'; ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Fecha Alta'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Fecha Alta'); ?></div>

                    <?php echo $accountData->getDateAdd() ?>
                </td>
            </tr>
            <?php if ($isModified && $accountData->getUserEditName()): ?>
                <tr>
                    <td class="descField"><?php echo __('Última Modificación'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Última Modificación'); ?></div>

                        <?php printf('%s (%s)', $accountData->getDateEdit(), $accountData->getUserEditName()); ?>
                    </td>
                </tr>
            <?php endif; ?>
            <tr>
                <td class="descField"><?php echo __('Creador'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Creador'); ?></div>

                    <?php echo $accountData->getUserName() ?: $accountData->getUserLogin(); ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Grupo Principal'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Grupo Principal'); ?></div>

                    <?php echo $accountData->getUserGroupName(); ?>
                </td>
            </tr>
            <?php if (is_array($accountOtherUsers) && count($accountOtherUsers) > 0): ?>
                <tr>
                    <td class="descField"><?php echo __('Usuarios Secundarios'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Usuarios Secundarios'); ?></div>

                        <?php
                        $users = [];

                        foreach ($accountOtherUsers as $otherUser) {
                            if ($otherUser->id !== $accountData->getUserId()) {
                                $users[] = $otherUser->name;
                            }
                        }

                        $usersEdit = $accountData->getOtherUserEdit() ? '(+)' : '';
                        echo $usersEdit . ' ' . implode(' | ', $users);
                        ?>
                    </td>
                </tr>
            <?php endif; ?>
            <?php if (is_array($accountOtherGroups) && count($accountOtherGroups) > 0): ?>
                <tr>
                    <td class="descField"><?php echo __('Grupos Secundarios'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Grupos Secundarios'); ?></div>

                        <?php
                        $groups = [];

                        foreach ($accountOtherGroups as $otherGroup) {
                            /** @var $otherGroup \SP\DataModel\UserGroupData */
                            if ($otherGroup->id !== $accountData->getUserGroupId()) {
                                $groups[] = $otherGroup->name;
                            }
                        }

                        $groupsEdit = $accountData->getOtherUserGroupEdit() ? '(+)' : '';

                        echo $groupsEdit . ' ' . implode(' | ', $groups);
                        ?>
                    </td>
                </tr>
            <?php endif; ?>
            <?php if ($AccountAcl->isModified()): ?>
                <tr>
                    <td class="descField"><?php echo __('Fecha Edición'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Fecha Edición'); ?></div>

                        <?php echo $accountData->getDateEdit(); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo __('Editor'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Editor'); ?></div>

                        <?php echo $accountData->getUserEditName() ?: $accountData->getUserEditLogin(); ?>
                    </td>
                </tr>
            <?php endif; ?>
            <tr>
                <td class="descField"><?php echo __('Fecha de Clave'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Fecha de Clave'); ?></div>

                    <?php echo $accountPassDate; ?>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Fecha Caducidad Clave'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Fecha Caducidad Clave'); ?></div>

                    <?php echo $accountPassDateChange; ?>
                </td>
            </tr>
        </table>
    </div>
</fieldset>