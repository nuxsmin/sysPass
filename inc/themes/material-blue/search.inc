<div id="resBuscar">
    <?php use SP\Util\Checks;

    if (!$accounts): ?>
        <div class="noRes round"><?php echo _('No se encontraron registros'); ?></div>
    <?php else: ?>
        <?php if ($topNavbar): ?>
            <div id="pageNav" class="round shadow">
                <div id="pageNavLeft">
                    <?php echo $totalRows; ?> @ <?php echo round(microtime() - $queryTimeStart, 5); ?>s
                    <?php if ($filterOn): ?>
                        <span class="filterOn round"><?php echo _('Filtro ON'); ?></span>
                    <?php endif; ?>
                    &nbsp;
                    <?php if ($searchGlobal): ?>
                        <span class="globalOn round"><?php echo _('Global ON'); ?></span>
                    <?php endif; ?>
                </div>
                <div id="pageNavRight">
                    <?php if ($limitStart >= 1): ?>
                        <img src="imgs/arrow_first.png" onClick="<?php echo $pagerOnnClick['first']; ?>"
                             title="<?php echo _('Primera página'); ?>"/>
                        <img src="imgs/arrow_left.png" onClick="<?php echo $pagerOnnClick['prev']; ?>"
                             title="<?php echo _('Página anterior'); ?>"/>
                    <?php endif; ?>
                    &nbsp;<?php echo $firstPage, '/', $lastPage; ?>&nbsp;
                    <?php if ($limitStart < $totalRows && $firstPage != $lastPage): ?>
                        <img src="imgs/arrow_right.png" onClick="<?php echo $pagerOnnClick['next']; ?>"
                             title="<?php echo _('Página siguiente'); ?>"/>
                        <img src="imgs/arrow_last.png" onClick="<?php echo $pagerOnnClick['last']; ?>"
                             title="<?php echo _('Última página'); ?>"/>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div id="data-search-header" class="data-header data-header-minimal">
            <ul>
                <?php foreach ($sortFields as $field): ?>
                    <li data-key="<?php echo $field['key']; ?>">
                        <a class="round <?php echo ($searchKey == $field['key']) ? 'filterOn' : ''; ?>"
                           title="<?php echo $field['title']; ?>">
                            <?php echo $field['name']; ?>
                        </a>

                        <div
                            class="icon material-icons sort-down cursor-pointer <?php echo ($searchKey == $field['key'] && $searchOrder === 0) ? 'fg-orange80' : 'md-dark md-inactive'; ?>">
                            arrow_drop_down
                        </div>
                        <div
                            class="icon material-icons sort-up cursor-pointer <?php echo ($searchKey == $field['key'] && $searchOrder === 1) ? 'fg-orange80' : 'md-dark md-inactive'; ?>">
                            arrow_drop_up
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div id="data-search">
            <?php foreach ($accounts as $account): ?>
                <div class="account-label round shadow">
                    <div class="field-customer label-field">
                        <div class="field-name"><?php echo _('Cliente'); ?></div>
                        <div class="field-text round5 no-link"
                             style="background-color: <?php echo $account['color']; ?>;">
                            <?php if (!empty($account['customer_link'])): ?>
                                <a href="<?php echo $account['customer_link']; ?>" target="blank"
                                   title="<?php echo _('Buscar en Wiki'); ?>&nbsp;<?php echo $account['customer_name']; ?>">
                                    <?php echo $account['customer_name']; ?>
                                </a>
                            <?php else: ?>
                                <?php echo $account['customer_name']; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="field-account label-field">
                        <div class="field-name"><?php echo _('Nombre'); ?></div>
                        <?php if ($account['show'] && $accountLink): ?>
                            <a title="<?php echo _('Detalles de Cuenta'); ?>"
                               OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW; ?>,<?php echo \SP\Core\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $account['id']; ?>)"><?php echo $account['name']; ?></a>
                        <?php else: ?>
                            <div class="field-text"><?php echo $account['name']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="field-category label-field">
                        <div class="field-name"><?php echo _('Categoría'); ?></div>
                        <div class="field-text"><?php echo $account['category_name']; ?></div>
                    </div>

                    <?php if ($account['show']): ?>
                        <div class="field-user label-field">
                            <div class="field-name"><?php echo _('Usuario'); ?></div>
                            <?php if ($account['show']): ?>
                                <div class="field-text"><?php echo $account['login']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="field-url label-field">
                            <div class="field-name"><?php echo _('URL / IP'); ?></div>
                            <?php if ($account['show'] && $account['url_islink']): ?>
                                <a href="<?php echo $account['url']; ?>" target="_blank"
                                   title="<?php echo _('Abrir enlace a'), ': ', $account['url']; ?>"><?php echo $account['url_short']; ?></a>
                            <?php else: ?>
                                <div class="field-text"><?php echo $account['url_short']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="account-info">
                            <?php if ($account['show']): ?>
                                <i id="accesses-<?php echo $account['id']; ?>" class="material-icons">face</i>
                                <div class="mdl-tooltip" for="accesses-<?php echo $account['id']; ?>">
                                    <?php echo _('Permisos'), '<br>', $account['accesses']; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($account['show'] && !empty($account['notes'])): ?>
                                <i id="notes-<?php echo $account['id']; ?>" class="material-icons">speaker_notes</i>
                                <div class="mdl-tooltip" for="notes-<?php echo $account['id']; ?>">
                                    <?php echo _('Notas'), '<br>', $account['notes']; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($account['show'] && $account['numFiles'] > 0): ?>
                                <i id="attachments-<?php echo $account['id']; ?>" class="material-icons">attach_file</i>
                                <div class="mdl-tooltip" for="attachments-<?php echo $account['id']; ?>">
                                    <?php echo _('Archivos adjuntos'), ': ', $account['numFiles']; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($wikiFilter)): ?>
                                <?php if (preg_match('/^(' . preg_quote($wikiFilter) . ').*/i', $account['name'])): ?>
                                    <?php if ($dokuWikiEnabled): ?>
                                        <i class="material-icons fg-blue100" title="<?php echo _('Ver en Wiki'); ?>" onClick="sysPassUtil.Common.viewWiki('<?php echo $account['name']; ?>',<?php echo \SP\Core\ActionsInterface::ACTION_WIKI_VIEW; ?>)">library_books</i>
                                    <?php else: ?>
                                        <a href="<?php echo $wikiPageUrl, $account['name']; ?>" target="_blank">
                                            <i class="material-icons" title="<?php echo _('Enlace a Wiki'); ?>">library_books</i>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div class="account-actions round">

                            <?php if ($account['showView']): ?>
                                <i class="material-icons fg-blue80" title="<?php echo _('Detalles de Cuenta'); ?>"
                                   OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW; ?>,<?php echo \SP\Core\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $account['id']; ?>)">visibility</i>
                            <?php endif ?>

                            <?php if ($account['showViewPass']): ?>
                                <i id="viewpass" class="material-icons fg-blue80" title="<?php echo _('Ver Clave'); ?>"
                                   onClick="sysPassUtil.Common.viewPass(<?php echo $account['id']; ?>, 1)">lock_open</i>
                                <?php if (!Checks::accountPassToImageIsEnabled()): ?>
                                    <i class="material-icons fg-blue80 clip-pass-button <?php echo (!$optionalActions) ? 'actions-optional' : ''; ?>"
                                       data-account-id="<?php echo $account['id']; ?>"
                                       title="<?php echo _('Copiar Clave en Portapapeles'); ?>">content_paste</i>
                                <?php endif ?>
                            <?php endif ?>

                            <?php if (!$optionalActions
                                && ($account['showEdit'] || $account['showCopy'] || $account['showDel'] || $account['showViewPass'])
                            ): ?>
                                <i class="material-icons"
                                   title="<?php echo _('Más Acciones'); ?>"
                                   OnClick="sysPassUtil.Common.showOptional(this)">settings</i>
                            <?php endif ?>

                            <?php if ($account['showEdit']): ?>
                                <i class="material-icons fg-orange80 <?php echo (!$optionalActions) ? 'actions-optional' : ''; ?>"
                                   title="<?php echo _('Modificar Cuenta'); ?>"
                                   OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Core\ActionsInterface::ACTION_ACC_EDIT; ?>,<?php echo \SP\Core\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $account['id']; ?>)">mode_edit</i>
                            <?php endif; ?>

                            <?php if ($account['showCopy']): ?>
                                <i class="material-icons fg-blue80 <?php echo (!$optionalActions) ? 'actions-optional' : ''; ?>"
                                   title="<?php echo _('Copiar Cuenta'); ?>"
                                   OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Core\ActionsInterface::ACTION_ACC_COPY; ?>,<?php echo \SP\Core\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $account['id']; ?>)">content_copy</i>
                            <?php endif; ?>

                            <?php if ($account['showDel']): ?>
                                <i class="material-icons fg-red80 <?php echo (!$optionalActions) ? 'actions-optional' : ''; ?>"
                                   title="<?php echo _('Eliminar Cuenta'); ?>"
                                   OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Core\ActionsInterface::ACTION_ACC_DELETE; ?>,<?php echo \SP\Core\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $account['id']; ?>)">delete</i>
                            <?php endif; ?>

                        </div>
                    <?php elseif ($requestEnabled || $isDemoMode): ?>
                        <div class="account-spacer"></div>
                        <div class="account-actions round">
                            <i class="material-icons fg-blue80"
                               title="<?php echo _('Solicitar Modificación'); ?>"
                               OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Core\ActionsInterface::ACTION_ACC_REQUEST; ?>,<?php echo \SP\Core\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $account['id']; ?>)">email</i>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="pageNav" class="round shadow">
            <div id="pageNavLeft">
                <?php echo $totalRows; ?> @ <?php echo round(microtime() - $queryTimeStart, 5); ?>s
                <?php if ($filterOn): ?>
                    <span class="filterOn round"><?php echo _('Filtro ON'); ?></span>
                <?php endif; ?>
                &nbsp;
                <?php if ($searchGlobal): ?>
                    <span class="globalOn round"><?php echo _('Global ON'); ?></span>
                <?php endif; ?>
            </div>
            <div id="pageNavRight">
                <?php if ($limitStart >= 1): ?>
                    <img src="imgs/arrow_first.png" onClick="<?php echo $pagerOnnClick['first']; ?>"
                         title="<?php echo _('Primera página'); ?>"/>
                    <img src="imgs/arrow_left.png" onClick="<?php echo $pagerOnnClick['prev']; ?>"
                         title="<?php echo _('Página anterior'); ?>"/>
                <?php endif; ?>
                &nbsp;<?php echo $firstPage, '/', $lastPage; ?>&nbsp;
                <?php if ($limitStart < $totalRows && $firstPage != $lastPage): ?>
                    <img src="imgs/arrow_right.png" onClick="<?php echo $pagerOnnClick['next']; ?>"
                         title="<?php echo _('Página siguiente'); ?>"/>
                    <img src="imgs/arrow_last.png" onClick="<?php echo $pagerOnnClick['last']; ?>"
                         title="<?php echo _('Última página'); ?>"/>
                <?php endif; ?>
            </div>
        </div>

        <div id="clip-pass-text" style="visibility: hidden"></div>
    <?php endif; ?>
    <script>
        $(function () {
            $('#data-search-header').find('.sort-down').each(function () {
                $(this).click(function () {
                    var key = $(this).parent().data('key');
                    $(this).parent().find('a').addClass('filterOn');
                    sysPassUtil.Common.searchSort(key, <?php echo $limitStart; ?>, 0);
                });
            });

            $('#data-search-header').find('.sort-up').each(function () {
                $(this).click(function () {
                    var key = $(this).parent().data('key');
                    $(this).parent().find('a').addClass('filterOn');
                    sysPassUtil.Common.searchSort(key, <?php echo $limitStart; ?>, 1);
                });
            });
        })
    </script>
</div>