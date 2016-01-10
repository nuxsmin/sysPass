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
                    <img src="<?php echo $icons->getIconHelp()->getIcon(); ?>" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                    <div class="tooltip" for="help-wiki_enabled" style="display:none;">
                        <p>
                            <?php echo _('Habilita la opción de añadir un enlace a Wiki externa para los resultados de la búsqueda.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label for="wikienabled"><?php echo ($chkWiki) ? _('SI') : _('NO'); ?></label>
                    <input type="checkbox" name="wiki_enabled" id="wikienabled"
                           class="checkbox" <?php echo $chkWiki, ' ', $isDisabled; ?> />
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('URL de búsqueda Wiki'); ?>
                    <img src="<?php echo $icons->getIconHelp()->getIcon(); ?>" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
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
                    <img src="<?php echo $icons->getIconHelp()->getIcon(); ?>" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
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
                    <img src="<?php echo $icons->getIconHelp()->getIcon(); ?>" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
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

        <div id="title" class="midroundup titleNormal">
            <?php echo _('DokuWiki API'); ?>
        </div>

        <table id="tblDokuWiki" class="data tblConfig round">
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar API de DokuWiki'); ?>
                    <img src="<?php echo $icons->getIconHelp()->getIcon(); ?>" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                    <div class="tooltip" for="help-dokuwiki_enabled" style="display:none;">
                        <p>
                            <?php echo _('Habilita la conexión a la API XML-RPC de DokuWiki para los enlaces Wiki.'); ?>
                        </p>
                        <p>
                            <?php echo _('Para que esta característica funcione, es necesario habilitar los enlaces Wiki para el filtrado de cuentas.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label for="dokuwiki_enabled"><?php echo ($chkDokuWiki) ? _('SI') : _('NO'); ?></label>
                    <input type="checkbox" name="dokuwiki_enabled" id="dokuwiki_enabled"
                           class="checkbox" <?php echo $chkDokuWiki, ' ', $isDisabled; ?> />
                </td>
            </tr>

            <tr>
                <td class="descField">
                    <?php echo _('URL API'); ?>
                    <img src="<?php echo $icons->getIconHelp()->getIcon(); ?>" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                    <div class="tooltip" for="help-dokuwiki_url" style="display:none;">
                        <p>
                            <?php echo _('URL de la API de DokuWiki.'); ?>
                        </p>

                        <p>
                            <?php echo _('Ejemplo:'); ?>
                        <ul>
                            <li>http://wiki.syspass.org/lib/exe/xmlrpc.php</li>
                        </ul>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <input type="text" name="dokuwiki_url" class="txtLong" value="<?php echo $dokuWikiUrl; ?>"
                           maxlength="128" <?php echo $isDisabled; ?>/>
                </td>
            </tr>

            <tr>
                <td class="descField">
                    <?php echo _('URL Base'); ?>
                    <img src="<?php echo $icons->getIconHelp()->getIcon(); ?>" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                    <div class="tooltip" for="help-dokuwiki_urlbase" style="display:none;">
                        <p>
                            <?php echo _('URL base de DokuWiki.'); ?>
                        </p>

                        <p>
                            <?php echo _('Ejemplo:'); ?>
                        <ul>
                            <li>http://wiki.syspass.org/dokuwiki</li>
                        </ul>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <input type="text" name="dokuwiki_urlbase" class="txtLong" value="<?php echo $dokuWikiUrlBase; ?>"
                           maxlength="128" <?php echo $isDisabled; ?>/>
                </td>
            </tr>

            <tr>
                <td class="descField">
                    <?php echo _('Usuario'); ?>
                    <img src="<?php echo $icons->getIconHelp()->getIcon(); ?>" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                    <div class="tooltip" for="help-dokuwiki_user" style="display:none;">
                        <p>
                            <?php echo _('Usuario para conectar a la API de DokuWiki.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <input type="text" name="dokuwiki_user" value="<?php echo $dokuWikiUser; ?>"
                           maxlength="128" <?php echo $isDisabled; ?>/>
                </td>
            </tr>

            <tr>
                <td class="descField">
                    <?php echo _('Clave'); ?>
                </td>
                <td class="valField">
                    <input type="password" id="dokuwiki_pass" name="dokuwiki_pass" value="<?php echo $dokuWikiPass; ?>"
                           class="passwordfield__input-show"
                           maxlength="128" <?php echo $isDisabled; ?>/>
                </td>
            </tr>

            <tr>
                <td class="descField">
                    <?php echo _('Namespace'); ?>
                    <img src="<?php echo $icons->getIconHelp()->getIcon(); ?>" title="<?php echo _('Ayuda'); ?>" class="inputImgMini help-tooltip"/>
                    <div class="tooltip" for="help-dokuwiki_namespace" style="display:none;">
                        <p>
                            <?php echo _('Namespace utilizado para buscar las páginas.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <input type="text" name="dokuwiki_namespace" value="<?php echo $dokuWikiNamespace; ?>"
                           maxlength="128" <?php echo $isDisabled; ?>/>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Comprobar'); ?></td>
                <td class="valField">
                    <img src="<?php echo $icons->getIconRefresh()->getIcon(); ?>" class="inputImg"
                         title="<?php echo _('Comprobar conexión con DokuWiki'); ?>" onclick="sysPassUtil.Common.checkDokuWikiConn('frmWiki');"/>
                    <span id="dokuWikiResCheck"></span>
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
        <button type="submit" form="frmWiki" title="<?php echo $icons->getIconSave()->getTitle(); ?>" class="button-action">
            <img src="<?php echo $icons->getIconSave()->getIcon(); ?>" alt="save" />
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