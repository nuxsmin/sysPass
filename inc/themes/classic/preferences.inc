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
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip"/>

                    <div class="tooltip" for="help-account_link" style="display: none;">
                        <p>
                            <?php echo _('Habilita el nombre de la cuenta de la búsqueda, como enlace a los detalles de la cuenta.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label for="account_link"><?php echo ($chkAccountLink) ? 'SI' : 'NO'; ?></label>
                    <input type="checkbox" name="account_link" id="account_link"
                           class="checkbox" <?php echo $chkAccountLink; ?> />
                </td>
            </tr>

            <tr>
                <td class="descField">
                    <?php echo _('Resultados por página'); ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip"/>

                    <div class="tooltip" for="help-resultsperpage" style="display:none;">
                        <p>
                            <?php echo _('Número de resultados por página a mostrar, al realizar una búsqueda.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <input type="number" name="resultsperpage" id="sel-resultsperpage" step="6" pattern="[0-9]{1,5}"
                           value="<?php echo $resultsPerPage; ?>"/>
                </td>
            </tr>

            <tr>
                <td class="descField">
                    <?php echo _('Ordenar resultados por visitas'); ?>
                    <img src="imgs/help.png" title="" class="inputImgMini help-tooltip"/>
                    <div class="tooltip" for="help-sort_views" style="display:none;">
                        <p>
                            <?php echo _('Ordena los resultados de búsqueda por el número de visitas de las cuentas.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label for="sort_views"><?php echo ($chkSortViews) ? _('SI') : _('NO'); ?></label>
                    <input type="checkbox" name="sort_views" id="sort_views"
                           class="checkbox" <?php echo $chkSortViews; ?> />
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
        <button type="submit" form="frmPreferences" title="<?php echo _('Guardar'); ?>" class="button-action">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/check.png" alt="save"/>
        </button>
    </div>
</div> <!-- End Tab - Preferences -->