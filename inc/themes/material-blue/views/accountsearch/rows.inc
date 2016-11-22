<?php
/**
 * @var $data              SP\Html\DataGrid\DataGridTab
 * @var $AccountSearchItem SP\Account\AccountsSearchItem
 * @var $this              SP\Core\Template
 * @var $action            \SP\Html\DataGrid\DataGridActionBase
 * @var $actionMenu        \SP\Html\DataGrid\DataGridActionBase
 * @var $icons             \SP\Core\UI\ThemeIconsBase
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
                               title="<?php echo _('Buscar en Wiki'); ?>">
                                <span class="mdl-chip mdl-chip--contact">
                                    <span style="background-color: <?php echo $AccountSearchItem->getColor(); ?>;"
                                          class="mdl-chip__contact mdl-color-text--white"><?php echo strtoupper($AccountSearchData->getCustomerName()[0]); ?></span>
                                    <span class="mdl-chip__text"
                                          title="<?php echo $AccountSearchData->getCustomerName(); ?>"><?php echo $AccountSearchItem->getShortCustomerName(); ?></span>
                                </span>
                            </a>
                        <?php else: ?>
                            <span class="mdl-chip mdl-chip--contact">
                                <span style="background-color: <?php echo $AccountSearchItem->getColor(); ?>;"
                                      class="mdl-chip__contact mdl-color-text--white"><?php echo strtoupper($AccountSearchData->getCustomerName()[0]); ?></span>
                                <span class="mdl-chip__text"
                                      title="<?php echo $AccountSearchData->getCustomerName(); ?>"><?php echo $AccountSearchItem->getShortCustomerName(); ?></span>
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
                    <?php endif; ?>
                </div>
                <div class="label-row-down">
                    <div class="tags-box">
                        <?php foreach ($AccountSearchData->getTags() as $tagId => $tagName): ?>
                            <span class="tag" data-tag-id="<?php echo $tagId; ?>"><?php echo $tagName; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="label-right">
                <?php if ($AccountSearchItem->isShow()): ?>
                    <div class="account-info">
                        <?php if ($AccountSearchItem->isPasswordExpired()): ?>
                            <i id="icon-expired-<?php echo $AccountSearchData->getAccountId(); ?>"
                               class="material-icons <?php echo $icons->getIconWarning()->getClass(); ?>">
                                <?php echo $icons->getIconWarning()->getIcon(); ?></i>
                            <span
                                for="icon-expired-<?php echo $AccountSearchData->getAccountId(); ?>"
                                class="mdl-tooltip mdl-tooltip--top"><?php echo _('Clave Caducada'); ?></span>
                        <?php endif; ?>

                        <?php if ($AccountSearchData->getAccountIsPrivate() === 0): ?>
                            <i id="accesses-<?php echo $AccountSearchData->getAccountId(); ?>"
                               class="material-icons">face</i>

                            <div class="mdl-tooltip mdl-tooltip--top"
                                 for="accesses-<?php echo $AccountSearchData->getAccountId(); ?>">
                                <?php echo _('Permisos'), '<br>', $AccountSearchItem->getAccesses(); ?>
                            </div>
                        <?php else: ?>
                            <i id="icon-private-<?php echo $AccountSearchData->getAccountId(); ?>"
                               class="material-icons">lock</i>
                            <span
                                for="icon-private-<?php echo $AccountSearchData->getAccountId(); ?>"
                                class="mdl-tooltip mdl-tooltip--top"><?php echo _('Cuenta Privada'); ?></span>
                        <?php endif; ?>

                        <?php if ($AccountSearchItem->isFavorite()): ?>
                            <i id="btn-favorite-<?php echo $AccountSearchData->getAccountId(); ?>"
                               class="material-icons icon-favorite mdl-color-text--amber-A100"
                               data-action-id-on="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_FAVORITES_ADD; ?>"
                               data-action-id-off="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_FAVORITES_DELETE; ?>"
                               data-item-id="<?php echo $AccountSearchData->getAccountId(); ?>"
                               data-status="<?php echo 'on'; ?>">star</i>
                            <span
                                for="btn-favorite-<?php echo $AccountSearchData->getAccountId(); ?>"
                                class="mdl-tooltip mdl-tooltip--top"><?php echo _('Eliminar Favorito'); ?></span>
                        <?php else: ?>
                            <i id="btn-favorite-<?php echo $AccountSearchData->getAccountId(); ?>"
                               class="material-icons icon-favorite"
                               data-action-id-on="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_FAVORITES_ADD; ?>"
                               data-action-id-off="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_FAVORITES_DELETE; ?>"
                               data-item-id="<?php echo $AccountSearchData->getAccountId(); ?>"
                               data-status="<?php echo 'off'; ?>">star_border</i>
                            <span
                                for="btn-favorite-<?php echo $AccountSearchData->getAccountId(); ?>"
                                class="mdl-tooltip mdl-tooltip--top"><?php echo _('Marcar Favorito'); ?></span>
                        <?php endif; ?>

                        <?php if ($AccountSearchData->getAccountNotes() !== ''): ?>
                            <i id="notes-<?php echo $AccountSearchData->getAccountId(); ?>" class="material-icons">speaker_notes</i>
                            <div class="mdl-tooltip mdl-tooltip--top"
                                 for="notes-<?php echo $AccountSearchData->getAccountId(); ?>">
                                <?php echo _('Notas'), '<br>', $AccountSearchItem->getShortNotes(); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($AccountSearchItem->getNumFiles() > 0): ?>
                            <i id="attachments-<?php echo $AccountSearchData->getAccountId(); ?>"
                               class="material-icons">attach_file</i>
                            <div class="mdl-tooltip mdl-tooltip--top"
                                 for="attachments-<?php echo $AccountSearchData->getAccountId(); ?>">
                                <?php echo _('Archivos adjuntos'), ': ', $AccountSearchItem->getNumFiles(); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($wikiFilter)): ?>
                            <?php if (preg_match('/^(' . $wikiFilter . ').*/i', $AccountSearchData->getAccountName())): ?>
                                <?php if (\SP\Account\AccountsSearchItem::$dokuWikiEnabled): ?>
                                    <a href="<?php echo $wikiPageUrl, $AccountSearchData->getAccountName(); ?>"
                                       target="_blank">
                                        <i class="material-icons"
                                           title="<?php echo _('Enlace a Wiki'); ?>">library_books</i>
                                    </a>
                                    <i class="btn-action material-icons fg-green100"
                                       title="<?php echo _('Ver en Wiki'); ?>"
                                       data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_WIKI_VIEW; ?>"
                                       data-item-id="<?php echo $AccountSearchData->getAccountName(); ?>"
                                       data-onclick="wiki/show">library_books</i>
                                <?php else: ?>
                                    <a href="<?php echo $wikiPageUrl, $AccountSearchData->getAccountName(); ?>"
                                       target="_blank">
                                        <i class="material-icons"
                                           title="<?php echo _('Enlace a Wiki'); ?>">library_books</i>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($AccountSearchItem->isShow() || $AccountSearchItem->isShowRequest()): ?>
                    <div class="account-actions">
                        <?php foreach ($data->getDataActions() as $action): ?>
                            <?php if ($action->getReflectionFilter()->invoke($AccountSearchItem)): ?>
                                <i id="btn-action-<?php echo $AccountSearchData->getAccountId(), '-', $action->getIcon()->getIcon(); ?>"
                                   class="btn-action material-icons <?php echo $action->getIcon()->getClass(); ?>"
                                   data-item-id="<?php echo $AccountSearchData->getAccountId(); ?>"
                                    <?php foreach ($action->getData() as $name => $actionData): ?>
                                        <?php echo 'data-', $name, '=', '"', $actionData, '"'; ?>
                                    <?php endforeach; ?>><?php echo $action->getIcon()->getIcon(); ?></i>
                                <span
                                    for="btn-action-<?php echo $AccountSearchData->getAccountId(), '-', $action->getIcon()->getIcon(); ?>"
                                    class="mdl-tooltip mdl-tooltip--bottom"><?php echo $action->getTitle(); ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <?php if (!$AccountSearchItem->isShowRequest() && count($data->getDataActionsMenu()) > 0): ?>
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
                                            <?php foreach ($actionMenu->getData() as $dataName => $dataValue): ?>
                                                <?php echo 'data-', $dataName, '=', '"', $dataValue, '"'; ?>
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
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>