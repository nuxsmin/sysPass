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
                foreach ($accountOtherUsersName as $userId => $userName) {
                    if ($userId != $accountData->account_userId) {
                        if (in_array($userId, $accountOtherUsers)) {
                            $accUsers[] = $userName;
                        }
                    }
                }

                $usersEdit = ($accountData->account_otherUserEdit) ? '(+)' : '';
                echo $usersEdit . ' ' . implode(" | ", $accUsers);
                ?>
            </td>
        </tr>
    <?php endif; ?>
    <?php if (is_array($accountOtherGroups) && count($accountOtherGroups) > 0): ?>
        <tr>
            <td class="descField"><?php echo _('Grupos Secundarios'); ?></td>
            <td class="valField">
                <?php
                foreach ($accountOtherGroupsName as $groupId => $groupName) {
                    if ($groupId != $accountData->account_userGroupId) {
                        if (in_array($groupId, $accountOtherGroups)) {
                            $accGroups[] = $groupName;
                        }
                    }
                }

                $groupsEdit = ($accountData->account_otherGroupEdit) ? '(+)' : '';

                echo $groupsEdit . ' ' . implode(" | ", $accGroups);
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
</table>