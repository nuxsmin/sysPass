<table class="data round extra-info">
    <tr>
        <td class="descField"><?php echo _('Permisos'); ?></td>
        <td class="valField">
            <div class="account-permissions">
                <fieldset class="round5">
                    <legend><?php echo _('Usuarios'); ?></legend>
                    <select id="otherusers" name="otherusers[]" multiple="multiple">
                        <option value=""><?php echo _('Seleccionar Usuarios'); ?></option>
                        <?php if ($gotData === true): ?>
                            <?php foreach ($accountOtherUsers as $otherUser): ?>
                                <?php /** @var $otherUser \SP\DataModel\UserData */ ?>
                                <option
                                    value="<?php echo $otherUser->getUserId(); ?>"
                                    selected><?php echo $otherUser->getUserLogin(); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <br>
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="ueditenabled">
                        <input name="ueditenabled" type="checkbox" id="ueditenabled"
                               class="mdl-switch__input" <?php echo $chkUserEdit; ?>/>
                        <span class="mdl-switch__label"><?php echo _('Hablitar edición'); ?></span>
                    </label>
                </fieldset>
            </div>
            <div class="account-permissions">
                <fieldset class="round5">
                    <legend><?php echo _('Grupos'); ?></legend>
                    <select id="othergroups" name="othergroups[]" multiple="multiple">
                        <option value=""><?php echo _('Seleccionar Grupos'); ?></option>
                        <?php if ($gotData === true): ?>
                            <?php foreach ($accountOtherGroups as $otherGroup): ?>
                                <?php /** @var $otherGroup \SP\DataModel\GroupData */ ?>
                                <option
                                    value="<?php echo $otherGroup->getUsergroupId(); ?>"
                                    selected><?php echo $otherGroup->getUsergroupName(); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <br>
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="geditenabled">
                        <input name="geditenabled" type="checkbox" id="geditenabled"
                               class="mdl-switch__input" <?php echo $chkGroupEdit; ?>/>
                        <span class="mdl-switch__label"><?php echo _('Hablitar edición'); ?></span>
                    </label

                </fieldset>
            </div>
        </td>
    </tr>
    <?php if ($userIsAdminApp || $userIsAdminAcc): ?>
        <tr>
            <td class="descField"><?php echo _('Grupo Principal'); ?></td>
            <td class="valField">
                <select id="selMainGroupId" name="mainGroupId" class="select-box sel-chosen-usergroup" required>
                    <option value=""><?php echo _('Seleccionar Grupo'); ?></option>
                    <?php foreach ($otherGroups as $group): ?>
                        <option
                            value="<?php echo $group->id; ?>" <?php echo ($gotData && $group->id == $accountData->getAccountUserGroupId()) ? 'selected' : ''; ?>><?php echo $group->name; ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    <?php endif; ?>
</table>

