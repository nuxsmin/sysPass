<div id="resBuscar">
    <?php if (!$accounts): ?>
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
                               class="btn-action"
                               data-action-id="<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_VIEW; ?>"
                               data-next-id="<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>"
                               data-account-id="<?php echo $account['id']; ?>"><?php echo $account['name']; ?></a>
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

                            <?php if (isset($wikiFilter) && is_array($wikiFilter)): ?>
                                <?php foreach ($wikiFilter as $filter): ?>
                                    <?php if (preg_match('/^' . preg_quote($filter) . '.*/i', $account['name'])): ?>
                                        <a href="<?php echo $wikiPageUrl, $account['name']; ?>" target="_blank">
                                            <i id="viewpass" class="material-icons"
                                               title="<?php echo _('Enlace a Wiki'); ?>">library_books</i>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="account-actions round">

                            <?php if ($account['showView']): ?>
                                <i class="material-icons fg-blue80 btn-action"
                                   title="<?php echo _('Detalles de Cuenta'); ?>"
                                   data-action-id="<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_VIEW; ?>"
                                   data-next-id="<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>"
                                   data-account-id="<?php echo $account['id']; ?>">visibility</i>
                            <?php endif ?>

                            <?php if ($account['showViewPass']): ?>
                                <i id="viewpass" class="material-icons fg-blue80 btn-action-pass"
                                   title="<?php echo _('Ver Clave'); ?>"
                                   data-account-id="<?php echo $account['id']; ?>">lock_open</i>
                                <?php if (!\SP\Util::accountPassToImageIsEnabled()): ?>
                                    <i class="material-icons fg-blue80 clip-pass-button <?php echo $optionalActions ? '' : 'actions-optional'; ?>"
                                       data-account-id="<?php echo $account['id']; ?>"
                                       title="<?php echo _('Copiar Clave en Portapapeles'); ?>">content_paste</i>
                                <?php endif ?>
                            <?php endif ?>

                            <?php if (!$optionalActions && ($account['showEdit'] || $account['showCopy'] || $account['showDel'] || $account['showViewPass'])): ?>
                                <i class="material-icons btn-action-optional"
                                   title="<?php echo _('Más Acciones'); ?>">settings</i>
                            <?php endif ?>

                            <?php if ($account['showEdit']): ?>
                                <i class="material-icons fg-orange80 btn-action <?php echo $optionalActions ? '' : 'actions-optional'; ?>"
                                   title="<?php echo _('Modificar Cuenta'); ?>"
                                   data-action-id="<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_EDIT; ?>"
                                   data-next-id="<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>"
                                   data-account-id="<?php echo $account['id']; ?>">mode_edit</i>
                            <?php endif; ?>

                            <?php if ($account['showCopy']): ?>
                                <i class="material-icons fg-blue80 btn-action <?php echo $optionalActions ? '' : 'actions-optional'; ?>"
                                   title="<?php echo _('Copiar Cuenta'); ?>"
                                   data-action-id="<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_COPY; ?>"
                                   data-next-id="<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>"
                                   data-account-id="<?php echo $account['id']; ?>">content_copy</i>
                            <?php endif; ?>

                            <?php if ($account['showDel']): ?>
                                <i class="material-icons fg-red80 btn-action <?php echo ($optionalActions) ? '' : 'actions-optional'; ?>"
                                   title="<?php echo _('Eliminar Cuenta'); ?>"
                                   data-action-id="<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_DELETE; ?>"
                                   data-next-id="<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>"
                                   data-account-id="<?php echo $account['id']; ?>">delete</i>
                            <?php endif; ?>

                        </div>
                    <?php elseif ($requestEnabled || $isDemoMode): ?>
                        <div class="account-spacer"></div>
                        <div class="account-actions round">
                            <i class="material-icons fg-blue80 btn-action"
                               title="<?php echo _('Solicitar Modificación'); ?>"
                               data-action-id="<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_REQUEST; ?>"
                               data-next-id="<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>"
                               data-account-id="<?php echo $account['id']; ?>">email</i>
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
            // Actualizar el token de seguridad
            $("#frmSearch").find(":input[name='sk']").val('<?php echo $sk; ?>');

            var searchHeader = $('#data-search-header');

            searchHeader.find('.sort-down').click(function () {
                var key = $(this).parent().data('key');
                $(this).parent().find('a').addClass('filterOn');
                sysPassUtil.Common.searchSort(key, <?php echo $limitStart; ?>, 0);
            });

            searchHeader.find('.sort-up').click(function () {
                var key = $(this).parent().data('key');
                $(this).parent().find('a').addClass('filterOn');
                sysPassUtil.Common.searchSort(key, <?php echo $limitStart; ?>, 1);
            });

            $('.btn-action').on("click", function (e) {
                var btn = $(this);

                sysPassUtil.Common.doAction(btn.data('action-id'), btn.data('next-id'), btn.data('account-id'));
            });

            $('.btn-action-pass').on("click", function (e) {
                var btn = $(this);

                sysPassUtil.Common.viewPass(btn.data('account-id'), 1);
            });

            $('.btn-action-optional').on("click", function (e) {
                sysPassUtil.Common.showOptional($(this));
            });
        })
    </script>
</div>