<?php /** @var $icons \Theme\Icons */ ?>

<!-- Start Tab - Preferences -->
<div id="tabs-<?php echo $preferences_tabIndex; ?>" class="mdl-tabs__panel">
    <div class="tab-data">
        <div id="title" class="midroundup titleNormal">
            <?php echo __('Sitio'); ?>
        </div>

        <form method="post" name="frmPreferences" id="frmPreferences"
              class="form-action"
              data-onsubmit="user/savePreferences"
              data-nextaction-id="<?php echo \SP\Core\ActionsInterface::ACTION_USR_PREFERENCES_GENERAL; ?>"
              data-activetab="<?php echo $preferences_tabIndex; ?>"
              data-hash="">

            <table id="tblSite" class="data tblConfig round">
                <tr>
                    <td class="descField"><?php echo __('Idioma'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Idioma'); ?></div>

                        <select name="userlang" id="sel-userlang" size="1" class="select-box sel-chosen-ns">
                            <?php foreach ($langsAvailable as $langName => $langValue): ?>
                                <option
                                    value='<?php echo $langValue; ?>' <?php echo ($currentLang == $langValue) ? "SELECTED" : ""; ?>><?php echo $langName; ?></option>
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
                            <?php foreach ($themesAvailable as $themeDir => $themeName): ?>
                                <option
                                    value='<?php echo $themeDir; ?>' <?php echo ($currentTheme == $themeDir) ? "SELECTED" : ""; ?>><?php echo $themeName; ?></option>
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
                    <td class="descField">
                        <div id="help-account_link"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-account_link">
                            <p>
                                <?php echo __('Habilita el nombre de la cuenta de la búsqueda, como enlace a los detalles de la cuenta.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="account_link">
                            <input type="checkbox" id="account_link"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="account_link" <?php echo $chkAccountLink; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Nombre de cuenta como enlace'); ?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <div id="help-sort_views"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-sort_views">
                            <p>
                                <?php echo __('Ordena los resultados de búsqueda por el número de visitas de las cuentas.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="sort_views">
                            <input type="checkbox" id="sort_views" class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="sort_views" <?php echo ($chkSortViews) ? 'checked' : ''; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Ordenar resultados por visitas'); ?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <div id="help-top_navbar"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-top_navbar">
                            <p>
                                <?php echo __('Mostrar una barra de navegación superior en las búsquedas.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="top_navbar">
                            <input type="checkbox" id="top_navbar" class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="top_navbar" <?php echo ($chkTopNavbar) ? 'checked' : ''; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Barra de navegación superior'); ?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <div id="help-optional_actions"
                             class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-optional_actions">
                            <p>
                                <?php echo __('Mostrar las acciones ocultas para los elementos de la búsqueda de cuentas.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="optional_actions">
                            <input type="checkbox" id="optional_actions"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="optional_actions" <?php echo ($chkOptionalActions) ? 'checked' : ''; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Mostrar Acciones Ocultas'); ?></span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="descField">
                        <div id="help-resultsascards" class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                        <div class="mdl-tooltip mdl-tooltip--large" for="help-resultsascards">
                            <p>
                                <?php echo __('Muestra los resultados de búsqueda de cuentas en formato tarjeta.'); ?>
                            </p>
                        </div>
                    </td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="resultsascards">
                            <input type="checkbox" id="resultsascards" class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="resultsascards"
                                <?php echo $chkResultsAsCards; ?>/>
                            <span class="mdl-switch__label"><?php echo __('Resultados en Tarjetas'); ?></span>
                        </label>

                    </td>
                </tr>
            </table>

            <input type="hidden" name="itemId" value="<?php echo $userId; ?>"/>
            <input type="hidden" name="actionId" value="<?php echo $preferences_actionId; ?>"/>
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
    </div>
</div> <!-- End Tab - Preferences -->