<?php /** @var $icons \SP\Core\UI\ThemeIcons */ ?>

<div id="title" class="titleNormal">
    <i class="material-icons">home</i>
    <?php echo __('Sitio'); ?>
</div>

<form method="post" name="frmPreferences" id="frmPreferences" class="form-action"
      data-onsubmit="user/saveSettings"
      data-action-route="<?php echo $route; ?>"
      data-reload="1"
      data-hash="">

    <table id="tblSite" class="data tblConfig round">
        <tr>
            <td class="descField"><?php echo __('Idioma'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Idioma'); ?></div>

                <select name="userlang" id="sel-userlang" size="1" class="select-box sel-chosen-ns">
                    <?php /** @var \SP\Mvc\View\Components\SelectItem[] $langs */
                    foreach ($langs as $langName => $lang): ?>
                        <option
                                value='<?php echo $lang->getId(); ?>' <?php echo $lang->isSelected() ? 'selected' : ""; ?>><?php echo $lang->getName(); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo __('Tema Visual'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Tema Visual'); ?></div>

                <select name="usertheme" id="sel-usertheme" size="1"
                        class="select-box sel-chosen-ns">
                    <?php /** @var \SP\Mvc\View\Components\SelectItem[] $themes */
                    foreach ($themes as $theme): ?>
                        <option
                                value='<?php echo $theme->getId(); ?>' <?php echo $theme->isSelected() ? 'selected' : ""; ?>><?php echo $theme->getName(); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Resultados por página'); ?>
                <div id="help-account_count"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-account_count">
                    <p>
                        <?php echo __('Número de resultados por página a mostrar, al realizar una búsqueda.'); ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="resultsperpage" name="resultsperpage" type="number" step="6"
                           pattern="[0-9]{1,5}" class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="5"
                           value="<?php echo $resultsPerPage; ?>" required/>
                    <label class="mdl-textfield__label"
                           for="resultsperpage"><?php echo __('Número de resultados por página'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="usersettings-general-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="account_link">
                                <input type="checkbox" id="account_link"
                                       class="mdl-switch__input"
                                       name="account_link" <?php echo $chkAccountLink ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Nombre de cuenta como enlace'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Habilita el nombre de la cuenta de la búsqueda, como enlace a los detalles de la cuenta.'); ?>
                            </span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="sort_views">
                                <input type="checkbox" id="sort_views"
                                       class="mdl-switch__input"
                                       name="sort_views" <?php echo $chkSortViews ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Ordenar resultados por visitas'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Ordena los resultados de búsqueda por el número de visitas de las cuentas.'); ?>
                            </span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="top_navbar">
                                <input type="checkbox" id="top_navbar"
                                       class="mdl-switch__input"
                                       name="top_navbar" <?php echo $chkTopNavbar ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Barra de navegación superior'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Mostrar una barra de navegación superior en las búsquedas.'); ?>
                            </span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="optional_actions">
                                <input type="checkbox" id="optional_actions"
                                       class="mdl-switch__input"
                                       name="optional_actions" <?php echo $chkOptionalActions ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Mostrar Acciones Ocultas'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Mostrar las acciones ocultas para los elementos de la búsqueda de cuentas.'); ?>
                            </span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="resultsascards">
                                <input type="checkbox" id="resultsascards"
                                       class="mdl-switch__input"
                                       name="resultsascards" <?php echo $chkResultsAsCards ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Resultados en Tarjetas'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Muestra los resultados de búsqueda de cuentas en formato tarjeta.'); ?>
                            </span>
                        </span>
                    </li>

                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="check_notifications">
                                <input type="checkbox" id="check_notifications"
                                       class="mdl-switch__input"
                                       name="check_notifications" <?php echo $chkNotifications ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Notificaciones In-App'); ?></span>
                            <span class="mdl-list__item-sub-title">
                                <?php echo __('Habilita la consulta de notificaciones activas In-App.'); ?>
                            </span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
    </table>

    <input type="hidden" name="isAjax" value="1"/>
    <input type="hidden" name="sk" value="">
</form>
<div class="tab-actions">
    <ul>
        <li>
            <button id="btnBack" type="button"
                    class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                    title="<?php echo __('Atrás'); ?>">
                <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
            </button>
        </li>
        <li>
            <button form="frmPreferences"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                    title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
            </button>
        </li>
    </ul>
</div>
