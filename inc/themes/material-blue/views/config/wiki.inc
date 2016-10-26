<?php /** @var $icons \Theme\Icons */ ?>

<!-- Start Tab - Wiki -->
<div id="tabs-<?php echo $wiki_tabIndex; ?>">
    <form method="post" name="frmWiki" id="frmWiki" class="form-action" data-onsubmit="config/save" data-type="wiki"
          data-hash="">

        <div id="title" class="midroundup titleNormal">
            <?php echo _('Wiki'); ?>
        </div>

        <table id="tblWiki" class="data tblConfig round">
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar enlaces Wiki'); ?>
                    <div id="help-wiki_enabled"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-wiki_enabled">
                        <p>
                            <?php echo _('Habilita la opción de añadir un enlace a Wiki externa para los resultados de la búsqueda.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="wiki_enabled">
                        <input type="checkbox" id="wiki_enabled" class="mdl-switch__input mdl-color-text--indigo-400"
                               name="wiki_enabled"
                            <?php echo $chkWiki, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('URL de búsqueda Wiki'); ?>
                    <div id="help-wiki_searchurl"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
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
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="128" value="<?php echo $wikiSearchUrl; ?>">
                        <label class="mdl-textfield__label"
                               for="wiki_searchurl"><?php echo _('URL de búsqueda Wiki'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('URL de página en Wiki'); ?>
                    <div id="help-wiki_pageurl"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
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
                        <input id="wiki_pageurl" name="wiki_pageurl" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="128" value="<?php echo $wikiPageUrl; ?>">
                        <label class="mdl-textfield__label"
                               for="wiki_pageurl"><?php echo _('URL de página en Wiki'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Prefijo para nombre de cuenta'); ?>
                    <div id="help-wikifilter"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
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

        <div id="title" class="midroundup titleNormal">
            <?php echo _('DokuWiki API'); ?>
        </div>

        <table id="tblDokuWiki" class="data tblConfig round">
            <tr>
                <td class="descField">
                    <?php echo _('Habilitar API de DokuWiki'); ?>
                    <div id="help-dokuwiki_enabled"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-dokuwiki_enabled">
                        <p>
                            <?php echo _('Habilita la conexión a la API XML-RPC de DokuWiki para los enlaces Wiki.'); ?>
                        </p>
                        <p>
                            <?php echo _('Para que esta característica funcione, es necesario habilitar los enlaces Wiki para el filtrado de cuentas.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="dokuwiki_enabled">
                        <input type="checkbox" id="dokuwiki_enabled"
                               class="mdl-switch__input mdl-color-text--indigo-400"
                               name="dokuwiki_enabled"
                            <?php echo $chkDokuWiki, ' ', $isDisabled; ?>/>
                        <span class="mdl-switch__label"></span>
                    </label>
                </td>
            </tr>

            <tr>
                <td class="descField">
                    <?php echo _('URL API'); ?>
                    <div id="help-dokuwiki_url"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-dokuwiki_url">
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
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="dokuwiki_url" name="dokuwiki_url" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="128" value="<?php echo $dokuWikiUrl; ?>" <?php echo $isDisabled; ?>/>
                        <label class="mdl-textfield__label" for="dokuwiki_url"><?php echo _('URL API'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('URL Base'); ?>
                    <div id="help-dokuwiki_urlbase"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-dokuwiki_urlbase">
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
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="dokuwiki_urlbase" name="dokuwiki_urlbase" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="128" value="<?php echo $dokuWikiUrlBase; ?>" <?php echo $isDisabled; ?>/>
                        <label class="mdl-textfield__label" for="dokuwiki_urlbase"><?php echo _('URL Base'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Usuario'); ?>
                    <div id="help-dokuwiki_user"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-dokuwiki_user">
                        <p>
                            <?php echo _('Usuario para conectar a la API de DokuWiki.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="dokuwiki_user" name="dokuwiki_user" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="128" value="<?php echo $dokuWikiUser; ?>" <?php echo $isDisabled; ?>/>
                        <label class="mdl-textfield__label" for="dokuwiki_user"><?php echo _('Usuario'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Clave'); ?>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="dokuwiki_pass" name="dokuwiki_pass" type="password"
                               class="mdl-textfield__input passwordfield__input mdl-color-text--indigo-400"
                               maxlength="128" value="<?php echo $dokuWikiPass; ?>" <?php echo $isDisabled; ?>/>
                        <label class="mdl-textfield__label" for="dokuwiki_pass"><?php echo _('Clave'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Namespace'); ?>
                    <div id="help-dokuwiki_namespace"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-dokuwiki_namespace">
                        <p>
                            <?php echo _('Namespace utilizado para buscar las páginas.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="dokuwiki_namespace" name="dokuwiki_namespace" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="128" value="<?php echo $dokuWikiNamespace; ?>" <?php echo $isDisabled; ?>/>
                        <label class="mdl-textfield__label"
                               for="dokuwiki_namespace"><?php echo _('Namespace'); ?></label>
                    </div>
                </td>
            </tr>
        </table>

        <?php if ($isDemoMode): ?>
            <input type="hidden" name="wiki_enabled" value="1"/>
        <?php endif; ?>

        <input type="hidden" name="activeTab" value="<?php echo $wiki_tabIndex; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $wiki_actionId; ?>"/>
        <input type="hidden" name="sk" value="">
        <input type="hidden" name="isAjax" value="1"/>
    </form>

    <div class="action">
        <button type="button" class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconCheck()->getClassButton(); ?>"
                data-onclick="checks/wiki"
                data-src="#frmWiki"
                title="<?php echo $icons->getIconCheck()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconCheck()->getIcon(); ?></i>
        </button>
        <button form="frmWiki"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div> <!-- End Tab - Wiki -->