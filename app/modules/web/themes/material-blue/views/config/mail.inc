<?php
/**
 * @var \SP\Core\UI\ThemeIcons $icons
 * @var \SP\Config\ConfigData  $configData
 */
?>

<div id="title" class="titleNormal">
    <i class="material-icons">mail</i>
    <?php echo __('Correo'); ?>
</div>

<form method="post" name="frmMail" id="frmMail" class="form-action" data-onsubmit="config/save"
      data-action-route="configMail/save"
      data-hash="">
    <table id="tblMail" class="data tblConfig">
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-mail-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="mail_enabled">
                                <input type="checkbox" id="mail_enabled"
                                       class="mdl-switch__input"
                                       name="mail_enabled" <?php echo $configData->isMailEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Habilitar notificaciones de correo'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Habilitar notificaciones de correo'); ?></span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="mail_requests_enabled">
                                <input type="checkbox" id="mail_requests_enabled"
                                       class="mdl-switch__input"
                                       name="mail_requests_enabled" <?php echo $configData->isMailRequestsEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Habilitar peticiones por correo'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Habilita que los usuarios puedan solicitar modificaciones o acceso a las cuentas sin permisos.'); ?>
                            </span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Servidor'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="mail_server" name="mail_server" type="text"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="128" value="<?php echo $configData->getMailServer(); ?>"/>
                    <label class="mdl-textfield__label" for="mail_server"><?php echo __('Servidor'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Puerto'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="mail_port" name="mail_port" type="number" pattern="[0-9]{1,5}"
                           class="mdl-textfield__input mdl-color-text--indigo-400" maxlength="5"
                           value="<?php echo $configData->getMailPort(); ?>"/>
                    <label class="mdl-textfield__label" for="mail_port"><?php echo __('Puerto'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-mail-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="mail_auth_enabled">
                                <input type="checkbox" id="mail_auth_enabled"
                                       class="mdl-switch__input"
                                       name="mail_auth_enabled" <?php echo $configData->isMailAuthenabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Habilitar Autentificación'); ?></span>
                            <span class="mdl-list__item-sub-title"></span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Usuario'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="mail_user" name="mail_user" type="email"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="50" value="<?php echo $configData->getMailUser(); ?>"/>
                    <label class="mdl-textfield__label" for="mail_user"><?php echo __('Usuario'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Clave'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="mail_pass" name="mail_pass" type="password"
                           class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                           maxlength="128" value="<?php echo $configData->getMailPass() ? '***' : ''; ?>"/>
                    <label class="mdl-textfield__label" for="mail_pass"><?php echo __('Clave'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Seguridad'); ?>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Seguridad'); ?></div>

                <select name="mail_security" id="sel-mailsecurity" size="1" class="select-box select-box-deselect">
                    <option value=""><?php echo __('Deshabilitada'); ?></option>
                    <?php foreach ($mailSecurity as $security): ?>
                        <option
                                value="<?php echo $security; ?>" <?php echo ($configData->getMailSecurity() === $security) ? 'selected' : ''; ?>><?php echo $security; ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Dirección de correo de envío'); ?>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="mail_from" name="mail_from" type="email"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="128" value="<?php echo $configData->getMailFrom(); ?>"/>
                    <label class="mdl-textfield__label"
                           for="mail_from"><?php echo __('Dirección de correo de envío'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Destinatarios'); ?>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Destinatarios'); ?></div>

                <input type="text" name="mail_recipients" id="mail_recipients" class="select-items-tag"
                       value="<?php echo implode(',', $configData->getMailRecipients()); ?>"/>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Eventos'); ?>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Eventos'); ?></div>

                <select id="mail_events" name="mail_events[]" multiple="multiple" class="select-box" data-create="true">
                    <?php /** @var \SP\Mvc\View\Components\SelectItem[] $mailEvents */
                    foreach ($mailEvents as $mailEvent): ?>
                        <option
                                value="<?php echo $mailEvent->getName(); ?>"
                            <?php echo $mailEvent->isSelected() ? 'selected' : '' ?>><?php echo $mailEvent->getName(); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>

    <input type="hidden" name="sk" value="">
</form>

<div class="tab-actions">
    <ul>
        <li>
            <button type="button"
                    class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconCheck()->getClassButton(); ?>"
                    data-onclick="config/mailCheck"
                    data-action-route="configMail/check"
                    data-src="#frmMail"
                    title="<?php echo $icons->getIconCheck()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconCheck()->getIcon(); ?></i>
            </button>
        </li>
        <li>
            <button id="btnBack" type="button"
                    class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                    title="<?php echo __('Atrás'); ?>">
                <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
            </button>
        </li>
        <li>
            <button form="frmMail"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                    title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
            </button>
        </li>
    </ul>
</div>
