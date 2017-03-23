<?php
/** @var \SP\Account\AccountAcl $AccountAcl */
/** @var \SP\DataModel\AccountExtData $accountData */
/** @var $icons \SP\Core\UI\ThemeIconsBase */
?>

<fieldset class="data round">
    <legend>
        <?php echo __('Accesos'); ?>
        <i id="btn-filters" class="material-icons show-extra-info" data-state="0"
           data-target="#data-accesses"
           data-icon-down="<?php echo $icons->getIconDown()->getIcon(); ?>"
           data-icon-up="<?php echo $icons->getIconUp()->getIcon(); ?>"><?php echo $icons->getIconDown()->getIcon(); ?></i>
    </legend>

    <div id="data-accesses">
        <table>
            <?php if ($AccountAcl->isShowPermission()): ?>
                <tr>
                    <td class="descField"><?php echo __('Permisos'); ?></td>
                    <td class="valField">
                        <div class="account-permissions">
                            <fieldset class="round5">
                                <legend><?php echo __('Usuarios'); ?></legend>
                                <select id="otherUsers" name="otherUsers[]" multiple="multiple">
                                    <option value=""><?php echo __('Seleccionar Usuarios'); ?></option>
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
                                <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="userEditEnabled">
                                    <input name="userEditEnabled" type="checkbox" id="userEditEnabled"
                                           class="mdl-switch__input" <?php echo ($gotData && $accountData->getAccountOtherUserEdit()) ? 'checked' : ''; ?>/>
                                    <span class="mdl-switch__label"><?php echo __('Hablitar edición'); ?></span>
                                </label>
                            </fieldset>
                        </div>
                        <div class="account-permissions">
                            <fieldset class="round5">
                                <legend><?php echo __('Grupos'); ?></legend>
                                <select id="otherGroups" name="otherGroups[]" multiple="multiple">
                                    <option value=""><?php echo __('Seleccionar Grupos'); ?></option>
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
                                <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="groupEditEnabled">
                                    <input name="groupEditEnabled" type="checkbox" id="groupEditEnabled"
                                           class="mdl-switch__input" <?php echo ($gotData && $accountData->getAccountOtherGroupEdit()) ? 'checked' : ''; ?>/>
                                    <span class="mdl-switch__label"><?php echo __('Hablitar edición'); ?></span>
                                </label

                            </fieldset>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            <?php if ($userIsAdminApp || $userIsAdminAcc): ?>
                <tr>
                    <td class="descField"><?php echo __('Grupo Principal'); ?></td>
                    <td class="valField">
                        <select id="selMainGroupId" name="mainGroupId" class="select-box sel-chosen-usergroup">
                            <option value=""><?php echo __('Seleccionar Grupo'); ?></option>
                            <?php foreach ($otherGroups as $group): ?>
                                <option
                                        value="<?php echo $group->id; ?>" <?php echo ($gotData && $group->id == $accountData->getAccountUserGroupId()) ? 'selected' : ''; ?>><?php echo $group->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endif; ?>

            <?php if ($allowPrivate === 1): ?>
                <tr>
                    <td class="descField"><?php echo __('Cuenta Privada'); ?></td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="privateEnabled">
                            <input name="privateEnabled" type="checkbox" id="privateEnabled"
                                   class="mdl-switch__input" <?php echo ($gotData && $accountData->getAccountIsPrivate()) ? 'checked' : ''; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Privada'); ?></span>
                        </label>
                    </td>
                </tr>
            <?php endif; ?>

            <?php if ($allowPrivateGroup === 1): ?>
                <tr>
                    <td class="descField"><?php echo __('Cuenta Privada Grupo'); ?></td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="privateGroupEnabled">
                            <input name="privateGroupEnabled" type="checkbox" id="privateGroupEnabled"
                                   class="mdl-switch__input" <?php echo ($gotData && $accountData->getAccountIsPrivateGroup()) ? 'checked' : ''; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Privada Grupo'); ?></span>
                        </label>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</fieldset>

<?php if (!$isView): ?>
    <script>
        $(function () {
            var $accesses = $("#data-accesses");

            if ($accesses[0].childNodes.length === 1) {
                $accesses.parent(".data").hide();
            }

            $('#otherUsers').selectize({
                persist: false,
                valueField: 'user_id',
                labelField: 'user_login',
                searchField: ['user_login'],
                plugins: ['remove_button'],
                options: <?php echo $otherUsersJson; ?>,
                onInitialize: function () {
                    var userId = <?php echo $gotData ? $accountData->getAccountUserId() : 0; ?>;

                    if (userId > 0) {
                        this.removeOption(userId);
                    }
                }
            });

            $('#otherGroups').selectize({
                persist: false,
                valueField: 'id',
                labelField: 'name',
                searchField: ['name'],
                plugins: ['remove_button'],
                options: <?php echo $otherGroupsJson; ?>
            });
        });
    </script>
<?php endif; ?>

