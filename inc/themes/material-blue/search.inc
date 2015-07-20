<div id="resBuscar">
    <?php use SP\Acl;

    if (!$accounts): ?>
        <div class="noRes round"><?php echo _('No se encontraron registros'); ?></div>
    <?php else: ?>
        <div id="data-search-header" class="data-header data-header-minimal">
            <ul>
                <?php foreach ($sortFields as $field): ?>
                    <li>
                        <a id="search-sort-<?php echo $field['key']; ?>" class="round"
                           onClick="<?php echo $field['function']; ?>"
                           title="<?php echo $field['title']; ?>"><?php echo $field['name']; ?></a>
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
                             style="background-color: <?php echo $account['color']; ?>">
                            <?php if (!empty($account['customer_link'])): ?>
                                <a href="<?php echo $account['customer_link']; ?>" target="blank"
                                   title="<?php echo _('Buscar en Wiki'); ?><br><br><?php echo $account['customer_name']; ?>">
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
                               OnClick="doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_VIEW; ?>,<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $account['id']; ?>)"><?php echo $account['name']; ?></a>
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
                                    <?php if (preg_match("/^" . preg_quote($filter) . ".*/i", $account['name'])): ?>
                                        <a href="<?php echo $wikiPageUrl, $account['name']; ?>" target="_blank">
                                            <img src="imgs/wiki.png" title="<?php echo _('Enlace a Wiki'); ?>"/>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="account-actions round">

                            <?php if ($account['showView']): ?>
                                <i class="material-icons" title="<?php echo _('Detalles de Cuenta'); ?>"
                                   OnClick="doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_VIEW; ?>,<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $account['id']; ?>)">visibility</i>
                            <?php endif ?>

                            <?php if ($account['showViewPass']): ?>
                                <i id="viewpass" class="material-icons" title="<?php echo _('Ver Clave'); ?>"
                                   onClick="viewPass(<?php echo $account['id']; ?>, 1)">lock_open</i>
                                <i id="clipboard" class="material-icons actions-optional clip-pass-button"
                                   data-clipboard-target="clip-pass-text"
                                   title="<?php echo _('Copiar Clave en Portapapeles'); ?>"
                                   onmousedown="viewPass(<?php echo $account['id']; ?>, false)">content_paste</i>
                            <?php endif ?>

                            <?php if ($account['showEdit'] || $account['showCopy'] || $account['showDel'] || $account['showViewPass']): ?>
                                <i class="material-icons"
                                   title="<?php echo _('Más Acciones'); ?>" OnClick="showOptional(this)">settings</i>
                            <?php endif ?>

                            <?php if ($account['showEdit']): ?>
                                <i class="material-icons actions-optional"
                                   title="<?php echo _('Modificar Cuenta'); ?>"
                                   OnClick="doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_EDIT; ?>,<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $account['id']; ?>)">mode_edit</i>
                            <?php endif; ?>

                            <?php if ($account['showCopy']): ?>
                                <i class="material-icons actions-optional"
                                   title="<?php echo _('Copiar Cuenta'); ?>"
                                   OnClick="doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_COPY; ?>,<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $account['id']; ?>)">content_copy</i>
                            <?php endif; ?>

                            <?php if ($account['showDel']): ?>
                                <i class="material-icons actions-optional"
                                   title="<?php echo _('Eliminar Cuenta'); ?>"
                                   OnClick="doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_DELETE; ?>,<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $account['id']; ?>)">delete</i>
                            <?php endif; ?>

                        </div>
                    <?php elseif ($requestEnabled || $isDemoMode): ?>
                        <div class="account-spacer"></div>
                        <div class="account-actions round">
                            <img src="imgs/request.png" title="<?php echo _('Solicitar Modificación'); ?>"
                                 class="inputImg"
                                 OnClick="doAction(<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_REQUEST; ?>,<?php echo \SP\Controller\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $account['id']; ?>)"/>
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

        <script>
            passToClip = 0;

            function clipboard() {
                var client = new ZeroClipboard($('.clip-pass-button'), {
                    swfPath: "js/ZeroClipboard.swf",
                    debug: false,
                    title: '<?php echo _('Copiar Clave en Portapapeles'); ?>'
                });

                client.on("aftercopy", function (e) {
                    resMsg("ok", "<?php echo _('Clave Copiada al Portapapeles'); ?>");
                });

                client.on("error", function (e) {
                    ZeroClipboard.destroy();
                });
            }

            clipboard();

            showSearchOrder();
        </script>
    <?php endif; ?>
    <script>
        // Actualizar el token de seguridad
        $("#frmSearch :input[name='sk']").val('<?php echo $sk; ?>');
    </script>
</div>