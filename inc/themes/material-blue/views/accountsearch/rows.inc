<?php
/**
 * @var $data    SP\Html\DataGrid\DataGridTab
 * @var $Account SP\Account\AccountsSearchData
 * @var $this    SP\Core\Template
 */
?>
<div id="data-search">
    <?php foreach ($data->getData()->getData() as $index => $Account): ?>
        <?php if ($index === 'count'): continue; endif; ?>

        <div class="account-label round shadow">
            <div class="field-customer label-field">
                <div class="field-name"><?php echo _('Cliente'); ?></div>
                <div class="field-text round5 no-link"
                     style="background-color: <?php echo $Account->getColor(); ?>;">
                    <?php if (!empty($Account->getCustomerLink())): ?>
                        <a href="<?php echo $Account->getCustomerLink(); ?>" target="blank"
                           title="<?php echo _('Buscar en Wiki'); ?>&nbsp;<?php echo $Account->getCustomerName(); ?>">
                            <?php echo $Account->getCustomerName(); ?>
                        </a>
                    <?php else: ?>
                        <?php echo $Account->getCustomerName(); ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="field-account label-field">
                <div class="field-name"><?php echo _('Nombre'); ?></div>
                <?php if ($Account->isShow() && $Account->getLink()): ?>
                    <a title="<?php echo _('Detalles de Cuenta'); ?>" class="btn-action"
                       data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW; ?>"
                       data-item-id="<?php echo $Account->getId(); ?>"
                       data-onclick="account/view"><?php echo $Account->getName(); ?></a>
                <?php else: ?>
                    <div class="field-text"><?php echo $Account->getName(); ?></div>
                <?php endif; ?>
            </div>

            <div class="field-category label-field">
                <div class="field-name"><?php echo _('Categoría'); ?></div>
                <div class="field-text"><?php echo $Account->getCategoryName(); ?></div>
            </div>

            <?php if ($Account->isShow()): ?>
                <div class="field-user label-field">
                    <div class="field-name"><?php echo _('Usuario'); ?></div>
                    <div class="field-text"><?php echo $Account->getLogin(); ?></div>
                </div>

                <div class="field-url label-field">
                    <div class="field-name"><?php echo _('URL / IP'); ?></div>
                    <?php if ($Account->isUrlIslink()): ?>
                        <a href="<?php echo $Account->getUrl(); ?>" target="_blank"
                           title="<?php echo _('Abrir enlace a'), ': ', $Account->getUrl(); ?>"><?php echo $Account->getUrlShort(); ?></a>
                    <?php else: ?>
                        <div class="field-text"><?php echo $Account->getUrlShort(); ?></div>
                    <?php endif; ?>
                </div>

                <div class="account-info">
                    <?php if ($Account->isShow()): ?>
                        <i id="accesses-<?php echo $Account->getId(); ?>" class="material-icons">face</i>

                        <div class="mdl-tooltip" for="accesses-<?php echo $Account->getId(); ?>">
                            <?php echo _('Permisos'), '<br>', $Account->getAccesses(); ?>
                        </div>

                        <?php if ($Account->isFavorite()): ?>
                            <i class="material-icons icon-favorite mdl-color-text--amber-A100"
                               title="<?php echo _('Eliminar Favorito'); ?>"
                               data-action-id-on="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_FAVORITES_ADD; ?>"
                               data-action-id-off="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_FAVORITES_DELETE; ?>"
                               data-item-id="<?php echo $Account->getId(); ?>"
                               data-status="<?php echo 'on'; ?>">star</i>
                        <?php else: ?>
                            <i class="material-icons icon-favorite"
                               title="<?php echo _('Marcar Favorito'); ?>"
                               data-action-id-on="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_FAVORITES_ADD; ?>"
                               data-action-id-off="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_FAVORITES_DELETE; ?>"
                               data-item-id="<?php echo $Account->getId(); ?>"
                               data-status="<?php echo 'off'; ?>">star_border</i>
                        <?php endif; ?>

                        <?php if ($Account->getNotes() !== ''): ?>
                            <i id="notes-<?php echo $Account->getId(); ?>" class="material-icons">speaker_notes</i>
                            <div class="mdl-tooltip" for="notes-<?php echo $Account->getId(); ?>">
                                <?php echo _('Notas'), '<br>', $Account->getNotes(); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($Account->getNumFiles() > 0): ?>
                            <i id="attachments-<?php echo $Account->getId(); ?>" class="material-icons">attach_file</i>
                            <div class="mdl-tooltip" for="attachments-<?php echo $Account->getId(); ?>">
                                <?php echo _('Archivos adjuntos'), ': ', $Account->getNumFiles(); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($wikiFilter)): ?>
                            <?php if (preg_match('/^(' . $wikiFilter . ').*/i', $Account->getName())): ?>
                                <?php if (\SP\Account\AccountsSearchData::$dokuWikiEnabled): ?>
                                    <a href="<?php echo $wikiPageUrl, $Account->getName(); ?>" target="_blank">
                                        <i class="material-icons"
                                           title="<?php echo _('Enlace a Wiki'); ?>">library_books</i>
                                    </a>
                                    <i class="btn-action material-icons fg-green100"
                                       title="<?php echo _('Ver en Wiki'); ?>"
                                       data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_WIKI_VIEW; ?>"
                                       data-item-id="<?php echo $Account->getName() ?>"
                                       data-onclick="wiki/show">library_books</i>
                                <?php else: ?>
                                    <a href="<?php echo $wikiPageUrl, $Account->getName(); ?>" target="_blank">
                                        <i class="material-icons"
                                           title="<?php echo _('Enlace a Wiki'); ?>">library_books</i>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="tags-box">
                            <?php foreach ($Account->getTags() as $tag): ?>
                                <span class="tag"><?php echo $tag; ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="account-spacer"></div>
            <?php endif; ?>

            <div class="account-actions round">
                <?php foreach ($data->getDataActions() as $action): ?>
                    <?php if ($action->getReflectionFilter()->invoke($Account)): ?>
                        <i class="btn-action material-icons <?php echo $action->getIcon()->getClass(); ?>"
                           title="<?php echo $action->getTitle(); ?>"
                           data-item-id="<?php echo $Account->getId(); ?>"
                            <?php foreach ($action->getData() as $name => $actionData): ?>
                                <?php echo 'data-', $name, '=', '"', $actionData, '"'; ?>
                            <?php endforeach; ?>><?php echo $action->getIcon()->getIcon(); ?></i>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if (count($data->getDataActionsMenu()) > 0): ?>
                    <button id="actions-menu-lower-right-<?php echo $Account->getId(); ?>"
                            class="mdl-button mdl-js-button mdl-button--icon">
                        <i class="material-icons">more_vert</i>
                    </button>
                    <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect"
                        for="actions-menu-lower-right-<?php echo $Account->getId(); ?>">

                        <?php foreach ($data->getDataActionsMenu() as $actionMenu): ?>
                            <?php if ($actionMenu->getReflectionFilter()->invoke($Account)): ?>
                                <li class="btn-action mdl-menu__item"
                                    data-item-id="<?php echo $Account->getId(); ?>"
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
    <?php endforeach; ?>
</div>