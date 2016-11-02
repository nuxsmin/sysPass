<?php
/**
 * @var $data    SP\Html\DataGrid\DataGridTab
 * @var $AccountSearchItem SP\Account\AccountsSearchItem
 * @var $this    SP\Core\Template
 */
?>
<div id="data-search">
    <?php foreach ($data->getData()->getData() as $index => $AccountSearchItem): ?>
        <?php if ($index === 'count'): continue; endif; ?>

        <?php $AccountSearchData = $AccountSearchItem->getAccountSearchData(); ?>

        <div class="account-label round shadow">
            <div class="label-left">
                <div class="label-row-up">
                    <div class="field-customer label-field">
                        <div class="field-name"><?php echo _('Cliente'); ?></div>
                        <?php if (!empty($AccountSearchItem->getCustomerLink())): ?>
                            <a href="<?php echo $AccountSearchItem->getCustomerLink(); ?>" target="blank"
                               title="<?php echo _('Buscar en Wiki'); ?>&nbsp;<?php echo $AccountSearchData->getCustomerName(); ?>">
                    <span class="mdl-chip mdl-chip--contact">
                        <span style="background-color: <?php echo $AccountSearchItem->getColor(); ?>;"
                              class="mdl-chip__contact mdl-color-text--white">
                            <?php echo $AccountSearchData->getCustomerName()[0]; ?></span>
                        <span class="mdl-chip__text"><?php echo $AccountSearchData->getCustomerName(); ?></span>
                    </span>
                            </a>
                        <?php else: ?>
                            <span class="mdl-chip mdl-chip--contact">
                        <span style="background-color: <?php echo $AccountSearchItem->getColor(); ?>;"
                              class="mdl-chip__contact mdl-color-text--white">
                            <?php echo $AccountSearchData->getCustomerName()[0]; ?></span>
                        <span class="mdl-chip__text"><?php echo $AccountSearchData->getCustomerName(); ?></span>
                    </span>
                        <?php endif; ?>
                    </div>

                    <div class="field-account label-field">
                        <div class="field-name"><?php echo _('Nombre'); ?></div>
                        <?php if ($AccountSearchItem->isShow() && $AccountSearchItem->getLink()): ?>
                            <a title="<?php echo _('Detalles de Cuenta'); ?>" class="btn-action"
                               data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW; ?>"
                               data-item-id="<?php echo $AccountSearchData->getAccountId(); ?>"
                               data-onclick="account/view"><?php echo $AccountSearchData->getAccountName(); ?></a>
                        <?php else: ?>
                            <div class="field-text">
                                <?php echo $AccountSearchData->getAccountName(); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="field-category label-field">
                        <div class="field-name"><?php echo _('Categoría'); ?></div>
                        <div class="field-text"><?php echo $AccountSearchData->getCategoryName(); ?></div>
                    </div>

                    <?php if ($AccountSearchItem->isShow()): ?>
                    <div class="field-user label-field">
                        <div class="field-name"><?php echo _('Usuario'); ?></div>
                        <div class="field-text"><?php echo $AccountSearchItem->getShortLogin(); ?></div>
                    </div>

                    <div class="field-url label-field">
                        <div class="field-name"><?php echo _('URL / IP'); ?></div>
                        <?php if ($AccountSearchItem->isUrlIslink()): ?>
                            <a href="<?php echo $AccountSearchData->getAccountUrl(); ?>" target="_blank"
                               title="<?php echo _('Abrir enlace a'), ': ', $AccountSearchData->getAccountUrl(); ?>"><?php echo $AccountSearchItem->getShortUrl(); ?></a>
                        <?php else: ?>
                            <div class="field-text"><?php echo $AccountSearchItem->getShortUrl(); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="label-row-down">
                    <div class="tags-box">
                        <?php foreach ($AccountSearchData->getTags() as $tag): ?>
                            <span class="tag"><?php echo $tag; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="label-right">
                <div class="account-info">
                    <?php if ($AccountSearchItem->isShow()): ?>
                        <i id="accesses-<?php echo $AccountSearchData->getAccountId(); ?>" class="material-icons">face</i>

                        <div class="mdl-tooltip" for="accesses-<?php echo $AccountSearchData->getAccountId(); ?>">
                            <?php echo _('Permisos'), '<br>', $AccountSearchItem->getAccesses(); ?>
                        </div>

                        <?php if ($AccountSearchItem->isFavorite()): ?>
                            <i class="material-icons icon-favorite mdl-color-text--amber-A100"
                               title="<?php echo _('Eliminar Favorito'); ?>"
                               data-action-id-on="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_FAVORITES_ADD; ?>"
                               data-action-id-off="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_FAVORITES_DELETE; ?>"
                               data-item-id="<?php echo $AccountSearchData->getAccountId(); ?>"
                               data-status="<?php echo 'on'; ?>">star</i>
                        <?php else: ?>
                            <i class="material-icons icon-favorite"
                               title="<?php echo _('Marcar Favorito'); ?>"
                               data-action-id-on="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_FAVORITES_ADD; ?>"
                               data-action-id-off="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_FAVORITES_DELETE; ?>"
                               data-item-id="<?php echo $AccountSearchData->getAccountId(); ?>"
                               data-status="<?php echo 'off'; ?>">star_border</i>
                        <?php endif; ?>

                        <?php if ($AccountSearchData->getAccountNotes() !== ''): ?>
                            <i id="notes-<?php echo $AccountSearchData->getAccountId(); ?>" class="material-icons">speaker_notes</i>
                            <div class="mdl-tooltip" for="notes-<?php echo $AccountSearchData->getAccountId(); ?>">
                                <?php echo _('Notas'), '<br>', $AccountSearchItem->getShortNotes(); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($AccountSearchItem->getNumFiles() > 0): ?>
                            <i id="attachments-<?php echo $AccountSearchData->getAccountId(); ?>"
                               class="material-icons">attach_file</i>
                            <div class="mdl-tooltip" for="attachments-<?php echo $AccountSearchData->getAccountId(); ?>">
                                <?php echo _('Archivos adjuntos'), ': ', $AccountSearchItem->getNumFiles(); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($wikiFilter)): ?>
                            <?php if (preg_match('/^(' . $wikiFilter . ').*/i', $AccountSearchData->getAccountName())): ?>
                                <?php if (\SP\Account\AccountsSearchItem::$dokuWikiEnabled): ?>
                                    <a href="<?php echo $wikiPageUrl, $AccountSearchData->getAccountName(); ?>" target="_blank">
                                        <i class="material-icons"
                                           title="<?php echo _('Enlace a Wiki'); ?>">library_books</i>
                                    </a>
                                    <i class="btn-action material-icons fg-green100"
                                       title="<?php echo _('Ver en Wiki'); ?>"
                                       data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_WIKI_VIEW; ?>"
                                       data-item-id="<?php echo $AccountSearchData->getAccountName(); ?>"
                                       data-onclick="wiki/show">library_books</i>
                                <?php else: ?>
                                    <a href="<?php echo $wikiPageUrl, $AccountSearchData->getAccountName(); ?>" target="_blank">
                                        <i class="material-icons"
                                           title="<?php echo _('Enlace a Wiki'); ?>">library_books</i>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="account-actions">
                    <?php foreach ($data->getDataActions() as $action): ?>
                        <?php if ($action->getReflectionFilter()->invoke($AccountSearchItem)): ?>
                            <i class="btn-action material-icons <?php echo $action->getIcon()->getClass(); ?>"
                               title="<?php echo $action->getTitle(); ?>"
                               data-item-id="<?php echo $AccountSearchData->getAccountId(); ?>"
                                <?php foreach ($action->getData() as $name => $actionData): ?>
                                    <?php echo 'data-', $name, '=', '"', $actionData, '"'; ?>
                                <?php endforeach; ?>><?php echo $action->getIcon()->getIcon(); ?></i>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if (count($data->getDataActionsMenu()) > 0): ?>
                        <button id="actions-menu-lower-right-<?php echo $AccountSearchData->getAccountId(); ?>"
                                class="mdl-button mdl-js-button mdl-button--icon">
                            <i class="material-icons">more_vert</i>
                        </button>
                        <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect"
                            for="actions-menu-lower-right-<?php echo $AccountSearchData->getAccountId(); ?>">

                            <?php foreach ($data->getDataActionsMenu() as $actionMenu): ?>
                                <?php if ($actionMenu->getReflectionFilter()->invoke($AccountSearchItem)): ?>
                                    <li class="btn-action mdl-menu__item"
                                        data-item-id="<?php echo $AccountSearchData->getAccountId(); ?>"
                                        <?php foreach ($actionMenu->getData() as $name => $actionData): ?>
                                            <?php echo 'data-', $name, '=', '"', $actionData, '"'; ?>
                                        <?php endforeach; ?>>
                                        <i class="material-icons <?php echo $actionMenu->getIcon()->getClass(); ?>"
                                           title="<?php echo $actionMenu->getTitle(); ?>"><?php echo $actionMenu->getIcon()->getIcon(); ?></i>
                                        <?php echo $actionMenu->getName(); ?>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>