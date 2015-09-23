<!-- Start Tab - Wiki -->
<div id="tabs-<?php echo $wiki_tabIndex; ?>">
    <div id="title" class="midroundup titleNormal">
        <?php echo _('Wiki'); ?>
    </div>

    <form method="post" name="frmWiki" id="frmWiki" onsubmit="sysPassUtil.Common.configMgmt('config', this); return false;">
        <table id="tblWiki" class="data tblConfig round">
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar enlaces Wiki'); ?>
                    <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                    <div class="tooltip" for="help-wiki_enabled" style="display:none;">
                        <p>
                            <?php echo _('Habilita la opción de añadir un enlace a Wiki externa para los resultados de la búsqueda.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label for="wikienabled"><?php echo ($chkWiki) ? 'SI' : 'NO'; ?></label>
                    <input type="checkbox" name="wiki_enabled" id="wikienabled"
                           class="checkbox" <?php echo $chkWiki, ' ', $isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('URL de búsqueda Wiki'); ?>
                    <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                    <div class="tooltip" for="help-wiki_searchurl" style="display:none;">
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
                    <input type="text" name="wiki_searchurl" class="txtLong" value="<?php echo $wikiSearchUrl; ?>"
                           maxlength="128"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('URL de página en Wiki'); ?>
                    <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                    <div class="tooltip" for="help-wiki_pageurl" style="display:none;">
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
                    <input type="text" name="wiki_pageurl" class="txtLong" value="<?php echo $wikiPageUrl; ?>"
                           maxlength="128"/>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Prefijo para nombre de cuenta'); ?>
                    <img src="imgs/help.png" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                    <div class="tooltip" for="help-wikifilter" style="display:none;">
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
        <button type="submit" form="frmWiki" title="<?php echo _('Guardar'); ?>" class="button-action">
            <img src="<?php \SP\Init::$WEBURI; ?>imgs/check.png" alt="save" />
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