<?php
/** @var \SP\Account\AccountAcl $AccountAcl */
/** @var \SP\DataModel\AccountExtData $accountData */
?>
<table class="data round extra-info">
    <tr>
        <td class="descField"><?php echo _('Visitas'); ?></td>
        <td class="valField"><?php echo $accountData->getAccountCountView() . '(' . $accountData->getAccountCountDecrypt() . ')'; ?></td>
    </tr>
    <tr>
        <td class="descField"><?php echo _('Fecha Alta'); ?></td>
        <td class="valField"><?php echo $accountData->getAccountDateAdd() ?></td>
    </tr>
    <tr>
        <td class="descField"><?php echo _('Creador'); ?></td>
        <td class="valField"><?php echo $accountData->getUserName() ?: $accountData->getUserLogin(); ?></td>
    </tr>
    <tr>
        <td class="descField"><?php echo _('Grupo Principal'); ?></td>
        <td class="valField"><?php echo $accountData->getUsergroupName(); ?></td>
    </tr>
    <?php if (is_array($accountOtherUsers) && count($accountOtherUsers) > 0): ?>
        <tr>
            <td class="descField"><?php echo _('Usuarios Secundarios'); ?></td>
            <td class="valField">
                <?php
                $users = [];

                foreach ($accountOtherUsers as $otherUser) {
                    /** @var $otherUser \SP\DataModel\UserData */
                    if ($otherUser->getUserId() !== $accountData->getAccountUserId()) {
                        $users[] = $otherUser->getUserName();
                    }
                }

                $usersEdit = $accountData->getAccountOtherUserEdit() ? '(+)' : '';
                echo $usersEdit . ' ' . implode(' | ', $users);
                ?>
            </td>
        </tr>
    <?php endif; ?>
    <?php if (is_array($accountOtherGroups) && count($accountOtherGroups) > 0): ?>
        <tr>
            <td class="descField"><?php echo _('Grupos Secundarios'); ?></td>
            <td class="valField">
                <?php
                $groups = [];

                foreach ($accountOtherGroups as $otherGroup) {
                    /** @var $otherGroup \SP\DataModel\GroupData */
                    if ($otherGroup->getUsergroupId() !== $accountData->getAccountUserGroupId()) {
                        $groups[] = $otherGroup->getUsergroupName();
                    }
                }

                $groupsEdit = $accountData->getAccountOtherGroupEdit() ? '(+)' : '';

                echo $groupsEdit . ' ' . implode(' | ', $groups);
                ?>
            </td>
        </tr>
    <?php endif; ?>
    <?php if ($AccountAcl->isModified()): ?>
        <tr>
            <td class="descField"><?php echo _('Fecha Edición'); ?></td>
            <td class="valField"><?php echo $accountData->getAccountDateEdit(); ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Editor'); ?></td>
            <td class="valField"><?php echo $accountData->getUserEditName() ?: $accountData->getUserEditLogin(); ?></td>
        </tr>
    <?php endif; ?>
    <?php if ($AccountAcl->isShowLink() && $accountData->getPublicLinkHash()): ?>
        <tr>
            <td class="descField"><?php echo _('Enlace Público'); ?></td>
            <td class="valField">
                <a href="<?php echo $publicLinkUrl; ?>" target="_blank"> <?php echo $publicLinkUrl; ?></a>
            </td>
        </tr>
    <?php endif; ?>
    <tr>
        <td class="descField"><?php echo _('Fecha de Clave'); ?></td>
        <td class="valField"><?php echo $accountPassDate; ?></td>
    </tr>
    <tr>
        <td class="descField"><?php echo _('Fecha Caducidad Clave'); ?></td>
        <td class="valField"><?php echo $accountPassDateChange; ?></td>
    </tr>
</table>