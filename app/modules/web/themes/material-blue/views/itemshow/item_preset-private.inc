<?php /** @var $private \SP\DataModel\AccountPrivate */ ?>

<table class="popup-data">
    <tbody>
    <tr>
        <td class="descField"></td>
        <td class="valField">
            <ul class="private-account-list-action mdl-list">
                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="private_user_enabled">
                            <input type="checkbox" id="private_user_enabled"
                                   class="mdl-switch__input"
                                   name="private_user_enabled" <?php echo $private->isPrivateUser() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Cuenta Privada'); ?></span>
                        <span class="mdl-list__item-sub-title">
                            <?php echo __('Las cuentas sólo serán visibles por el usuario.'); ?>
                            <br>
                            <?php echo __('Los administradores no podrán acceder a las cuentas.'); ?>
                        </span>
                    </span>
                </li>

                <li class="mdl-list__item mdl-list__item--two-line">
                    <div class="mdl-switch__box">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="private_group_enabled">
                            <input type="checkbox" id="private_group_enabled"
                                   class="mdl-switch__input"
                                   name="private_group_enabled" <?php echo $private->isPrivateGroup() ? 'checked' : ''; ?>/>
                        </label>
                    </div>

                    <span class="mdl-list__item-primary-content">
                        <span><?php echo __('Cuenta Privada Grupo'); ?></span>
                        <span class="mdl-list__item-sub-title">
                            <?php echo __('Las cuentas sólo serán visibles por el usuario y su grupo principal.'); ?>
                            <br>
                            <?php echo __('Los administradores no podrán acceder a las cuentas.'); ?>
                        </span>
                    </span>
                </li>
            </ul>
        </td>
    </tr>
    </tbody>
</table>