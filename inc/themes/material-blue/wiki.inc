<!-- Start Tab - Wiki -->
<div id="tabs-<?php echo $wiki_tabIndex; ?>">
    <div id="title" class="midroundup titleNormal">
        <?php echo _('Wiki'); ?>
    </div>

    <form method="post" name="frmWiki" id="frmWiki" onsubmit="configMgmt('config', this); return false;">
        <table id="tblWiki" class="data tblConfig round">
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar enlaces Wiki'); ?>
                    <div id="help-wiki_enabled" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-wiki_enabled">
                        <p>
                            <?php echo _('Habilita la opción de añadir un enlace a Wiki externa para los resultados de la búsqueda.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="wiki_enabled">
                        <input type="checkbox" id="wiki_enabled" class="mdl-switch__input fg-blue100"
                               name="wiki_enabled"
                            <?php echo $chkWiki, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('URL de búsqueda Wiki'); ?>
                    <div id="help-wiki_searchurl" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-wiki_searchurl">
                        <p>
                            <?php echo _('URL que utiliza la wiki para realizar una búsqueda de una página.'); ?>
                        </p>

                        <p>
                            <?php echo _('Como parámetro se utiliza el nombre del cliente.'); ?>
                        </p>

                        <p>
                            <?php _('Ejemplo:'); ?>
                        </p>

                        <p>
                            https://wiki.example.org/search.php?phrase=
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="wiki_searchurl" name="wiki_searchurl" type="text"
                               class="mdl-textfield__input fg-blue100"
                               maxlength="128" value="<?php echo $wikiSearchUrl; ?>">
                        <label class="mdl-textfield__label"
                               for="wiki_searchurl"><?php echo _('URL de búsqueda Wiki'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('URL de página en Wiki'); ?>
                    <div id="help-wiki_pageurl" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-wiki_pageurl">
                        <p>
                            <?php echo _('URL que utiliza la wiki para acceder a los detalles de una página.'); ?>
                        </p>

                        <p>
                            <?php echo _('El nombre de la cuenta se utiliza como parámetro de la variable de búsqueda de la Wiki.'); ?>
                        </p>

                        <p>
                            <?php echo _('Ejemplo:'); ?>
                        </p>

                        <p>
                            https://wiki.example.org/show.php?name=
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="wiki_pageurl" name="wiki_pageurl" type="text" class="mdl-textfield__input fg-blue100"
                               maxlength="128" value="<?php echo $wikiPageUrl; ?>">
                        <label class="mdl-textfield__label"
                               for="wiki_pageurl"><?php echo _('URL de página en Wiki'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Prefijo para nombre de cuenta'); ?>
                    <div id="help-wikifilter" class="icon material-icons fg-blue80">help_outline</div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-wikifilter">
                        <p>
                            <?php echo _('Prefijo para determinar qué cuentas tienen un enlace a una página de la Wiki.'); ?>
                        </p>

                        <p>
                            <?php echo _('Ejemplos:') . ' serv- | srv- | vm-'; ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <input type="text" name="wiki_filter" id="wikifilter" value="<?php echo $wikiFilter; ?>"/>
                </td>
            </tr>
        </table>

        <?php if ($isDemoMode): ?>
            <input type="hidden" name="wiki_enabled" value="1"/>
        <?php endif; ?>

        <input type="hidden" name="activeTab" value="<?php echo $wiki_tabIndex; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $wiki_actionId; ?>"/>
        <input type="hidden" name="isAjax" value="1"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action">
        <button form="frmWiki"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored bg-green80"
                title="<?php echo _('Guardar'); ?>">
            <i class="material-icons">save</i>
        </button>
    </div>
</div> <!-- End Tab - Wiki -->

<script>
    $(function () {
        $('#wikifilter').tagsInput({
            'width': '350px',
            'height': '50px',
            'defaultText': '<?php echo _('Añadir filtro'); ?>',
            'defaultRemoveText': '<?php echo _('Eliminar filtro'); ?>',
            'removeWithBackspace': false,
            onAddTag: function () {
                // Fix scrolling to bottom
                var $tagsbox = $(this).next();
                $tagsbox.animate({scrollTop: $tagsbox.height()});

                if (!$tagsbox.find('div').hasClass('icon')) {
                    $tagsbox.find('div:last').prev().append('<div class="icon material-icons fg-orange80" title="' + LANG[5] + '">warning</div>');
                }
            },
            onRemoveTag: function () {
                var $tagsbox = $(this).next();

                if (!$tagsbox.find('div').hasClass('icon')) {
                    $tagsbox.find('div:last').prev().append('<div class="icon material-icons fg-orange80" title="' + LANG[5] + '">warning</div>');
                }
            },
            onChange: function () {
                var $tagsbox = $(this).next();
                var last_width = $tagsbox.find("span:last").width() + 10;
                $tagsbox.find(".tag:last").css('width', last_width);
            }
        });
    })
</script>