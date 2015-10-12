<!-- Start Tab - Preferences -->
<div id="tabs-<?php echo $preferences_tabIndex; ?>">
    <div id="title" class="midroundup titleNormal">
        <?php echo _('Sitio'); ?>
    </div>

    <form method="post" name="frmPreferences" id="frmPreferences"
          onsubmit="sysPassUtil.Common.configMgmt('preferences', this); return false;">

        <table id="tblSite" class="data tblConfig round">
            <tr>
                <td class="descField"><?php echo _('Idioma'); ?></td>
                <td class="valField">
                    <select name="userlang" id="sel-userlang" size="1" class="sel-chosen-ns">
                        <?php foreach ($langsAvailable as $langName => $langValue): ?>
                            <option
                                value='<?php echo $langValue; ?>' <?php echo ($currentLang == $langValue) ? "SELECTED" : ""; ?>><?php echo $langName; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo _('Tema Visual'); ?></td>
                <td class="valField">
                    <select name="usertheme" id="sel-usertheme" size="1"
                            class="sel-chosen-ns">
                        <?php foreach ($themesAvailable as $themeDir => $themeName): ?>
                            <option
                                value='<?php echo $themeDir; ?>' <?php echo ($currentTheme == $themeDir) ? "SELECTED" : ""; ?>><?php echo $themeName; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Nombre de cuenta como enlace'); ?>
                    <div id="help-account_link" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-account_link">
                        <p>
                            <?php echo _('Habilita el nombre de la cuenta de la búsqueda, como enlace a los detalles de la cuenta.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="account_link">
                        <input type="checkbox" id="account_link" class="mdl-switch__input fg-blue100"
                               name="account_link" <?php echo $chkAccountLink; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Resultados por página'); ?>
                    <div id="help-account_count" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-account_count">
                        <p>
                            <?php echo _('Número de resultados por página a mostrar, al realizar una búsqueda.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="resultsperpage" name="resultsperpage" type="number" step="6"
                               pattern="[0-9]{1,5}" class="mdl-textfield__input fg-blue100" maxlength="5"
                               value="<?php echo $resultsPerPage; ?>" required/>
                        <label class="mdl-textfield__label"
                               for="resultsperpage"><?php echo _('Número de resultados por página'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Ordenar resultados por visitas'); ?>
                    <div id="help-sort_views" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-sort_views">
                        <p>
                            <?php echo _('Ordena los resultados de búsqueda por el número de visitas de las cuentas.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="sort_views">
                        <input type="checkbox" id="sort_views" class="mdl-switch__input fg-blue100"
                               name="sort_views" <?php echo ($chkSortViews) ? 'checked' : ''; ?>/>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Barra de navegación superior'); ?>
                    <div id="help-top_navbar" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-top_navbar">
                        <p>
                            <?php echo _('Mostrar una barra de navegación superior en las búsquedas.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="top_navbar">
                        <input type="checkbox" id="top_navbar" class="mdl-switch__input fg-blue100"
                               name="top_navbar" <?php echo ($chkTopNavbar) ? 'checked' : ''; ?>/>
                    </label>
                </td>
            </tr>
        </table>

        <input type="hidden" name="itemId" value="<?php echo $userId; ?>"/>
        <input type="hidden" name="activeTab" value="<?php echo $preferences_tabIndex; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $preferences_actionId; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>
    <div class="action">
        <button form="frmPreferences"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored bg-green80"
                title="<?php echo _('Guardar'); ?>">
            <i class="material-icons">save</i>
        </button>
    </div>
</div> <!-- End Tab - Preferences -->