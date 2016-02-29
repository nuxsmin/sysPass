<table class="data round extra-info">
    <tr>
        <td class="descField"><?php echo _('Visitas'); ?></td>
        <td class="valField"><?php echo $accountData->account_countView . "(" . $accountData->account_countDecrypt . ")"; ?></td>
    </tr>
    <tr>
        <td class="descField"><?php echo _('Fecha Alta'); ?></td>
        <td class="valField"><?php echo $accountData->account_dateAdd ?></td>
    </tr>
    <tr>
        <td class="descField"><?php echo _('Creador'); ?></td>
        <td class="valField"><?php echo ($accountData->user_name) ? $accountData->user_name : $accountData->user_login; ?></td>
    </tr>
    <tr>
        <td class="descField"><?php echo _('Grupo Principal'); ?></td>
        <td class="valField"><?php echo $accountData->usergroup_name; ?></td>
    </tr>
    <?php if (is_array($accountOtherUsers) && count($accountOtherUsers) > 0): ?>
        <tr>
            <td class="descField"><?php echo _('Usuarios Secundarios'); ?></td>
            <td class="valField">
                <?php
                $users = [];

                foreach ($accountOtherUsers as $otherUser) {
                    /** @var $otherUser \SP\DataModel\UserBasicData */
                    if ($otherUser->getUserId() != $accountData->account_userId){
                        $users[] = $otherUser->getUserName();
                    }
                }

                $usersEdit = ($accountData->account_otherUserEdit) ? '(+)' : '';
                echo $usersEdit . ' ' . implode(" | ", $users);
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
                    if ($otherGroup->getUsergroupId() != $accountData->account_userGroupId) {
                        $groups[] = $otherGroup->getUsergroupName();
                    }
                }

                $groupsEdit = ($accountData->account_otherGroupEdit) ? '(+)' : '';

                echo $groupsEdit . ' ' . implode(" | ", $groups);
                ?>
            </td>
        </tr>
    <?php endif; ?>
    <?php if ($isModified): ?>
        <tr>
            <td class="descField"><?php echo _('Fecha Edición'); ?></td>
            <td class="valField"><?php echo $accountData->account_dateEdit; ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Editor'); ?></td>
            <td class="valField"><?php echo ($accountData->user_editName) ? $accountData->user_editName : $accountData->user_editLogin; ?></td>
        </tr>
    <?php endif; ?>
    <?php if ($showLink && $accountData->publicLink_hash): ?>
        <tr>
            <td class="descField"><?php echo _('Enlace Público'); ?></td>
            <td class="valField">
                <a href="<?php echo $publicLinkUrl; ?>" target="_blank"> <?php echo $publicLinkUrl; ?></a>
            </td>
        </tr>
    <?php endif; ?>
</table>