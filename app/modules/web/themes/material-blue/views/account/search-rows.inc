<?php
/**
 * @var $data              SP\Html\DataGrid\DataGridTab
 * @var $accountSearchItem SP\Account\AccountSearchItem
 * @var $this              \SP\Mvc\View\Template
 * @var $action            \SP\Html\DataGrid\DataGridActionBase
 * @var $actionMenu        \SP\Html\DataGrid\DataGridActionBase
 * @var $icons             \SP\Core\UI\ThemeIcons
 */
?>
<div id="data-search">
    <?php foreach ($data->getData()->getData() as $index => $accountSearchItem):
        if ($index === 'count'): continue; endif;
        $accountSearchData = $accountSearchItem->getAccountSearchVData();
        ?>

        <div class="account-label round shadow">
            <div class="label-left">
                <div class="label-row-up">
                    <div class="field-customer label-field">
                        <div class="field-name-hidden"><?php echo __('Cliente'); ?></div>
                        <?php if (!empty($accountSearchItem->getClientLink())): ?>
                            <a href="<?php echo $accountSearchItem->getClientLink(); ?>" target="blank"
                               title="<?php echo __('Buscar en Wiki'); ?>">
                                <span class="mdl-chip mdl-chip--contact">
                                    <span style="background-color: <?php echo $accountSearchItem->getColor(); ?>;"
                                          class="mdl-chip__contact mdl-color-text--white"><?php echo mb_ucfirst($accountSearchData->getClientName()); ?></span>
                                    <span class="mdl-chip__text"
                                          title="<?php echo $accountSearchData->getClientName(); ?>"><?php echo $accountSearchItem->getShortClientName(); ?></span>
                                </span>
                            </a>
                        <?php else: ?>
                            <span class="mdl-chip mdl-chip--contact">
                                <span style="background-color: <?php echo $accountSearchItem->getColor(); ?>;"
                                      class="mdl-chip__contact mdl-color-text--white"><?php echo mb_ucfirst($accountSearchData->getClientName()); ?></span>
                                <span class="mdl-chip__text"
                                      title="<?php echo $accountSearchData->getClientName(); ?>"><?php echo $accountSearchItem->getShortClientName(); ?></span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="field-account field-text label-field">
                        <div class="field-name"><?php echo __('Nombre'); ?></div>
                        <?php if ($accountSearchItem->isShow() && $accountSearchItem->getLink()): ?>
                            <a title="<?php echo __('Detalles de Cuenta'); ?>" class="btn-action"
                               data-action-id="<?php echo \SP\Core\Acl\ActionsInterface::ACCOUNT_VIEW; ?>"
                               data-item-id="<?php echo $accountSearchData->getId(); ?>"
                               data-onclick="account/show"><?php echo $accountSearchData->getName(); ?></a>
                        <?php else: ?>
                            <div class="field-text">
                                <?php echo $accountSearchData->getName(); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="field-category field-text label-field">
                        <div class="field-name"><?php echo __('Categoría'); ?></div>
                        <div class="field-text"><?php echo $accountSearchData->getCategoryName(); ?></div>
                    </div>

                    <?php if ($accountSearchItem->isShow()): ?>
                        <div class="field-user field-text label-field">
                            <div class="field-name"><?php echo __('Usuario'); ?></div>
                            <div class="field-text"><?php echo $accountSearchItem->getShortLogin(); ?></div>
                        </div>

                        <div class="field-url field-text label-field">
                            <div class="field-name"><?php echo __('URL / IP'); ?></div>
                            <?php if ($accountSearchItem->isUrlIslink()): ?>
                                <a href="<?php echo $accountSearchData->getUrl(); ?>" target="_blank"
                                   title="<?php printf(__('Abrir enlace a: %s'), $accountSearchData->getUrl()); ?>"><?php echo $accountSearchItem->getShortUrl(); ?></a>
                            <?php else: ?>
                                <div class="field-text"><?php echo $accountSearchItem->getShortUrl(); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="field-user field-text label-field"></div>
                        <div class="field-url field-text label-field"></div>
                    <?php endif; ?>
                </div>
                <div class="label-row-down">
                    <div class="tags-box">
                        <?php foreach ($accountSearchItem->getTags() as $tag): ?>
                            <span class="tag" data-tag-id="<?php echo $tag->id; ?>"><?php echo $tag->name; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="label-right">
                <div class="account-info">
                    <?php if ($accountSearchItem->isShow()): ?>
                        <?php if ($accountSearchItem->isPasswordExpired()): ?>
                            <i id="icon-expired-<?php echo $accountSearchData->getId(); ?>"
                               class="material-icons <?php echo $icons->getIconWarning()->getClass(); ?>">
                                <?php echo $icons->getIconWarning()->getIcon(); ?></i>
                            <span
                                    for="icon-expired-<?php echo $accountSearchData->getId(); ?>"
                                    class="mdl-tooltip mdl-tooltip--top"><?php echo __('Clave Caducada'); ?></span>
                        <?php endif; ?>

                        <?php if ($accountSearchData->getIsPrivate() === 1): ?>
                            <i id="icon-private-<?php echo $accountSearchData->getId(); ?>"
                               class="material-icons">lock</i>
                            <span
                                    for="icon-private-<?php echo $accountSearchData->getId(); ?>"
                                    class="mdl-tooltip mdl-tooltip--top"><?php echo __('Cuenta Privada'); ?></span>
                        <?php elseif ($accountSearchData->getIsPrivateGroup() === 1): ?>
                            <i id="icon-private-<?php echo $accountSearchData->getId(); ?>"
                               class="material-icons">lock_open</i>
                            <span
                                    for="icon-private-<?php echo $accountSearchData->getId(); ?>"
                                    class="mdl-tooltip mdl-tooltip--top"><?php echo __('Cuenta Privada (Grupo)'); ?></span>
                        <?php else: ?>
                            <i id="accesses-<?php echo $accountSearchData->getId(); ?>"
                               class="material-icons">face</i>

                            <div class="mdl-tooltip mdl-tooltip--top"
                                 for="accesses-<?php echo $accountSearchData->getId(); ?>">
                                <div class="tooltip-header"><?php echo __('Permisos'); ?></div>
                                <?php echo implode('<br>', $accountSearchItem->getAccesses()); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($accountSearchItem->isFavorite()): ?>
                            <i id="btn-favorite-<?php echo $accountSearchData->getId(); ?>"
                               class="material-icons icon-favorite mdl-color-text--amber-A100"
                               data-action-route-on="<?php echo $favoriteRouteOn; ?>"
                               data-action-route-off="<?php echo $favoriteRouteOff; ?>"
                               data-item-id="<?php echo $accountSearchData->getId(); ?>"
                               data-status="<?php echo 'on'; ?>">star</i>
                            <span
                                    for="btn-favorite-<?php echo $accountSearchData->getId(); ?>"
                                    class="mdl-tooltip mdl-tooltip--top"><?php echo __('Eliminar Favorito'); ?></span>
                        <?php else: ?>
                            <i id="btn-favorite-<?php echo $accountSearchData->getId(); ?>"
                               class="material-icons icon-favorite"
                               data-action-route-on="<?php echo $favoriteRouteOn; ?>"
                               data-action-route-off="<?php echo $favoriteRouteOff; ?>"
                               data-item-id="<?php echo $accountSearchData->getId(); ?>"
                               data-status="<?php echo 'off'; ?>">star_border</i>
                            <span
                                    for="btn-favorite-<?php echo $accountSearchData->getId(); ?>"
                                    class="mdl-tooltip mdl-tooltip--top"><?php echo __('Marcar Favorito'); ?></span>
                        <?php endif; ?>

                        <?php if ($accountSearchData->getNotes() !== ''): ?>
                            <i id="notes-<?php echo $accountSearchData->getId(); ?>" class="material-icons">speaker_notes</i>
                            <div class="mdl-tooltip mdl-tooltip--top"
                                 for="notes-<?php echo $accountSearchData->getId(); ?>">
                                <div class="tooltip-header"><?php echo __('Notes'); ?></div>
                                <?php echo $accountSearchItem->getShortNotes(); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($accountSearchItem->getNumFiles() > 0): ?>
                            <i id="attachments-<?php echo $accountSearchData->getId(); ?>"
                               class="material-icons">attach_file</i>
                            <div class="mdl-tooltip mdl-tooltip--top"
                                 for="attachments-<?php echo $accountSearchData->getId(); ?>">
                                <?php echo __('Archivos adjuntos'), ': ', $accountSearchItem->getNumFiles(); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($wikiFilter)): ?>
                            <?php if (preg_match('/^(' . $wikiFilter . ').*/i', $accountSearchData->getName())): ?>
                                <?php if (\SP\Account\AccountSearchItem::$dokuWikiEnabled): ?>
                                    <a href="<?php echo $wikiPageUrl, $accountSearchData->getName(); ?>"
                                       target="_blank">
                                        <i class="material-icons"
                                           title="<?php echo __('Enlace a Wiki'); ?>">library_books</i>
                                    </a>
                                    <i class="btn-action material-icons fg-green100"
                                       title="<?php echo __('Ver en Wiki'); ?>"
                                       data-action-id="<?php echo \SP\Core\Acl\ActionsInterface::WIKI_VIEW; ?>"
                                       data-pagename="<?php echo $accountSearchData->getName(); ?>"
                                       data-onclick="wiki/show">library_books</i>
                                <?php else: ?>
                                    <a href="<?php echo $wikiPageUrl, $accountSearchData->getName(); ?>"
                                       target="_blank">
                                        <i class="material-icons"
                                           title="<?php echo __('Enlace a Wiki'); ?>">library_books</i>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>


                <?php if ($accountSearchItem->isShow() || $accountSearchItem->isShowRequest()): ?>
                    <div class="account-actions">
                        <?php foreach ($data->getDataActionsFiltered($accountSearchItem) as $action): ?>
                            <i id="btn-action-<?php echo $accountSearchData->getId(), '-', $action->getIcon()->getIcon(); ?>"
                               class="btn-action material-icons <?php echo $action->getIcon()->getClass(); ?>"
                               data-item-id="<?php echo $accountSearchData->getId(); ?>"
                               data-parent-id="<?php echo $accountSearchData->getParentId(); ?>"
                                <?php foreach ($action->getData() as $name => $actionData): printf('data-%s="%s"', $name, $actionData); endforeach; ?>>
                                <?php echo $action->getIcon()->getIcon(); ?></i>
                            <span
                                    for="btn-action-<?php echo $accountSearchData->getId(), '-', $action->getIcon()->getIcon(); ?>"
                                    class="mdl-tooltip mdl-tooltip--bottom"><?php echo $action->getTitle(); ?></span>
                        <?php endforeach; ?>

                        <?php if ($data->getDataActionsMenuCount()): $actionsMenu = $data->getDataActionsMenuFiltered($accountSearchItem); ?>
                            <?php if (count($actionsMenu) > 0): ?>
                                <button id="actions-menu-lower-right-<?php echo $accountSearchData->getId(); ?>"
                                        class="mdl-button mdl-js-button mdl-button--icon">
                                    <i class="material-icons">more_vert</i>
                                </button>
                                <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect"
                                    for="actions-menu-lower-right-<?php echo $accountSearchData->getId(); ?>">

                                    <?php foreach ($actionsMenu as $actionMenu): ?>
                                        <li class="btn-action mdl-menu__item"
                                            data-item-id="<?php echo $accountSearchData->getId(); ?>"
                                            data-parent-id="<?php echo $accountSearchData->getParentId(); ?>"
                                            <?php foreach ($actionMenu->getData() as $dataName => $dataValue): printf('data-%s="%s"', $dataName, $dataValue); endforeach; ?>>
                                            <i class="material-icons <?php echo $actionMenu->getIcon()->getClass(); ?>"
                                               title="<?php echo $actionMenu->getTitle(); ?>"><?php echo $actionMenu->getIcon()->getIcon(); ?></i>
                                            <?php echo $actionMenu->getName(); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>